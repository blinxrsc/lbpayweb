<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Manager extends Model
{
    protected $fillable = ['name', 'email', 'phone', 'ssm'];

    public function outlets()
    {
        return $this->hasMany(Outlet::class);
    }
}
