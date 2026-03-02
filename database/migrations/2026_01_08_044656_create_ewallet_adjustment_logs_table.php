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
    { Schema::create('ewallet_adjustment_logs', function (Blueprint $table) { 
        $table->id(); 
        $table->unsignedBigInteger('user_id'); 
        // Customer affected 
        $table->unsignedBigInteger('admin_id'); 
        // Admin who made adjustment 
        $table->decimal('credit_change', 12, 2)->default(0); 
        $table->decimal('bonus_change', 12, 2)->default(0); 
        $table->string('note')->nullable(); 
        $table->timestamps(); 
        $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade'); 
        $table->foreign('admin_id')->references('id')->on('users')->onDelete('cascade'); }); 
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ewallet_adjustment_logs');
    }
};
