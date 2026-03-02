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
        Schema::create('merchant_configs', function (Blueprint $table) {
            $table->id();
            $table->string('company_name');
            $table->string('reg_no')->nullable();
            $table->string('logo_path')->nullable();
            $table->text('address');
            $table->string('city');
            $table->string('state');
            $table->string('country');
            $table->string('website')->nullable();
            $table->string('support_number');
            $table->string('email');
            $table->string('toll_free')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('merchant_configs');
    }
};
