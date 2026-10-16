<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Redis;

class DeviceOutlet extends Model
{
    protected $table = 'device_has_outlet';

    public const STATUS_ONLINE    = 'online';
    public const STATUS_OFFLINE   = 'offline';
    public const STATUS_REBOOTING = 'rebooting';

    /** Statuses driven by telemetry. Anything else (e.g. 'faulty') is a
     *  manual/maintenance flag and is never overwritten by a heartbeat. */
    public const PRESENCE_STATUSES = [self::STATUS_ONLINE, self::STATUS_OFFLINE, self::STATUS_REBOOTING];

    public static function presenceKey(string $serial): string
    {
        return "machine:status:{$serial}";
    }

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

    /** Always store status lowercase (F13: the old enum used 'Online'). */
    protected function status(): Attribute
    {
        return Attribute::make(set: fn ($value) => $value === null ? null : strtolower((string) $value));
    }

    public function getIsOnlineAttribute()
    {
        // F6: the key must say 'online'. It used to be enough for the key to
        // EXIST — but an offline (LWT) message also wrote it, so a device
        // that had just dropped looked online for 10 minutes and payments
        // were accepted for a machine that could not receive the pulse.
        return Redis::get(self::presenceKey($this->device_serial_number)) === self::STATUS_ONLINE;
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
