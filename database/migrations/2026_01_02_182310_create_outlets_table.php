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
        Schema::create('outlets', function (Blueprint $table) {
            $table->id();
            $table->string('outlet_name');
            $table->string('machine_number')->nullable();
            $table->string('business_hours')->nullable();
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            $table->string('address')->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->foreignId('manager_id')->constrained()->onDelete('cascade');
            $table->string('phone')->nullable();
            $table->foreignId('brand_id')->constrained()->onDelete('cascade');
            $table->enum('status', ['active', 'closed'])->default('active');
            $table->enum('type', ['own', 'jv', 'franchise', 'alacart'])->default('own');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlets');
    }
};
