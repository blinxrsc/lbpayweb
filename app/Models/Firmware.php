<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Firmware extends Model
{
    protected $table = 'firmware';

    protected $fillable = [
        'version',
        'file_path',
        'notes',
    ];
}
