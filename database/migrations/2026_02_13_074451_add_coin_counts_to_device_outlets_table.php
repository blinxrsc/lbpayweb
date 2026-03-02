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
            // bigInteger is safer for lifetime totals
            $table->bigInteger('current_coins')->default(0)->after('status');
            $table->bigInteger('lifetime_coins')->default(0)->after('current_coins');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('device_has_outlet', function (Blueprint $table) {
            $table->dropColumn(['current_coins', 'lifetime_coins']);
        });
    }
};
