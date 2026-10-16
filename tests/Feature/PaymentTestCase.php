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
 * Shared fixture for payment tests: one outlet, device TESTSN0001
 * (washer RM5/6/7, pulse_price RM1), online in Redis, customer "alice",
 * an active Fiuu gateway (secret SECRET, verify key VERIFY).
 *
 * (Originally the top of PaymentSecurityTest, which covers fixes F1–F4 from the 2026-10-14 review:
 *  F1 no auto-login on return URLs, unguessable order ids, protected result pages
 *  F2 duplicate Fiuu callbacks never start the machine twice
 *  F3 duplicate top-up callbacks never credit twice
 *  F4 e-wallet spend is locked, ledgered, uses bonus, and blocks double taps
 */
abstract class PaymentTestCase extends TestCase
{
    use RefreshDatabase;

    protected PaymentGatewaySetting $gw;
    protected Customer $customer;
    protected int $deviceOutletId;
    protected string $serial = 'TESTSN0001';

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

    protected function makeCustomer(string $name): Customer
    {
        return Customer::forceCreate([
            'name' => $name, 'email' => "$name@example.com", 'username' => $name,
            'password' => bcrypt('secret'), 'referral_code' => strtoupper($name),
        ]);
    }

    /** A Fiuu result payload with a correct skey. */
    protected function fiuu(string $orderId, string $amount, string $status = '00', string $tranId = 'T123'): array
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

    protected function deviceOrder(string $orderId = 'ORD-M-TEST1', ?int $customerId = null, string $amount = '5.00'): DeviceTransaction
    {
        return DeviceTransaction::create([
            'customer_id' => $customerId, 'device_outlet_id' => $this->deviceOutletId,
            'amount' => $amount, 'currency' => 'MYR', 'status' => 'initiated', 'provider' => 'fiuu',
            'order_id' => $orderId, 'meta' => ['type' => 'device', 'mode' => 'washer_cold'],
        ]);
    }

    protected function wallet(string $credit, string $bonus = '0'): EwalletAccount
    {
        return EwalletAccount::create(['user_id' => $this->customer->id, 'credit_balance' => $credit, 'bonus_balance' => $bonus]);
    }
}
