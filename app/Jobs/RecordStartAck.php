<?php

namespace App\Jobs;

use App\Models\DeviceTransaction;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

/**
 * F6: records the device's REMOTE_START ack on the paid DeviceTransaction,
 * so "paid but the machine never got it" becomes visible instead of every
 * paid start sitting at 'activated' forever.
 *
 *   received                 -> meta.ack_received_at (device has the command)
 *   completed                -> status 'completed'
 *   duplicate_ignored        -> status 'completed' (device already ran this op_id)
 *   busy / rejected / failed -> status 'review' + meta.ack_error (needs a human:
 *                               refund or re-send from Admin → Device Transactions)
 */
class RecordStartAck implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;

    public function __construct(public int $transactionId, public array $ack) {}

    public function handle(): void
    {
        DB::transaction(function () {
            $txn = DeviceTransaction::whereKey($this->transactionId)->lockForUpdate()->first();
            if (!$txn) {
                return;
            }

            $status = (string) ($this->ack['status'] ?? '');
            $meta   = $txn->meta ?? [];

            // Only the op this transaction is waiting for counts.
            if (($meta['op_id'] ?? null) !== ($this->ack['op_id'] ?? null)) {
                return;
            }

            $meta['acks'][] = array_filter([
                'status' => $status,
                'error'  => $this->ack['error'] ?? null,
                'sent'   => $this->ack['sent'] ?? null,
                'at'     => now()->toIso8601String(),
            ], fn ($v) => $v !== null);

            $newStatus = $txn->status;

            switch ($status) {
                case 'received':
                    $meta['ack_received_at'] ??= now()->toIso8601String();
                    break;

                case 'completed':
                case 'duplicate_ignored':
                    if (in_array($txn->status, [DeviceTransaction::STATUS_ACTIVATED, DeviceTransaction::STATUS_REVIEW], true)) {
                        $newStatus = DeviceTransaction::STATUS_COMPLETED;
                        $meta['completed_at'] = now()->toIso8601String();
                        if (isset($this->ack['sent'])) {
                            $meta['pulses_sent'] = (int) $this->ack['sent'];
                        }
                        unset($meta['review_reason']);
                    }
                    break;

                case 'busy':
                case 'rejected':
                case 'failed':
                    if ($txn->status === DeviceTransaction::STATUS_ACTIVATED) {
                        $newStatus = DeviceTransaction::STATUS_REVIEW;
                        $meta['review_reason'] = 'device_' . $status;
                        $meta['ack_error'] = $this->ack['error'] ?? $status;
                    }
                    break;
            }

            $txn->update(['status' => $newStatus, 'meta' => $meta]);
        });
    }
}
