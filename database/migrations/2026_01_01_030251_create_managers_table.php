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
        Schema::create('managers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique()->nullable();
            $table->string('phone')->nullable();
            $table->string('ssm')->nullable();
            $table->timestamps();
        });

        // Update outlets table to reference managers
        //Schema::table('outlets', function (Blueprint $table) {
            //$table->foreignId('manager_id')->nullable()->constrained('managers')->onDelete('set null');
            //$table->foreignId('manager_id')->constrained()->onDelete('cascade');
        //});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('managers');
    }
};
