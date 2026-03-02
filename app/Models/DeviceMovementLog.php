<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceMovementLog extends Model
{
    protected $fillable = [
        'device_serial_number', 
        'action', 
        'user_id',
        'outlet_id', 
        'from_status', 
        'to_status', 
        'notes'
    ];
    public function user() {
        return $this->belongsTo(User::class);
    }
    public function device() {
        return $this->belongsTo(Device::class, 'device_serial_number', 'serial_number');
    }

    public function outlet() {
        return $this->belongsTo(Outlet::class);
    }
}
