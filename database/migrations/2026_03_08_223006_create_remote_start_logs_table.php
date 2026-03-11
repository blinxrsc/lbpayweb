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
        Schema::create('remote_start_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // The Admin who clicked
            $table->string('device_serial_number');
            $table->string('cycle_type'); // Cold, Hot, etc.
            $table->decimal('equivalent_price', 8, 2);
            $table->string('reason')->nullable(); // Optional: "Customer lost coin"
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('remote_start_logs');
    }
};
