<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class TermsOfService extends Model
{
    protected $fillable = ['title', 'slug', 'content', 'version', 'is_active'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($term) => $term->slug = Str::slug($term->title));
    }
}