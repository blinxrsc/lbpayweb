<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailServer extends Model
{
    protected $table = 'stg_mail_server';

    protected $fillable = [
        'host','port','username','password','encryption'
    ];
}
