<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('transaction_refunds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_transaction_id')->constrained('device_transactions')->cascadeOnDelete();
            $table->foreignId('resolved_by')->constrained('users'); // the support/admin staff who processed it
            // 'wallet': credited to the customer's ewallet balance.
            // 'remote_start': compensated with a pulse to another machine, no charge.
            // 'external': handled outside the system (cash, bank transfer, etc.) — recorded for audit only.
            $table->string('method');
            $table->decimal('amount', 8, 2)->nullable(); // credited amount, when method = wallet
            // When method = remote_start: which machine received the compensation pulse,
            // and that pulse's own audit trail lives in remote_start_logs (same as any
            // other admin-triggered start) — not duplicated here.
            $table->foreignId('compensation_device_outlet_id')->nullable()
                ->constrained('device_has_outlet')->nullOnDelete();
            $table->string('screenshot_path')->nullable(); // customer's proof-of-payment screenshot
            $table->text('reason');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('transaction_refunds');
    }
};
