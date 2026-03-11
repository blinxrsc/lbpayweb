<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

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

    public function getIsOnlineAttribute()
    {
        // Check Redis first. If the key exists, the machine is online.
        // This avoids hitting the DB disk.
        return Redis::exists("machine:status:{$this->device_serial_number}");
    }

    public function getLastSeenAtAttribute()
    {
        $timestamp = Redis::hget('machine_heartbeats', $this->device_serial_number);
        return $timestamp ? \Carbon\Carbon::createFromTimestamp($timestamp) : $this->attributes['last_seen_at'];
    }

    public function getLastRebootAttribute()
    {
        $cached = Redis::hget('machine_last_reboot', $this->device_serial_number);
        return $cached ? \Carbon\Carbon::createFromTimestamp($cached) : $this->attributes['last_reboot_at'];
    }

}
