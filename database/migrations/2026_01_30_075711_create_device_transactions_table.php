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
        Schema::create('device_transactions', function (Blueprint $table) {
            $table->id();
            // Fiuu-required fields
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 8, 2);
            $table->string('currency')->default('MYR');
            $table->string('status')->default('initiated'); // Lifecycle status: initiated, paid, activated, completed, failed, refunded
            $table->string('provider')->nullable(); // e.g. fiuu / ewallet
            $table->string('provider_txn_id')->nullable()->unique(); // Gateway reference (for Fiuu)
            $table->string('order_id')->nullable()->unique();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            // Device-specific fields
            $table->foreignId('device_outlet_id')->constrained('device_has_outlet')->onDelete('cascade');
            $table->json('meta')->nullable(); // Optional metadata (IoT payload, session ID, etc.)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_transactions');
    }
};
