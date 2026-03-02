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
        Schema::create('payment_gateway_transactions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('user_id')->constrained('customers')->cascadeOnDelete();
            $t->decimal('amount', 12, 2);
            $t->string('currency', 3)->default('MYR');
            $t->enum('status', ['initiated','paid','failed','cancelled','refunded']);
            $t->string('provider')->default('fiuu'); // e.g., senangpay, ipay88, toyibpay
            $t->string('provider_txn_id')->nullable()->unique(); // for idempotency
            $t->string('order_id')->unique(); // your generated order ref
            $t->json('request_payload')->nullable();
            $t->json('response_payload')->nullable();
            $t->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_gateway_transactions');
    }
};
