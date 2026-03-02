<?php

namespace App\Services;

use App\Models\EwalletAccount;
use App\Models\EwalletTransaction;
use App\Models\TopupPackage;
use App\Models\PaymentGatewayTransaction;
use App\Models\PaymentGatewaySetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EwalletService
{
    /**
     * Handle customer top-up via payment gateway.
     * Credits wallet and applies package bonus if applicable.
     */
    public function creditTopupWithPackage(
        int $userId,
        float $amount,
        string $providerTxnId,
        string $orderId,
        string $provider,
        array $meta = []
    ): void {
        DB::transaction(function () use ($userId, $amount, $providerTxnId, $orderId, $provider, $meta) {
            Log::channel('fiuu')->info("Starting topup wallet for user {$userId}, amount {$amount}");

            // Lock gateway record for idempotency
            $gateway = PaymentGatewayTransaction::where('order_id', $orderId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($gateway->status === 'paid' &&
                EwalletTransaction::where('reference', $orderId)->exists()) {
                return; // already processed
            }

            // Mark gateway as paid
            $gateway->update([
                'status'           => 'paid',
                'provider_txn_id'  => $providerTxnId,
                'provider'         => $provider,
                'response_payload' => $meta['response_payload'] ?? null,
            ]);

            // Ensure wallet exists
            $account = EwalletAccount::firstOrCreate(
                ['user_id' => $userId],
                ['credit_balance' => 0, 'bonus_balance' => 0]
            );

            // Base transaction
            $txn = EwalletTransaction::create([
                'ewallet_account_id' => $account->id,
                'user_id'            => $userId,
                'transaction_type'   => 'recharge',
                'amount'             => $amount,
                'bonus_amount'       => 0,
                'currency'           => 'MYR',
                'reference'          => $orderId,
                'transaction_time'   => now(),
                'remaining_balance'  => $account->credit_balance + $account->bonus_balance + $amount, // snapshot
                'meta'               => [
                    'provider_txn_id' => $providerTxnId,
                    'source'          => 'gateway',
                    'recharge_amount' => $amount,
                ],
            ]);

            $account->increment('credit_balance', $amount);
            Log::channel('fiuu')->info("Crediting wallet for user {$userId}, recharge amount {$amount}");

            // Apply package bonus if matched
            $package = TopupPackage::where('is_active', true)
                ->where('topup_amount', $amount)
                ->first();

            if ($package && $package->bonus_amount > 0) {
                // Update transaction with bonus + new remaining balance
                $txn->update([
                    'bonus_amount' => $package->bonus_amount,
                    'remaining_balance' => $account->credit_balance + $account->bonus_balance,
                    'meta' => array_merge($txn->meta ?? [], [
                        'bonus_amount' => $package->bonus_amount,
                    ]),
                ]);

                $account->increment('bonus_balance', $package->bonus_amount);
                Log::channel('fiuu')->info("Crediting bonus wallet for user {$userId}, bonus amount {$amount}");
            }
        });
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
     * Debit spend from customer's wallet.
     * Prioritises credit balance, then bonus balance.
     * Records ledger entries and updates balances atomically.
     */
    public function debitSpend(
        int $userId,
        float $amount,
        string $orderId,
        ?string $note = null
    ): bool {
        return DB::transaction(function () use ($userId, $amount, $orderId, $note) {
            $account = EwalletAccount::where('user_id', $userId)->lockForUpdate()->first();

            if (!$account) {
                throw new \Exception("Wallet not found for user {$userId}");
            }

            // Check total balance
            $total = bcadd($account->credit_balance, $account->bonus_balance, 2);
            if (bccomp($total, $amount, 2) < 0) {
                // insufficient funds
                return false;
            }

            $remaining = $amount;

            // Deduct from credit first
            if ($account->credit_balance > 0) {
                $deduct = min($account->credit_balance, $remaining);
                if ($deduct > 0) {
                    EwalletTransaction::create([
                        'ewallet_account_id' => $account->id,
                        'user_id'            => $userId,
                        'type'               => 'debit_spend',
                        'amount'             => $deduct,
                        'currency'           => 'MYR',
                        'reference'          => $orderId,
                        'meta'               => ['source' => 'credit', 'note' => $note],
                    ]);
                    $account->decrement('credit_balance', $deduct);
                    $remaining -= $deduct;
                }
            }

            // Deduct remainder from bonus
            if ($remaining > 0 && $account->bonus_balance > 0) {
                $deduct = min($account->bonus_balance, $remaining);
                if ($deduct > 0) {
                    EwalletTransaction::create([
                        'ewallet_account_id' => $account->id,
                        'user_id'            => $userId,
                        'type'               => 'debit_spend',
                        'amount'             => $deduct,
                        'currency'           => 'MYR',
                        'reference'          => $orderId,
                        'meta'               => ['source' => 'bonus', 'note' => $note],
                    ]);
                    $account->decrement('bonus_balance', $deduct);
                    $remaining -= $deduct;
                }
            }

            return true;
        });
    }
}