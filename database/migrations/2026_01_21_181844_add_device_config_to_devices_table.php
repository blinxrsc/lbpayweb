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
        Schema::table('devices', function (Blueprint $table) {
            $table->decimal('washer_cold_price', 8, 2)->default(0);
            $table->decimal('washer_warm_price', 8, 2)->default(0);
            $table->decimal('washer_hot_price', 8, 2)->default(0);
            $table->decimal('dryer_low_price', 8, 2)->default(0);
            $table->decimal('dryer_med_price', 8, 2)->default(0);
            $table->decimal('dryer_hi_price', 8, 2)->default(0);
            $table->decimal('pulse_price', 8, 2)->default(0);
            $table->integer('pulse_add_min')->default(0);
            $table->integer('pulse_width')->default(0);
            $table->integer('pulse_delay')->default(0);
            $table->integer('coin_signal_width')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn([
                'washer_cold_price','washer_warm_price','washer_hot_price',
                'dryer_low_price','dryer_med_price','dryer_hi_price',
                'pulse_price','pulse_add_min','pulse_width','pulse_delay','coin_signal_width'
            ]);
        });
    }
};
