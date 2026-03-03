<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HealthStatus extends Model
{
    // You MUST add this line
    protected $fillable = ['id', 'data'];

    // Ensure it casts the JSON automatically
    protected $casts = [
        'data' => 'array',
    ];
}
