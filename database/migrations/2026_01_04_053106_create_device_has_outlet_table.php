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
        Schema::create('device_has_outlet', function (Blueprint $table) {
            $table->id();
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('cascade');
            $table->string('machine_num');
            $table->string('machine_name');
            $table->enum('machine_type', ['Washer','Dryer','Combo','Token Changer','Vending']);
            $table->string('device_serial_number');
            $table->foreign('device_serial_number')->references('serial_number')->on('devices')->onDelete('cascade');
            $table->enum('status', ['Online','Offline'])->default('Offline');
            $table->boolean('availability')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_has_outlet');
    }
};
