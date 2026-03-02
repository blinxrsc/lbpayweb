<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceOutlet extends Model
{
    protected $table = 'device_has_outlet';

    protected $fillable = [
        'outlet_id',
        'machine_num',
        'machine_name',
        'machine_type',
        'device_serial_number',
        'status',
        'availability',
        'current_coins',   // Add this
        'lifetime_coins',  // Add this
        'last_seen_at',
    ];

    public function outlet()
    {
        return $this->belongsTo(Outlet::class, 'outlet_id', 'id');
    }

    public function device()
    {
        return $this->belongsTo(Device::class, 'device_serial_number', 'serial_number');
    }

    public function transactions()
    {
        return $this->hasMany(DeviceTransaction::class);
    }
}
