<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerTermAgreement extends Model
{
    protected $fillable = [
        'customer_id',
        'term_id',
        'version_agreed',
    ];

    // Relationship back to the term
    public function term()
    {
        return $this->belongsTo(TermsOfService::class, 'term_id');
    }

    // Relationship back to the customer
    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
