<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EwalletTransaction extends Model
{
    use HasFactory;

    protected $table = 'ewallet_transactions';

    protected $fillable = [
        'ewallet_account_id',
        'user_id',
        'transaction_time',
        'outlet_name',
        'customer_name',
        'machine_num',
        'device_serial_number',
        'type',
        'amount',
        'bonus_amount',
        'deduct_amount',
        'deduct_bonus',
        'remaining_balance',
        'transaction_type',
        'admin_email',
        'referral',
        'currency',
        'reference',
        'meta',
    ];

    protected $casts = [
        'transaction_time' => 'datetime',
        'amount' => 'decimal:2',
        'bonus_amount' => 'decimal:2',
        'deduct_amount' => 'decimal:2',
        'remaining_balance' => 'decimal:2',
        'meta'   => 'array',
    ];

    // -----------------------------
    // Relationships
    // -----------------------------

    public function account()
    {
        return $this->belongsTo(EwalletAccount::class, 'ewallet_account_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'user_id');
    }
    public function ewalletAccount()
    {
        return $this->belongsTo(EwalletTransaction::class, 'user_id');
    }

    // -----------------------------
    // Helpers
    // -----------------------------

    public function isCredit(): bool
    {
        return in_array($this->type, ['recharge', 'admin_topup']);
    }

    public function isBonus(): bool
    {
        //return in_array($this->type, ['referral']);
        return $this->type === 'referral';
    }

    public function isDebit(): bool
    {
        return $this->type === 'debit_spend';
    }

}
