<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Device extends Model
{
    protected $fillable = [
        'serial_number',
        'model',
        'version',
        'order_number',
        'purchase_date',
        'supplier_id',
        'purchase_cost',
        'washer_cold_price','washer_warm_price','washer_hot_price',
        'dryer_low_price','dryer_med_price','dryer_hi_price',
        'pulse_price','pulse_add_min','pulse_width','pulse_delay','coin_signal_width',
        'status',
        'outlet_id',
        'ota_status',
        'ota_error',
        'last_payload',
    ];

    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
    // Each device can be linked to one outlets via DeviceOutlet
    public function deviceOutlets()
    {
        //return $this->hasMany(DeviceOutlet::class, 'device_serial_number', 'serial_number');
        return $this->hasOne(DeviceOutlet::class, 'device_serial_number', 'serial_number');
    }
    public function auditLogs() {
        return $this->hasMany(DeviceAuditLog::class);
    }
    public function movementLogs()
    {
        // Use the foreign key column name in your table if it's not 'device_id'
        return $this->hasMany(DeviceMovementLog::class, 'device_serial_number', 'serial_number');
    }
}

