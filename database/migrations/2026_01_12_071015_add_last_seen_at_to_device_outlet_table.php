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
        Schema::table('device_has_outlet', function (Blueprint $table) {
            //$table->enum('status', ['online', 'offline'])->default('offline');
            $table->timestamp('last_seen_at')->nullable();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_outlet', function (Blueprint $table) {
            //$table->dropColumn(['status', 'last_seen_at']);
            $table->dropColumn(['last_seen_at']);
        });
    }
};
