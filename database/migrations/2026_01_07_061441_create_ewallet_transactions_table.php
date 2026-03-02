<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ewallet_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('ewallet_account_id')->constrained()->cascadeOnDelete();
            $t->foreignId('user_id')->constrained()->cascadeOnDelete(); // owner for convenience
            $t->enum('type', ['credit_topup','bonus_award','admin_credit','admin_bonus','debit_spend','reversal']);
            $t->decimal('amount', 12, 2); // positive values; debit_spend recorded as positive amount with type
            $t->string('currency', 3)->default('MYR');
            $t->string('reference')->nullable(); // gateway txn id, admin note, order id
            $t->json('meta')->nullable(); // package_id, source, reason
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ewallet_transactions');
    }
};
