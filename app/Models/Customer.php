<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use \App\Models\EwalletAccount;

//class Customer extends Model
class Customer extends Authenticatable implements MustVerifyEmail
{
    use HasFactory, Notifiable;

    protected $table = 'customers'; // ensure this matches your migration

    protected $fillable = [
        'name','email','phone_country_code','phone_number',
        'username','password','birthday','tags',
        'referral_code','sign_in','status'
    ];

    protected $hidden = ['password','remember_token'];
    // -----------------------------
    // Relationships
    // -----------------------------

    public function ewalletAccount()
    {
        return $this->hasOne(EwalletAccount::class, 'user_id');
    }

    public function ewalletTransactions()
    {
        return $this->hasMany(EwalletTransaction::class, 'user_id');
    }

    public function paymentGatewayTransactions()
    {
        return $this->hasMany(PaymentGatewayTransaction::class, 'user_id');
    }

    public function termAgreements()
    {
        return $this->hasMany(CustomerTermAgreement::class);
    }
    // -----------------------------
    // Helpers
    // -----------------------------

    public function getWalletBalanceAttribute()
    {
        return $this->ewalletAccount
            ? $this->ewalletAccount->total_balance
            : 0;
    }

    public function deviceTransactions()
    {
        return $this->hasMany(DeviceTransaction::class);
    }
    /**
     * Checks if the customer has accepted the latest version of all active terms
     */
    public function hasAcceptedLatestTerms(): bool
    {
        $activeTerms = TermsOfService::where('is_active', true)->get();
        
        foreach ($activeTerms as $term) {
            $exists = $this->termAgreements()
                ->where('term_id', $term->id)
                ->where('version_agreed', $term->version)
                ->exists();
            
            if (!$exists) return false;
        }
        return true;
    }                                      
}


