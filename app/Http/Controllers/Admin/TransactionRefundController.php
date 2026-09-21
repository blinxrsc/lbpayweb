<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendMqttCommand;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Models\Outlet;
use App\Models\TransactionRefund;
use App\Services\EwalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class TransactionRefundController extends Controller
{
    public function create(DeviceTransaction $transaction)
    {
        abort_if($transaction->refund, 409, 'This transaction has already been refunded/compensated.');

        $outlets = Outlet::with('deviceOutlets')->orderBy('outlet_name')->get();

        return view('admin.device-transactions.refund', compact('transaction', 'outlets'));
    }

    public function store(Request $request, DeviceTransaction $transaction, EwalletService $ewalletService)
    {
        abort_if($transaction->refund, 409, 'This transaction has already been refunded/compensated.');

        // Wallet refunds require a real customer account — a guest
        // checkout has nowhere to credit the money to.
        $availableMethods = $transaction->customer_id
            ? ['wallet', 'remote_start', 'external']
            : ['remote_start', 'external'];

        $validated = $request->validate([
            'method' => ['required', Rule::in($availableMethods)],
            'reason' => 'required|string|max:1000',
            'screenshot' => 'required|image|max:5120', // 5MB
            'compensation_device_outlet_id' => 'required_if:method,remote_start|nullable|exists:device_has_outlet,id',
        ]);

        $screenshotPath = $request->file('screenshot')->store('refund-evidence', 'public');

        $refundData = [
            'device_transaction_id' => $transaction->id,
            'resolved_by' => $request->user()->id,
            'method' => $validated['method'],
            'screenshot_path' => $screenshotPath,
            'reason' => $validated['reason'],
        ];

        if ($validated['method'] === 'wallet') {
            $ewalletService->adminAdjust(
                $transaction->customer_id,
                (float) $transaction->amount,
                'credit_adjust',
                0,
                'credit_bonus',
                $request->user()->id,
                $request->user()->email,
                "Refund for order {$transaction->order_id} — {$validated['reason']}"
            );
            $refundData['amount'] = $transaction->amount;
        }

        if ($validated['method'] === 'remote_start') {
            $targetOutlet = DeviceOutlet::findOrFail($validated['compensation_device_outlet_id']);

            if (!$targetOutlet->is_online) {
                return back()->withInput()->with('error', 'The selected machine is currently offline — pick another one, or use a different refund method.');
            }

            $device = $targetOutlet->device;
            if (!$device || (float) $device->pulse_price <= 0) {
                return back()->withInput()->with('error', 'The selected machine has no pulse_price configured — pick another one.');
            }

            $pulses = (int) ceil($transaction->amount / $device->pulse_price);

            SendMqttCommand::dispatch($targetOutlet->device_serial_number, 'REMOTE_START', [
                'op_id'  => (string) Str::uuid(),
                'type'   => 'compensation',
                'price'  => (float) $transaction->amount,
                'pulses' => $pulses,
            ], userId: $request->user()->id); // logged in Remote Start Log like any other admin-triggered start

            $refundData['compensation_device_outlet_id'] = $targetOutlet->id;
        }

        TransactionRefund::create($refundData);
        $transaction->update(['status' => DeviceTransaction::STATUS_REFUNDED]);

        return redirect()->route('admin.device-transactions.index')
            ->with('success', "Order {$transaction->order_id} marked as refunded.");
    }
}
