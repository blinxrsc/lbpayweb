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
        Schema::create('mail_server_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mail_server_id')->constrained('stg_mail_server')->onDelete('cascade');
            $table->string('recipient_email');
            $table->string('status'); // success or failed
            $table->text('message')->nullable(); // error message or success note
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mail_server_logs');
    }
};
