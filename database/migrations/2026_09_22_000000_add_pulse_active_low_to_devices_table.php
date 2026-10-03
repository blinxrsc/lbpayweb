<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The firmware's PulseConfig struct has always supported
     * pulseActiveLow (see CONFIG handler: `doc["pulse_active_low"]`),
     * but no backend field ever sent it — every device has been running
     * on the firmware's hardcoded default (idle HIGH, pulse LOW) with no
     * way to flip it without a manual MQTT message. wh Münzprüfer's own
     * coin selectors expose exactly this as a "Polarity Inverted" setting
     * because it genuinely varies by installation — this closes that gap.
     */
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->boolean('pulse_active_low')->default(true)->after('coin_signal_sensitivity');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn('pulse_active_low');
        });
    }
};
