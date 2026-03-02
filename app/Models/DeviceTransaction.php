<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceTransaction extends Model
{
    // Possible statuses for lifecycle tracking
    public const STATUS_INITIATED = 'initiated';
    public const STATUS_PAID      = 'paid';
    public const STATUS_FAILED    = 'failed';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REFUNDED  = 'refunded';
    public const STATUS_ACTIVATED  = 'activated';
    // Provider identifier
    public const PROVIDER_FIUU = 'fiuu';

    protected $fillable = [
        // Fiuu-required fields
        'customer_id',        // same as user_id in PaymentGatewayTransaction
        'amount',
        'currency',
        'status',
        'provider',
        'provider_txn_id',
        'order_id',
        'request_payload',
        'response_payload',

        // Device-specific fields
        'device_outlet_id',
        'meta',
    ];

    protected $casts = [
        'amount'           => 'decimal:2',
        'request_payload'  => 'array',
        'response_payload' => 'array',
        'meta'             => 'array',
    ];

    // Link to Customer
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    // Link to DeviceOutlet
    public function deviceOutlet()
    {
        return $this->belongsTo(DeviceOutlet::class, 'device_outlet_id');
    }
    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'completed' => 'bg-green-200 text-green-800',
            'failed'    => 'bg-red-200 text-red-800',
            'paid'      => 'bg-blue-200 text-blue-800',
            default     => 'bg-yellow-200 text-yellow-800',
        };
    }
}
