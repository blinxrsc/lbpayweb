<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MerchantConfig extends Model
{
    protected $fillable = [
        'company_name', 
        'reg_no', 
        'logo_path', 
        'address',
        'city',
        'state',
        'country',
        'website',
        'support_number',
        'email',
        'toll_free'
    ];
}

