<?php

namespace App\Console\Commands;

use App\Models\DeviceTransaction;
use App\Services\MachineStartService;
use App\Support\Telegram;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * F6: makes sure a PAID machine start actually reached the machine.
 *
 * MQTT commands are fire-and-forget (QoS 0). If the device was offline or
 * the message was lost, the payment used to stay 'activated' forever and
 * nobody knew the customer got nothing. Every minute this command:
 *
 *  1. Re-sends ONCE, with the same op_id, any start with no "received" ack
 *     after LBPAY_START_RETRY_AFTER seconds (default 90), if the machine is
 *     online AND runs firmware v1.5.6+. Starts that WERE received are never
 *     re-sent automatically — the device may already have pulsed part of them.
 *     Firmware <= v1.5.5 only remembers its LAST op_id, so a re-send that
 *     arrives after another start could credit the machine twice; those
 *     devices are never auto-retried (their stuck starts go to review).
 *  2. Moves to status 'review' any start still not 'completed' after
 *     LBPAY_START_REVIEW_AFTER seconds (default 600) and sends one Telegram
 *     digest, so staff can refund or re-send from Admin → Device Transactions.
 *
 * Only starts created by the new code (meta.started_at) are considered, so
 * old historical 'activated' rows are left alone.
 */
class ReconcileStarts extends Command
{
    protected $signature = 'app:reconcile-starts';

    protected $description = 'Retry unacknowledged paid starts once, flag stuck ones for review';

    public function handle(MachineStartService $starter): int
    {
        $retryAfter  = (int) config('lbpay.start_retry_after_seconds', 90);
        $reviewAfter = (int) config('lbpay.start_review_after_seconds', 600);

        $candidates = DeviceTransaction::with(['deviceOutlet.outlet', 'deviceOutlet.device'])
            ->where('status', DeviceTransaction::STATUS_ACTIVATED)
            ->where('updated_at', '>=', now()->subDay())
            ->orderBy('id')
            ->limit(1000)
            ->get()
            ->filter(fn ($t) => !empty($t->meta['started_at']));

        $retried = 0;
        $flagged = collect();

        foreach ($candidates as $txn) {
            $meta = $txn->meta;
            $age  = Carbon::parse($meta['started_at'])->diffInSeconds(now());

            if ($age >= $reviewAfter) {
                $flagged->push($this->flag($txn->id));
                continue;
            }

            $neverReceived = empty($meta['ack_received_at']);
            $notRetried    = (int) ($meta['retry_count'] ?? 0) === 0;

            if ($age >= $retryAfter && $neverReceived && $notRetried
                && $txn->deviceOutlet?->is_online
                && $this->supportsSafeRetry($txn->deviceOutlet->device?->version)) {
                $starter->resend($txn);
                $retried++;
            }
        }

        $flagged = $flagged->filter();

        if ($retried || $flagged->isNotEmpty()) {
            $this->info("Reconcile: re-sent {$retried}, flagged {$flagged->count()} for review.");
        }

        if ($flagged->isNotEmpty()) {
            $lines = ["⚠️ *Paid starts NOT confirmed by the machine* ({$flagged->count()})", ''];
            foreach ($flagged->take(30) as $t) {
                $where = ($t->deviceOutlet->outlet->outlet_name ?? '?') . ' #' . ($t->deviceOutlet->machine_num ?? '?');
                $lines[] = "• `{$t->order_id}` RM" . number_format((float) $t->amount, 2) . " — {$where} ({$t->meta['review_reason']})";
            }
            if ($flagged->count() > 30) {
                $lines[] = '…and ' . ($flagged->count() - 30) . ' more.';
            }
            $lines[] = '';
            $lines[] = 'Check Admin → Device Transactions (status: review): refund or re-send.';
            Telegram::send(implode("\n", $lines));
        }

        return self::SUCCESS;
    }

    /** Firmware v1.5.6+ remembers its last 8 op_ids, so a re-send can't double-credit. */
    private function supportsSafeRetry(?string $version): bool
    {
        return $version !== null && preg_match('/^\d+(\.\d+)*/', $version, $m) === 1
            && version_compare($m[0], '1.5.6', '>=');
    }

    /** Move one transaction to review under a row lock (an ack may be landing right now). */
    private function flag(int $id): ?DeviceTransaction
    {
        return DB::transaction(function () use ($id) {
            $txn = DeviceTransaction::with('deviceOutlet.outlet')->whereKey($id)->lockForUpdate()->first();
            if (!$txn || $txn->status !== DeviceTransaction::STATUS_ACTIVATED) {
                return null; // completed in the meantime
            }
            $meta = $txn->meta ?? [];
            $meta['review_reason'] = empty($meta['ack_received_at']) ? 'no_ack_from_device' : 'no_completion_ack';
            $txn->update(['status' => DeviceTransaction::STATUS_REVIEW, 'meta' => $meta]);
            return $txn;
        });
    }
}
