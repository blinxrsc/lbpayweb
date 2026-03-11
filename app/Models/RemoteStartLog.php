<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RemoteStartLog extends Model
{
    protected $fillable = [
        'user_id', 
        'device_serial_number',
        'cycle_type',
        'equivalent_price',
        'reason'
    ];
}
