<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use App\Models\DeviceTransaction;
use App\Models\DeviceOutlet;
use App\Models\Customer;
use App\Services\DeviceActivationService;

class FiuuCallbackTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_marks_transaction_paid_and_triggers_activation_on_success()
    {
        // Arrange: create customer + outlet + transaction
        $customer = Customer::factory()->create();
        $outlet   = DeviceOutlet::factory()->create();
        $amount = number_format(5.00, 2, '.', '');
        $orderId = 'ORD-M-' . now()->format('YmdHis') . '-' . $customer->id;
        $gw = PaymentGatewaySetting::where('status', 'active')->firstOrFail();
        $signature = md5($amount . $gw->merchant_id . $orderId . $gw->private_key);
        $transaction = DeviceTransaction::factory()->create([
            'customer_id'       => $customer->id,
            'device_outlet_id'  => $outlet->id,
            'amount'            => $amount,
            'currency'          => 'MYR',
            'status'            => DeviceTransaction::STATUS_INITIATED,
            'provider'          => DeviceTransaction::PROVIDER_FIUU,
            'order_id'          => $orderId,
            'request_payload'  => [
                'merchant_id' => $gw->merchant_id,
                'amount'      => $amount,
                'order_id'    => $orderId,
                'signature'   => $signature,
            ],
            
            'meta'        => [
                'type' => 'device',
                'device_outlet_id' => $r->device_outlet_id,
            ],
        ]);

        // Fake IoT activation service
        $this->mock(DeviceActivationService::class, function ($mock) use ($transaction) {
            $mock->shouldReceive('activate')
                ->once()
                ->with($transaction->deviceOutlet, 'low', 10)
                ->andReturn(true);
        });

        // Build fake Fiuu payload
        $payload = [
            'amount'    => $amount,
            'order_id'  => $orderId,
            'bill_name' => $customer->name,
            'bill_email' => $customer->email,
            'bill_desc' => 'Recharge',
            'currency'  => 'MYR',
            'returnurl' => $this->postJson(route('customer.payment.return')),
            'callbackurl' => $this->postJson(route('customer.payment.callback')),
            'status'   => 'success',
            'meta'     => ['type' => 'device'],
        ];
        $signature = md5($amount . $gw->merchant_id . $orderId . $gw->private_key);
        //$signature = hash_hmac('sha256', json_encode($payload), config('fiuu.secret_key'));
        $payload['vcode'] = $signature;

        // Act: call the callback route
        $response = $this->postJson(route('customer.payment.callback'), $payload);

        // Assert: response OK
        $response->assertStatus(200);

        // Assert: transaction updated
        $transaction->refresh();
        $this->assertEquals('activated', $transaction->status);
    }

    /** @test */
    public function it_marks_transaction_failed_on_failure()
    {
        $customer = Customer::factory()->create();
        $outlet   = DeviceOutlet::factory()->create();
        $transaction = DeviceTransaction::factory()->create([
            'customer_id'      => $customer->id,
            'device_outlet_id' => $outlet->id,
            'amount'           => 5.00,
            'mode'             => 'low',
            'duration'         => 10,
            'method'           => 'fiuu',
            'status'           => 'initiated',
            'gateway_ref'      => 'ORDER456',
        ]);

        $payload = [
            'order_id' => 'ORDER456',
            'status'   => 'failed',
            'meta'     => ['type' => 'device'],
        ];
        $signature = hash_hmac('sha256', json_encode($payload), config('fiuu.secret_key'));
        $payload['signature'] = $signature;

        $response = $this->postJson(route('customer.payment.callback'), $payload);

        $response->assertStatus(200);

        $transaction->refresh();
        $this->assertEquals('failed', $transaction->status);
    }
}