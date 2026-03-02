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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('phone_country_code')->nullable();
            $table->string('phone_number')->nullable();
            $table->string('username')->unique();
            $table->string('password');
            $table->date('birthday')->nullable();
            $table->string('tags')->nullable();
            $table->string('referral_code', 6)->unique();
            $table->enum('sign_in', ['web','google','facebook'])->default('web');
            $table->enum('status', ['active','inactive'])->default('active');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer');
    }
};
