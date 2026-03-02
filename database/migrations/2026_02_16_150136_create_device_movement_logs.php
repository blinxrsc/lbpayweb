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
        Schema::create('device_movement_logs', function (Blueprint $table) {
            $table->id();
            $table->string('device_serial_number');
            $table->unsignedBigInteger('user_id'); // Who did it
            $table->unsignedBigInteger('outlet_id')->nullable();
            $table->string('action'); // 'Assigned', 'Unassigned', 'Marked Faulty', 'Repaired'
            $table->string('from_status');
            $table->string('to_status');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_movement_logs');
    }
};
