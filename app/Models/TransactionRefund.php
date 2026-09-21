<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransactionRefund extends Model
{
    protected $fillable = [
        'device_transaction_id',
        'resolved_by',
        'method',
        'amount',
        'compensation_device_outlet_id',
        'screenshot_path',
        'reason',
    ];

    public function transaction()
    {
        return $this->belongsTo(DeviceTransaction::class, 'device_transaction_id');
    }

    public function resolvedBy()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }

    public function compensationDeviceOutlet()
    {
        return $this->belongsTo(DeviceOutlet::class, 'compensation_device_outlet_id');
    }
}
