<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            // Business cap: enforced server-side (TechnicianDeviceController@start,
            // the admin dashboard's remoteStart()) — never sent to the ESP32, since
            // pricing/authorization is a backend concern, not a firmware one.
            $table->decimal('max_vend_price', 8, 2)->nullable()->after('coin_signal_width');

            // These three ARE pushed to the device in the CONFIG MQTT command —
            // see SendMqttCommand::handle() and the firmware's PulseConfig struct.
            $table->boolean('pulse_pull_up')->default(true)->after('max_vend_price');
            $table->boolean('coin_signal_idle_high')->default(true)->after('pulse_pull_up');
            $table->unsignedInteger('coin_signal_sensitivity')->default(200)->after('coin_signal_idle_high');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropColumn(['max_vend_price', 'pulse_pull_up', 'coin_signal_idle_high', 'coin_signal_sensitivity']);
        });
    }
};
