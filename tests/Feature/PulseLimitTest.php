<?php

namespace Tests\Feature;

use App\Jobs\SendMqttCommand;
use App\Models\Device;
use App\Models\DeviceTransaction;
use App\Models\EwalletAccount;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use PhpMqtt\Client\Facades\MQTT;
use Spatie\Permission\Models\Permission;

/**
 * Bug 2026-10-16: RM1.00 credited 100 pulses but RM2.00 credited nothing
 * extra. pulse_price RM0.01 -> RM2.00 = 200 pulses, and firmware <= v1.5.4
 * rejected anything over a hard-coded 100 ("too_many_pulses").
 * The backend now owns the limit (config lbpay.max_pulses_per_start),
 * pushes it to the device in CONFIG, and refuses BEFORE charging.
 *
 * Reuses the PaymentTestCase fixture (outlet, device TESTSN0001,
 * washer_cold RM5, customer alice).
 */
class PulseLimitTest extends PaymentTestCase
{
    private function setPulsePrice(string $pulsePrice, array $extra = []): void
    {
        DB::table('devices')->where('serial_number', 'TESTSN0001')
            ->update(array_merge(['pulse_price' => $pulsePrice], $extra));
    }

    private function technician(): User
    {
        foreach (['devices_outlet.manage', 'devices_outlet.edit', 'outlets.view-all'] as $p) {
            Permission::findOrCreate($p, 'web');
        }
        $u = User::forceCreate(['name' => 'Tech', 'email' => 'tech@example.com', 'password' => bcrypt('x')]);
        $u->givePermissionTo(['devices_outlet.manage', 'devices_outlet.edit', 'outlets.view-all']);
        return $u;
    }

    public function test_rm2_at_one_sen_per_pulse_is_exactly_200_pulses(): void
    {
        $this->setPulsePrice('0.01');
        $device = Device::where('serial_number', 'TESTSN0001')->first();

        $this->assertSame(100, $device->pulsesFor(1.00));
        $this->assertSame(200, $device->pulsesFor(2.00));
        $this->assertSame(570, $device->pulsesFor(5.70)); // float noise would give 571
    }

    public function test_technician_start_rm2_sends_200_pulses(): void
    {
        $this->setPulsePrice('0.01');

        $this->actingAs($this->technician(), 'sanctum')
            ->postJson('/api/technician/devices/TESTSN0001/start', ['type' => 'custom', 'price' => 2.00])
            ->assertOk()->assertJson(['pulses' => 200]);

        Queue::assertPushed(SendMqttCommand::class, fn ($j) => $j->action === 'REMOTE_START' && $j->payload['pulses'] === 200);
    }

    public function test_technician_start_over_limit_is_refused_with_clear_message(): void
    {
        config(['lbpay.max_pulses_per_start' => 100]); // e.g. fleet still on v1.5.4
        $this->setPulsePrice('0.01');

        $this->actingAs($this->technician(), 'sanctum')
            ->postJson('/api/technician/devices/TESTSN0001/start', ['type' => 'custom', 'price' => 2.00])
            ->assertStatus(422)->assertJsonFragment(['message' => 'RM2.00 needs 200 pulses; the controller accepts at most 100 per start.']);

        Queue::assertNothingPushed();
    }

    public function test_customer_is_not_charged_when_price_exceeds_limit(): void
    {
        config(['lbpay.max_pulses_per_start' => 100]);
        $this->setPulsePrice('0.01'); // washer_cold RM5 = 500 pulses > 100
        EwalletAccount::create(['user_id' => \App\Models\Customer::first()->id, 'credit_balance' => '20.00', 'bonus_balance' => 0]);

        $this->actingAs(\App\Models\Customer::first(), 'customer')
            ->post('/customer/customer/payment/ewallet', ['device_outlet_id' => DB::table('device_has_outlet')->value('id'), 'mode' => 'washer_cold'])
            ->assertStatus(422);

        $this->assertEquals('20.00', EwalletAccount::first()->credit_balance);
        $this->assertSame(0, DeviceTransaction::count());
        Queue::assertNothingPushed();
    }

    public function test_saving_parameters_that_exceed_limit_is_rejected(): void
    {
        config(['lbpay.max_pulses_per_start' => 100]);

        $this->actingAs($this->technician(), 'sanctum')
            ->putJson('/api/technician/devices/TESTSN0001/parameters', ['pulse_price' => 0.01, 'washer_cold_price' => 2.00])
            ->assertStatus(422)->assertJsonValidationErrors('pulse_price');
    }

    public function test_config_push_tells_the_device_its_limit(): void
    {
        config(['lbpay.max_pulses_per_start' => 1000]);
        MQTT::shouldReceive('publish')->once()->withArgs(function ($topic, $message, $retain) {
            $p = json_decode($message, true);
            return $topic === 'machines/TESTSN0001/cmd' && $p['action'] === 'CONFIG' && $p['max_pulses'] === 1000 && $retain === true;
        });

        (new SendMqttCommand('TESTSN0001', 'CONFIG'))->handle();
    }
}
