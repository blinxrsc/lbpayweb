<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemoteStartLog extends Model
{
    protected $fillable = [
        'user_id', 
        'customer_id',
        'source',
        'device_serial_number',
        'cycle_type',
        'equivalent_price',
        'reason'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
