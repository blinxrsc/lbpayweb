<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EwalletAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id','credit_balance','bonus_balance'];
    protected $appends = ['total_balance'];

    protected $table = 'ewallet_accounts';

    //public function user(){ return $this->belongsTo(User::class, 'user_id'); }
    public function customer(){ return $this->belongsTo(Customer::class, 'user_id'); }
    public function transactions(){ return $this->hasMany(EwalletTransaction::class, 'ewallet_account_id'); }

    public function getTotalBalanceAttribute(){
        return bcadd($this->credit_balance, $this->bonus_balance, 2);
    }
}
