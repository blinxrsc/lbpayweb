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
        Schema::create('ewallet_accounts', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->unique()->constrained('customers')->cascadeOnDelete();
            $t->decimal('credit_balance', 12, 2)->default(0);
            $t->decimal('bonus_balance', 12, 2)->default(0);
            $t->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ewallet_accounts');
    }
};
