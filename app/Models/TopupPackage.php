<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TopupPackage extends Model
{
    use HasFactory; 
    
    protected $table = 'topup_packages'; 
    
    protected $fillable = [ 'topup_amount', 'bonus_amount', 'is_active', ]; 
    protected $casts = [ 
        'topup_amount' => 'decimal:2', 
        'bonus_amount' => 'decimal:2', 
        'is_active' => 'boolean', 
    ];
}
