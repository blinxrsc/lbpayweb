<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Outlet extends Model
{
    protected $fillable = [
        'outlet_name', 'machine_number', 'business_hours', 'country',
        'province', 'city', 'address', 'longitude', 'latitude',
        'manager', 'phone', 'brand_id', 'status_id', 'type_id', 'manager_id'
    ];
    // Relationship to TypeStatus
    public function status()
    {
        return $this->belongsTo(TypeStatus::class, 'status_id');
    }
    // Relationship to TypeOutlet
    public function type()
    {
        return $this->belongsTo(TypeOutlet::class, 'type_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    public function manager()
    {
        return $this->belongsTo(Manager::class);
    }
    // Each outlet has many DeviceOutlet records
    public function deviceOutlets()
    {
        return $this->hasMany(DeviceOutlet::class, 'outlet_id', 'id');
    }
    // Shortcut: get actual Device models via DeviceOutlet
    public function devices()
    {
        return $this->hasManyThrough(
            Device::class,        // final model
            DeviceOutlet::class,  // intermediate model
            'outlet_id',          // FK on DeviceOutlet
            'serial_number',      // FK on Device
            'id',                 // local key on Outlet
            'device_serial_number'// local key on DeviceOutlet
        );
    }

    public function deviceTransactions()
    {
        return $this->hasManyThrough(DeviceTransaction::class, DeviceOutlet::class);
    }

}
