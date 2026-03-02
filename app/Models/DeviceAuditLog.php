<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceAuditLog extends Model
{
    protected $fillable = ['device_id','user_id','field','old_value','new_value'];

    public function device() {
        return $this->belongsTo(Device::class);
    }

    public function user() {
        return $this->belongsTo(User::class);
    }
    public function rollback() {
        $this->device->update([$this->field => $this->old_value]);
    }

}
