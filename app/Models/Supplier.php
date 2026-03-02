<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    protected $fillable = ['supplier_name', 'email', 'phone', 'address'];

    public function devices()
    {
        return $this->hasMany(Device::class);
    }

}
