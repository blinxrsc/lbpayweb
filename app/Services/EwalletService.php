<?php

namespace App\Services;

use App\Models\EwalletAccount;
use App\Models\EwalletTransaction;
use App\Models\TopupPackage;
use App\Models\PaymentGatewayTransaction;
use App\Models\PaymentGatewaySetting;
use App\Models\DeviceOutlet;
use App\Models\DeviceTransaction;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EwalletService
{
    public const TOPUP_OK        = 'paid';
    public const TOPUP_ALREADY   = 'already_processed';
    public const TOPUP_FAILED    = 'failed';
    public const TOPUP_PENDING   = 'pending';
    public const TOPUP_NOT_FOUND = 'not_found';
    public const TOPUP_MISMATCH  = 'amount_mismatch';

    /**
     * Apply a VERIFIED Fiuu top-up result (callback, notification or return).
     *
     * Replaces creditTopupWithPackage(). The old flow had GatewayController
     * set status='initiated' BEFORE calling the service, which reset the
     * very flag the service's duplicate check relied on, so every repeated
     * "00" callback credited the wallet again. Now:
     *   1. lock the gateway row (FOR UPDATE),
     *   2. if it is already 'paid' -> no-op,
     *   3. check the amount matches the order,
     *   4. mark paid + credit + bonus, all in one DB transaction.
     * A unique index on ewallet_transactions(reference, transaction_type)
     * backs this up at database level (see migration 2026_10_14_000001).
     *
     * @return array{0: string, 1: ?PaymentGatewayTransaction}
     */
    public function settleTopup(array $in, string $source): array
    {
        $orderId = (string) ($in['orderid'] ?? '');
        $status  = (string) ($in['status'] ?? '');

        return DB::transaction(function () use ($in, $orderId, $status, $source) {
            $gateway = PaymentGatewayTransaction::where('order_id', $orderId)->lockForUpdate()->first();

            if (!$gateway) {
                Log::channel('fiuu')->error("[$source] Top-up order not found", ['order_id' => $orderId]);
                return [self::TOPUP_NOT_FOUND, null];
            }

            if ($gateway->status === PaymentGatewayTransaction::STATUS_PAID) {
                Log::channel('fiuu')->info("[$source] Duplicate top-up delivery ignored", ['order_id' => $orderId]);
                return [self::TOPUP_ALREADY, $gateway];
            }

            if ($status === '22') {
                $gateway->update(['response_payload' => $in]);
                return [self::TOPUP_PENDING, $gateway];
            }

            if ($status !== '00') {
                if ($gateway->status === PaymentGatewayTransaction::STATUS_INITIATED) {
                    $gateway->update([
                        'status'           => PaymentGatewayTransaction::STATUS_FAILED,
                        'provider_txn_id'  => $in['tranID'] ?? $gateway->provider_txn_id,
                        'response_payload' => $in,
                    ]);
                }
                Log::channel('fiuu')->warning("[$source] Top-up failed", ['order_id' => $orderId, 'status' => $status]);
                return [self::TOPUP_FAILED, $gateway];
            }

            if (!Money::equals($in['amount'] ?? null, $gateway->amount)) {
                // Leave it 'initiated' so it can be reviewed; never credit a
                // different amount than the order was created for.
                $gateway->update(['response_payload' => $in]);
                Log::channel('fiuu')->critical("[$source] Top-up amount mismatch — wallet NOT credited", [
                    'order_id' => $orderId, 'expected' => (string) $gateway->amount, 'received' => $in['amount'] ?? null,
                ]);
                return [self::TOPUP_MISMATCH, $gateway];
            }

            $gateway->update([
                'status'           => PaymentGatewayTransaction::STATUS_PAID,
                'provider_txn_id'  => $in['tranID'] ?? $gateway->provider_txn_id,
                'provider'         => PaymentGatewayTransaction::PROVIDER_FIUU,
                'response_payload' => $in,
            ]);

            $this->creditRecharge($gateway, $in['tranID'] ?? null);

            return [self::TOPUP_OK, $gateway];
        });
    }

    /**
     * Credit the wallet for a paid gateway row (+ package bonus). Must run
     * inside the caller's DB transaction with the gateway row locked.
     */
    private function creditRecharge(PaymentGatewayTransaction $gateway, ?string $providerTxnId): void
    {
        $userId    = $gateway->user_id;
        $amountSen = Money::toSen($gateway->amount);

        EwalletAccount::firstOrCreate(['user_id' => $userId], ['credit_balance' => 0, 'bonus_balance' => 0]);
        $account = EwalletAccount::where('user_id', $userId)->lockForUpdate()->firstOrFail();

        $package  = TopupPackage::where('is_active', true)->where('topup_amount', Money::fromSen($amountSen))->first();
        $bonusSen = $package ? Money::toSen($package->bonus_amount) : 0;

        $newCredit = Money::toSen($account->credit_balance) + $amountSen;
        $newBonus  = Money::toSen($account->bonus_balance) + $bonusSen;

        EwalletTransaction::create([
            'ewallet_account_id' => $account->id,
            'user_id'            => $userId,
            'type'               => 'credit_topup',
            'transaction_type'   => 'recharge',
            'amount'             => Money::fromSen($amountSen),
            'bonus_amount'       => Money::fromSen($bonusSen),
            'currency'           => 'MYR',
            'reference'          => $gateway->order_id,
            'transaction_time'   => now(),
            'customer_name'      => $account->customer->name ?? null,
            'remaining_balance'  => Money::fromSen($newCredit + $newBonus),
            'meta'               => [
                'provider_txn_id' => $providerTxnId,
                'source'          => 'gateway',
                'recharge_amount' => Money::fromSen($amountSen),
                'bonus_amount'    => Money::fromSen($bonusSen),
                'package_id'      => $package?->id,
            ],
        ]);

        $account->update([
            'credit_balance' => Money::fromSen($newCredit),
            'bonus_balance'  => Money::fromSen($newBonus),
        ]);

        Log::channel('fiuu')->info('Wallet credited', [
            'user_id' => $userId, 'order_id' => $gateway->order_id,
            'amount' => Money::fromSen($amountSen), 'bonus' => Money::fromSen($bonusSen),
        ]);
    }

    /**
     * Admin adjustment (credit or bonus).
     */
    public function adminAdjust(
        int $userId,
        float $amount,
        string $type,
        float $bonusAmount,
        string $bonusType,
        int $adminId,
        string $adminEmail,
        ?string $reason = null,
    ): void {
        DB::transaction(function () use ($userId, $amount, $type, $bonusAmount, $bonusType, $adminId, $adminEmail, $reason) {
            // Lock account for consistency
            $account = EwalletAccount::where('user_id', $userId)
                ->lockForUpdate()
                ->firstOrFail();
            // Apply adjustment
            switch ($type) {
                case 'credit_adjust':
                    $account->increment('credit_balance', $amount);
                    break;
                case 'debit_adjust':
                    $account->decrement('credit_balance', $amount);
                    break;
            }

            switch ($bonusType) {
                case 'credit_bonus':
                    $account->increment('bonus_balance', $bonusAmount);
                    break;
                case 'debit_bonus':
                    $account->decrement('bonus_balance', $bonusAmount);
                    break;
            }
            // Snapshot after adjustment
            $remainingBalance = $account->credit_balance + $account->bonus_balance;

            // Record transaction
            EwalletTransaction::create([
                'ewallet_account_id' => $account->id,
                'user_id'            => $userId,
                'transaction_type'   => 'admin_topup',
                'amount'             => $amount,
                'bonus_amount'       => $bonusAmount,
                'transaction_time'   => now(),
                'currency'           => 'MYR',
                'admin_email'        => $adminEmail,
                'customer_name'      => $account->customer->name ?? null,
                'reference'          => $adminId .'-'.uniqid(),
                'remaining_balance'  => $remainingBalance,
                'meta'               => [
                    'reason' => $reason,
                    'source' => 'admin',
                    'admin_id' => $adminId,
                ],
            ]);
        });
    }
    /**
     * Pay for a machine from the e-wallet, atomically.
     *
     * Replaces both PaymentController's inline balance check/decrement and
     * the unused debitSpend() (which wrote a non-existent `type` value and
     * no deduction columns). Fixes:
     *   - Race: the account row is locked (FOR UPDATE) before reading the
     *     balance, so two taps at the same moment can't both pass the
     *     check and overspend / go negative.
     *   - Ledger: writes one ewallet_transactions row
     *     (transaction_type='deduction') with deduct_amount / deduct_bonus,
     *     which the member reports and admin ledger already read.
     *   - Bonus: credit balance is used first, then bonus balance (the
     *     intent stated in the old debitSpend()).
     *   - The DeviceTransaction is created in the SAME DB transaction, so
     *     the wallet is never charged without a matching order row.
     *
     * Returns the paid DeviceTransaction, or null if balance is too low.
     * The caller starts the machine (inside the same DB transaction, the
     * MQTT job is dispatched afterCommit).
     */
    public function spendForDevice(int $customerId, DeviceOutlet $deviceOutlet, float $amount, string $mode, string $orderId): ?DeviceTransaction
    {
        $priceSen = Money::toSen($amount);

        $account = EwalletAccount::where('user_id', $customerId)->lockForUpdate()->first();
        if (!$account) {
            return null;
        }

        $creditSen = Money::toSen($account->credit_balance);
        $bonusSen  = Money::toSen($account->bonus_balance);

        if ($creditSen + $bonusSen < $priceSen) {
            return null;
        }

        $fromCredit = min($creditSen, $priceSen);
        $fromBonus  = $priceSen - $fromCredit;

        $newCredit = $creditSen - $fromCredit;
        $newBonus  = $bonusSen - $fromBonus;

        $account->update([
            'credit_balance' => Money::fromSen($newCredit),
            'bonus_balance'  => Money::fromSen($newBonus),
        ]);

        $deviceOutlet->loadMissing('outlet');

        EwalletTransaction::create([
            'ewallet_account_id'   => $account->id,
            'user_id'              => $customerId,
            'type'                 => 'debit_spend',
            'transaction_type'     => 'deduction',
            'amount'               => Money::fromSen($priceSen),
            'deduct_amount'        => Money::fromSen($fromCredit),
            'deduct_bonus'         => Money::fromSen($fromBonus),
            'remaining_balance'    => Money::fromSen($newCredit + $newBonus),
            'currency'             => 'MYR',
            'reference'            => $orderId,
            'transaction_time'     => now(),
            'customer_name'        => $account->customer->name ?? null,
            'outlet_name'          => $deviceOutlet->outlet->outlet_name ?? null,
            'machine_num'          => $deviceOutlet->machine_num,
            'device_serial_number' => $deviceOutlet->device_serial_number,
            'meta'                 => ['source' => 'device_payment', 'mode' => $mode],
        ]);

        return DeviceTransaction::create([
            'customer_id'      => $customerId,
            'device_outlet_id' => $deviceOutlet->id,
            'amount'           => Money::fromSen($priceSen),
            'currency'         => 'MYR',
            'status'           => DeviceTransaction::STATUS_PAID,
            'provider'         => 'ewallet',
            'order_id'         => $orderId,
            'meta'             => [
                'type'             => 'device',
                'mode'             => $mode,
                'device_outlet_id' => $deviceOutlet->id,
                'from_credit'      => Money::fromSen($fromCredit),
                'from_bonus'       => Money::fromSen($fromBonus),
            ],
        ]);
    }
}