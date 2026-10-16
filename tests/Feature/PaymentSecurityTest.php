<?php

namespace Tests\Feature;

use App\Jobs\SendMqttCommand;
use App\Models\Customer;
use App\Models\DeviceTransaction;
use App\Models\EwalletAccount;
use App\Models\EwalletTransaction;
use App\Models\PaymentGatewaySetting;
use App\Models\PaymentGatewayTransaction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;
use Tests\TestCase;

/**
 * Covers fixes F1–F4 from the 2026-10-14 review:
 *  F1 no auto-login on return URLs, unguessable order ids, protected result pages
 *  F2 duplicate Fiuu callbacks never start the machine twice
 *  F3 duplicate top-up callbacks never credit twice
 *  F4 e-wallet spend is locked, ledgered, uses bonus, and blocks double taps
 */
class PaymentSecurityTest extends TestCase
{
    use RefreshDatabase;

    private PaymentGatewaySetting $gw;
    private Customer $customer;
    private int $deviceOutletId;
    private string $serial = 'TESTSN0001';

    protected function setUp(): void
    {
        parent::setUp();
        Queue::fake();

        $this->gw = PaymentGatewaySetting::forceCreate([
            'merchant_id' => 'merchant_x', 'terminal_id' => 't1',
            'secret_key' => 'SECRET', 'private_key' => 'VERIFY', 'status' => 'active',
        ]);

        $this->customer = $this->makeCustomer('alice');

        $now = now();
        $managerId  = DB::table('managers')->insertGetId(['name' => 'M', 'created_at' => $now, 'updated_at' => $now]);
        $brandId    = DB::table('brands')->insertGetId(['name' => 'B', 'created_at' => $now, 'updated_at' => $now]);
        $statusId   = DB::table('type_statuses')->insertGetId(['name' => 'Active', 'created_at' => $now, 'updated_at' => $now]);
        $typeId     = DB::table('type_outlets')->insertGetId(['name' => 'Laundry', 'created_at' => $now, 'updated_at' => $now]);
        $supplierId = DB::table('suppliers')->insertGetId(['supplier_name' => 'S', 'created_at' => $now, 'updated_at' => $now]);
        $outletId   = DB::table('outlets')->insertGetId([
            'outlet_name' => 'Outlet 1', 'manager_id' => $managerId, 'brand_id' => $brandId,
            'status_id' => $statusId, 'type_id' => $typeId, 'created_at' => $now, 'updated_at' => $now,
        ]);
        DB::table('devices')->insert([
            'serial_number' => $this->serial, 'model' => 'LBP', 'supplier_id' => $supplierId,
            'washer_cold_price' => 5.00, 'washer_warm_price' => 6.00, 'washer_hot_price' => 7.00,
            'pulse_price' => 1.00, 'pulse_width' => 100, 'pulse_delay' => 100,
            'created_at' => $now, 'updated_at' => $now,
        ]);
        $this->deviceOutletId = DB::table('device_has_outlet')->insertGetId([
            'outlet_id' => $outletId, 'machine_num' => '1', 'machine_name' => 'W1', 'machine_type' => 'Washer',
            'device_serial_number' => $this->serial, 'status' => 'Online', 'created_at' => $now, 'updated_at' => $now,
        ]);

        Redis::setex("machine:status:{$this->serial}", 600, 'online');
    }

    protected function tearDown(): void
    {
        Redis::del("machine:status:{$this->serial}");
        parent::tearDown();
    }

    // ───────────────────────── helpers ─────────────────────────

    private function makeCustomer(string $name): Customer
    {
        return Customer::forceCreate([
            'name' => $name, 'email' => "$name@example.com", 'username' => $name,
            'password' => bcrypt('secret'), 'referral_code' => strtoupper($name),
        ]);
    }

    /** A Fiuu result payload with a correct skey. */
    private function fiuu(string $orderId, string $amount, string $status = '00', string $tranId = 'T123'): array
    {
        $p = [
            'tranID' => $tranId, 'orderid' => $orderId, 'status' => $status, 'domain' => 'merchant_x',
            'amount' => $amount, 'currency' => 'MYR', 'appcode' => 'A1', 'paydate' => '2026-10-14 21:00:00',
            'nbcb' => '1',
        ];
        $key0 = md5($p['tranID'] . $p['orderid'] . $p['status'] . $p['domain'] . $p['amount'] . $p['currency']);
        $p['skey'] = md5($p['paydate'] . $p['domain'] . $key0 . $p['appcode'] . 'SECRET');
        return $p;
    }

    private function deviceOrder(string $orderId = 'ORD-M-TEST1', ?int $customerId = null, string $amount = '5.00'): DeviceTransaction
    {
        return DeviceTransaction::create([
            'customer_id' => $customerId, 'device_outlet_id' => $this->deviceOutletId,
            'amount' => $amount, 'currency' => 'MYR', 'status' => 'initiated', 'provider' => 'fiuu',
            'order_id' => $orderId, 'meta' => ['type' => 'device', 'mode' => 'washer_cold'],
        ]);
    }

    private function wallet(string $credit, string $bonus = '0'): EwalletAccount
    {
        return EwalletAccount::create(['user_id' => $this->customer->id, 'credit_balance' => $credit, 'bonus_balance' => $bonus]);
    }

    // ───────────────────────── F2: device callbacks ─────────────────────────

    public function test_duplicate_device_callbacks_start_the_machine_only_once(): void
    {
        $txn = $this->deviceOrder(customerId: $this->customer->id);
        $payload = $this->fiuu($txn->order_id, '5.00');

        $this->post('/guest/payment/callback', $payload)->assertOk()->assertSee('CBTOKEN:MPSTATOK');
        $this->post('/guest/payment/callback', $payload)->assertOk()->assertSee('CBTOKEN:MPSTATOK');
        $this->post('/customer/customer/payment/callback', $payload)->assertOk();

        Queue::assertPushed(SendMqttCommand::class, 1);
        Queue::assertPushed(SendMqttCommand::class, fn ($job) => $job->payload['pulses'] === 5
            && $job->payload['op_id'] === $txn->fresh()->meta['op_id']);
        $this->assertSame('activated', $txn->fresh()->status);
    }

    public function test_invalid_signature_is_rejected_and_changes_nothing(): void
    {
        $txn = $this->deviceOrder();
        $payload = $this->fiuu($txn->order_id, '5.00');
        $payload['skey'] = str_repeat('0', 32);

        $this->post('/guest/payment/callback', $payload)->assertStatus(400);

        Queue::assertNothingPushed();
        $this->assertSame('initiated', $txn->fresh()->status);
    }

    public function test_amount_mismatch_does_not_start_machine(): void
    {
        $txn = $this->deviceOrder(amount: '5.00');

        $this->post('/guest/payment/callback', $this->fiuu($txn->order_id, '1.00'))->assertOk();

        Queue::assertNothingPushed();
        $this->assertSame('review', $txn->fresh()->status);
    }

    public function test_late_failure_notice_does_not_overwrite_success(): void
    {
        $txn = $this->deviceOrder();
        $this->post('/guest/payment/callback', $this->fiuu($txn->order_id, '5.00'))->assertOk();
        $this->post('/guest/payment/callback', $this->fiuu($txn->order_id, '5.00', '11'))->assertOk();

        $this->assertSame('activated', $txn->fresh()->status);
    }

    // ───────────────────────── F1: return URLs / pages ─────────────────────────

    public function test_return_url_never_logs_anyone_in(): void
    {
        $txn = $this->deviceOrder(customerId: $this->customer->id);

        // Attacker replays the return URL with only an order id.
        $res = $this->get('/customer/customer/payment/return?orderid=' . $txn->order_id . '&status=00');

        $this->assertGuest('customer');
        Queue::assertNothingPushed(); // no valid signature -> nothing settled
        $this->assertSame('initiated', $txn->fresh()->status);
        $res->assertRedirect();
    }

    public function test_valid_return_settles_and_redirects_to_a_signed_page(): void
    {
        $txn = $this->deviceOrder();

        $res = $this->post('/guest/payment/return', $this->fiuu($txn->order_id, '5.00'));
        $res->assertRedirectContains('/device/' . $txn->id . '/start?expires=');

        $this->get($res->headers->get('Location'))->assertOk();
        Queue::assertPushed(SendMqttCommand::class, 1);

        // Callback arriving after the return must not start it again.
        $this->post('/guest/payment/callback', $this->fiuu($txn->order_id, '5.00'))->assertOk();
        Queue::assertPushed(SendMqttCommand::class, 1);
    }

    public function test_result_pages_are_not_browsable_by_id(): void
    {
        $txn = $this->deviceOrder(customerId: $this->customer->id);
        $mallory = $this->makeCustomer('mallory');

        $this->get("/device/{$txn->id}/start")->assertForbidden();               // guest page, no signature
        $this->get("/customer/customer/device/{$txn->id}/start")->assertRedirect();         // not logged in -> login
        $this->actingAs($mallory, 'customer')->get("/customer/customer/device/{$txn->id}/start")->assertForbidden();
        $this->actingAs($this->customer, 'customer')->get("/customer/customer/device/{$txn->id}/start")->assertOk();
    }

    public function test_order_ids_are_unguessable_and_unique(): void
    {
        $a = \App\Support\OrderId::make('M');
        $b = \App\Support\OrderId::make('M');

        $this->assertNotSame($a, $b);
        $this->assertMatchesRegularExpression('/^ORD-M-[0-9A-HJKMNP-TV-Z]{26}$/', $a);
    }

    // ───────────────────────── F3: top-ups ─────────────────────────

    private function topupOrder(string $amount = '10.00'): PaymentGatewayTransaction
    {
        return PaymentGatewayTransaction::create([
            'user_id' => $this->customer->id, 'amount' => $amount, 'currency' => 'MYR',
            'status' => 'initiated', 'provider' => 'fiuu', 'order_id' => 'ORD-T-TEST1',
        ]);
    }

    public function test_duplicate_topup_callbacks_credit_only_once_including_bonus(): void
    {
        DB::table('topup_packages')->insert(['topup_amount' => 10.00, 'bonus_amount' => 2.00, 'is_active' => 1]);
        $pg = $this->topupOrder('10.00');
        $payload = $this->fiuu($pg->order_id, '10.00');

        $this->post('/customer/gateway/callback', $payload)->assertOk();
        $this->post('/customer/gateway/callback', $payload)->assertOk();
        $this->post('/customer/gateway/return', $payload)->assertRedirect();

        $acct = EwalletAccount::where('user_id', $this->customer->id)->first();
        $this->assertEquals('10.00', $acct->credit_balance);
        $this->assertEquals('2.00', $acct->bonus_balance);
        $this->assertSame(1, EwalletTransaction::where('reference', $pg->order_id)->count());
        $this->assertSame('paid', $pg->fresh()->status);
    }

    public function test_forged_topup_callback_cannot_fail_a_pending_topup(): void
    {
        $pg = $this->topupOrder();
        $payload = $this->fiuu($pg->order_id, '10.00', '11');
        $payload['skey'] = 'forged';

        $this->post('/customer/gateway/callback', $payload)->assertStatus(400);
        $this->assertSame('initiated', $pg->fresh()->status);
    }

    public function test_topup_return_does_not_log_in(): void
    {
        $pg = $this->topupOrder();
        $this->get('/customer/gateway/return?orderid=' . $pg->order_id);
        $this->assertGuest('customer');
    }

    // ───────────────────────── F4: e-wallet spend ─────────────────────────

    public function test_wallet_payment_uses_credit_then_bonus_and_writes_ledger(): void
    {
        $this->wallet('3.00', '4.00');

        $this->actingAs($this->customer, 'customer')
            ->post('/customer/customer/payment/ewallet', ['device_outlet_id' => $this->deviceOutletId, 'mode' => 'washer_cold'])
            ->assertRedirect();

        $acct = EwalletAccount::where('user_id', $this->customer->id)->first();
        $this->assertEquals('0.00', $acct->credit_balance);
        $this->assertEquals('2.00', $acct->bonus_balance);

        $ledger = EwalletTransaction::where('transaction_type', 'deduction')->sole();
        $this->assertEquals('5.00', $ledger->amount);
        $this->assertEquals('3.00', $ledger->deduct_amount);
        $this->assertEquals('2.00', $ledger->deduct_bonus);
        $this->assertEquals('2.00', $ledger->remaining_balance);

        $txn = DeviceTransaction::where('provider', 'ewallet')->sole();
        $this->assertSame($ledger->reference, $txn->order_id);
        $this->assertSame('activated', $txn->status);
        Queue::assertPushed(SendMqttCommand::class, 1);
    }

    public function test_wallet_insufficient_balance_charges_nothing(): void
    {
        $this->wallet('2.00', '1.00');

        $this->actingAs($this->customer, 'customer')
            ->from('/customer/customer/devices')
            ->post('/customer/customer/payment/ewallet', ['device_outlet_id' => $this->deviceOutletId, 'mode' => 'washer_cold'])
            ->assertSessionHas('error', 'Insufficient wallet balance.');

        $this->assertEquals('2.00', EwalletAccount::first()->credit_balance);
        $this->assertSame(0, DeviceTransaction::count());
        Queue::assertNothingPushed();
    }

    public function test_wallet_double_tap_charges_once(): void
    {
        $this->wallet('20.00');
        $body = ['device_outlet_id' => $this->deviceOutletId, 'mode' => 'washer_cold'];

        $this->actingAs($this->customer, 'customer')->post('/customer/customer/payment/ewallet', $body);
        $this->actingAs($this->customer, 'customer')->post('/customer/customer/payment/ewallet', $body);

        $this->assertEquals('15.00', EwalletAccount::first()->credit_balance);
        $this->assertSame(1, DeviceTransaction::count());
        Queue::assertPushed(SendMqttCommand::class, 1);
    }

    public function test_wallet_payment_requires_login(): void
    {
        $this->post('/guest/payment/ewallet', ['device_outlet_id' => $this->deviceOutletId, 'mode' => 'washer_cold'])
            ->assertRedirect(route('customer.login'));
    }
}
