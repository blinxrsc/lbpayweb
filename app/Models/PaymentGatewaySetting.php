<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PaymentGatewaySetting extends Model
{
    protected $table = 'stg_pmnt_gw';

    protected $fillable = [
        'merchant_id','terminal_id','app_id','client_id',
        'secret_key','public_key','private_key','api_key',
        'status','sandbox'
    ];
}
