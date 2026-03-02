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
        Schema::create('stg_pmnt_gw', function (Blueprint $table) {
            $table->id();
            $table->string('merchant_id');
            $table->string('terminal_id');
            $table->string('app_id')->nullable();
            $table->string('client_id')->nullable();
            $table->text('secret_key')->nullable();
            $table->text('public_key')->nullable();
            $table->text('private_key')->nullable();
            $table->string('api_key')->nullable();
            $table->enum('status', ['active','disable'])->default('disable');
            $table->enum('sandbox', ['on','off'])->default('off');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stg_pmnt_gw');
    }
};
