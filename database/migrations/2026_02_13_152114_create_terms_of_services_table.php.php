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
        Schema::create('terms_of_services', function (Blueprint $table) {
            $table->id();
            $table->string('title'); // e.g., "Privacy Notice"
            $table->string('slug')->unique(); // e.g., "privacy-notice"
            $table->longText('content'); 
            $table->integer('version')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('customer_term_agreements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('term_id')->constrained('terms_of_services')->onDelete('cascade');
            $table->integer('version_agreed'); // Records WHICH version they agreed to
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('terms_of_services');
        Schema::dropIfExists('customer_term_agreements');
    }
};
