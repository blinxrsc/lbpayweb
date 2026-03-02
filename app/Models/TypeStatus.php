<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TypeStatus extends Model
{
    protected $table = 'type_statuses'; 
    protected $fillable = ['name'];
}
