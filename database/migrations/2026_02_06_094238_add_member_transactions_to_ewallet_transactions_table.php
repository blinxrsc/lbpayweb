<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ewallet_transactions', function (Blueprint $t) {
            $t->timestamp('transaction_time')->useCurrent()->after('user_id');
            $t->string('outlet_name')->nullable()->after('transaction_time');
            $t->string('customer_name')->nullable()->after('outlet_name');
            $t->string('machine_num')->nullable()->after('customer_name');
            $t->string('device_serial_number')->nullable()->after('machine_num');
            $t->decimal('bonus_amount', 12, 2)->default(0)->after('amount');
            $t->decimal('deduct_amount', 12, 2)->default(0)->after('bonus_amount');
            $t->decimal('deduct_bonus', 12, 2)->default(0)->after('deduct_amount');
            $t->decimal('remaining_balance', 12, 2)->default(0)->after('deduct_bonus');
            $t->enum('transaction_type', ['deduction','recharge','admin_topup','referral'])->nullable()->after('remaining_balance');
            $t->string('admin_email')->nullable()->after('transaction_type');
            $t->string('referral')->nullable()->after('admin_email');
        });
        // Backfill defaults for existing rows
        DB::table('ewallet_transactions')->update([
            'transaction_time' => DB::raw('created_at'),
            'remaining_balance' => 0,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ewallet_transactions', function (Blueprint $t) {
            $t->dropColumn([
                'transaction_time',
                'outlet_name',
                'customer_name',
                'machine_num',
                'device_serial_number',
                'bonus_amount',
                'deduct_amount',
                'deduct_bonus',
                'remaining_balance',
                'transaction_type',
                'admin_email',
                'referral',
            ]);
        });
    }
};
