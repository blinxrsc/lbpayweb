<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MailServerLog extends Model
{
    protected $fillable = [
        'mail_server_id','recipient_email','status','message'
    ];

    public function mailServer()
    {
        return $this->belongsTo(MailServer::class, 'mail_server_id');
    }
}
