<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentGatewayTransaction extends Model
{
    use HasFactory;

    // Table name (optional if following conventions)
    protected $table = 'payment_gateway_transactions';

    // Possible statuses for lifecycle tracking
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PAID      = 'paid';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED  = 'refunded';

    // Provider identifier
    public const PROVIDER_FIUU = 'fiuu';

    protected $fillable = [
        'user_id',            // Customer ID
        'amount',
        'currency',
        'status',
        'provider',
        'provider_txn_id',    // unique from gateway for idempotency
        'order_id',           // your generated reference
        'request_payload',    // JSON
        'response_payload',   // JSON
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'request_payload'  => 'array',
        'response_payload' => 'array',
    ];

    // -----------------------------
    // Relationships
    // -----------------------------

    // Assuming customers are stored in App\Models\Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }

    // Optional: if you later want to link to wallet transactions via reference (order_id)
    public function ewalletTransactions()
    {
        return $this->hasMany(EwalletTransaction::class, 'reference', 'order_id');
    }

    // -----------------------------
    // Scopes
    // -----------------------------

    public function scopePaid($query)
    {
        return $query->where('status', self::STATUS_PAID);
    }

    public function scopeInitiated($query)
    {
        return $query->where('status', self::STATUS_INITIATED);
    }

    public function scopeProvider($query, string $provider)
    {
        return $query->where('provider', $provider);
    }

    public function scopeByOrderId($query, string $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    // -----------------------------
    // Helpers
    // -----------------------------

    public function markPaid(?string $providerTxnId = null, ?array $response = null): void
    {
        $this->update([
            'status'           => self::STATUS_PAID,
            'provider_txn_id'  => $providerTxnId ?? $this->provider_txn_id,
            'response_payload' => $response ?? $this->response_payload,
        ]);
    }

    public function markFailed(?array $response = null): void
    {
        $this->update([
            'status'           => self::STATUS_FAILED,
            'response_payload' => $response ?? $this->response_payload,
        ]);
    }

    public function isPaid(): bool
    {
        return $this->status === self::STATUS_PAID;
    }

}
