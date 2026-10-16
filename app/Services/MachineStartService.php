<?php

namespace App\Services;

use App\Jobs\SendMqttCommand;
use App\Models\DeviceTransaction;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Sends the coin-equivalent REMOTE_START for a paid DeviceTransaction.
 *
 * Previously lived as PaymentController::dispatchMachineStart(). Two changes:
 *
 *  1. The op_id is created ONCE per transaction and stored in meta.op_id.
 *     A repeated call (retry, duplicate callback that slipped through)
 *     re-sends the SAME op_id, so the ESP32's duplicate check
 *     ("duplicate_ignored") can protect against a double credit. Before,
 *     every call minted a fresh UUID, so the firmware could never tell.
 *
 *  2. The MQTT job is dispatched with afterCommit(). When this is called
 *     inside a DB transaction (wallet debit, callback settlement), the
 *     machine is only pulsed once the payment rows are durably committed,
 *     never for a payment that was then rolled back.
 */
class MachineStartService
{
    public function dispatchForTransaction(DeviceTransaction $transaction): bool
    {
        $deviceOutlet = $transaction->deviceOutlet;
        $device = $deviceOutlet?->device;

        if (!$device || (float) $device->pulse_price <= 0) {
            Log::error('Cannot start machine: no pulse_price configured', ['transaction_id' => $transaction->id]);
            return false;
        }

        $pulses = $device->pulsesFor((float) $transaction->amount);
        $meta   = $transaction->meta ?? [];

        // Last line of defence: never send a start the controller will
        // reject (it would ack "rejected: too_many_pulses" and the customer
        // gets nothing). PaymentController already refuses such prices
        // before charging; this catches admin/legacy paths.
        if ($pulses > \App\Models\Device::maxPulsesPerStart()) {
            Log::error('Cannot start machine: pulse count over controller limit', [
                'transaction_id' => $transaction->id, 'pulses' => $pulses,
                'max' => \App\Models\Device::maxPulsesPerStart(),
            ]);
            $transaction->update([
                'status' => DeviceTransaction::STATUS_REVIEW,
                'meta'   => array_merge($meta, ['pulses' => $pulses, 'review_reason' => 'pulses_over_limit']),
            ]);
            return false;
        }
        $opId   = $meta['op_id'] ?? (string) Str::uuid();

        SendMqttCommand::dispatch($device->serial_number, 'REMOTE_START', [
            'op_id'  => $opId,
            'type'   => $meta['mode'] ?? 'device',
            'price'  => (float) $transaction->amount,
            'pulses' => $pulses,
        ], userId: null, customerId: $transaction->customer_id)->afterCommit();

        $transaction->update([
            'status' => DeviceTransaction::STATUS_ACTIVATED,
            'meta'   => array_merge($meta, ['op_id' => $opId, 'pulses' => $pulses]),
        ]);

        return true;
    }
}
