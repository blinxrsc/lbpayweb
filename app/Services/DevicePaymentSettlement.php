<?php

namespace App\Services;

use App\Models\DeviceTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Applies a VERIFIED Fiuu result to a machine-payment DeviceTransaction.
 *
 * Fiuu tells us the result of one payment several times: the browser
 * return, the server callback, the notification URL, plus callback retries
 * until it sees CBTOKEN:MPSTATOK. The old callback handler had no
 * idempotency, so every "00" delivery started the machine again (free
 * cycles). This class makes every delivery after the first a no-op:
 *
 *   1. Row lock: SELECT ... FOR UPDATE on the transaction, so two
 *      deliveries arriving at the same moment are processed one after
 *      the other, never in parallel.
 *   2. State check: if the row already left 'initiated' as a success,
 *      return 'already_processed' and touch nothing.
 *   3. Amount check: the paid amount must equal the amount we stored
 *      when the order was created.
 *   4. Only then mark paid and queue the machine start (after commit).
 *
 * The caller MUST have verified the skey signature before calling this.
 */
class DevicePaymentSettlement
{
    public const OK          = 'paid';
    public const ALREADY     = 'already_processed';
    public const FAILED      = 'failed';
    public const PENDING     = 'pending';
    public const NOT_FOUND   = 'not_found';
    public const MISMATCH    = 'amount_mismatch';

    /** States that mean "money was already accepted for this order". */
    private const SETTLED_STATES = [
        DeviceTransaction::STATUS_PAID,
        DeviceTransaction::STATUS_ACTIVATED,
        DeviceTransaction::STATUS_COMPLETED,
        DeviceTransaction::STATUS_REFUNDED,
        DeviceTransaction::STATUS_REVIEW,
    ];

    public function __construct(private MachineStartService $starter) {}

    /**
     * @return array{0: string, 1: ?DeviceTransaction}  [result, transaction]
     */
    public function settle(array $in, string $source): array
    {
        $orderId = (string) ($in['orderid'] ?? '');
        $status  = (string) ($in['status'] ?? '');

        return DB::transaction(function () use ($in, $orderId, $status, $source) {
            $txn = DeviceTransaction::where('order_id', $orderId)->lockForUpdate()->first();

            if (!$txn) {
                Log::channel('fiuu')->error("[$source] Device transaction not found", ['order_id' => $orderId]);
                return [self::NOT_FOUND, null];
            }

            // ── Idempotency: anything already settled is left untouched ──
            if (in_array($txn->status, self::SETTLED_STATES, true)) {
                Log::channel('fiuu')->info("[$source] Duplicate delivery ignored", [
                    'order_id' => $orderId, 'current_status' => $txn->status, 'incoming_status' => $status,
                ]);
                return [self::ALREADY, $txn];
            }

            if ($status === '00') {
                // ── Amount/currency must match what we asked for ──
                if (!Money::equals($in['amount'] ?? null, $txn->amount)
                    || strtoupper((string) ($in['currency'] ?? 'MYR')) !== strtoupper((string) $txn->currency)) {
                    $txn->update([
                        'status'           => DeviceTransaction::STATUS_REVIEW,
                        'provider_txn_id'  => $in['tranID'] ?? $txn->provider_txn_id,
                        'response_payload' => $in,
                    ]);
                    Log::channel('fiuu')->critical("[$source] Amount/currency mismatch — machine NOT started", [
                        'order_id' => $orderId, 'expected' => (string) $txn->amount, 'received' => $in['amount'] ?? null,
                    ]);
                    return [self::MISMATCH, $txn];
                }

                $txn->update([
                    'status'           => DeviceTransaction::STATUS_PAID,
                    'provider_txn_id'  => $in['tranID'] ?? $txn->provider_txn_id,
                    'response_payload' => $in,
                ]);
                Log::channel('fiuu')->info("[$source] Transaction marked paid", ['id' => $txn->id]);

                $this->starter->dispatchForTransaction($txn);

                return [self::OK, $txn];
            }

            if ($status === '22') {
                // Pending (e.g. some FPX banks). Keep waiting for the final result.
                $txn->update(['response_payload' => $in]);
                return [self::PENDING, $txn];
            }

            // '11' or anything else = failure. Only an 'initiated' row can
            // fail; a late failure notice never overwrites a success.
            if ($txn->status === DeviceTransaction::STATUS_INITIATED) {
                $txn->update([
                    'status'           => DeviceTransaction::STATUS_FAILED,
                    'provider_txn_id'  => $in['tranID'] ?? $txn->provider_txn_id,
                    'response_payload' => $in,
                ]);
            }
            Log::channel('fiuu')->warning("[$source] Transaction failed", ['id' => $txn->id, 'status' => $status]);

            return [self::FAILED, $txn];
        });
    }
}
