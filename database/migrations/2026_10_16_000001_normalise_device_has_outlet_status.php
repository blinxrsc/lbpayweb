<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * F13: device_has_outlet.status was created as enum('Online','Offline'),
 * but the code writes 'online', 'offline', 'rebooting' and 'faulty'.
 *  - PHP compares with === ('Online' !== 'online'), so the telemetry job
 *    saw a "status change" on EVERY heartbeat and wrote to RDS each time.
 *  - 'rebooting' / 'faulty' are not valid enum values (rejected in strict mode).
 * Fix: plain varchar, all values lowercase.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_has_outlet', function (Blueprint $table) {
            $table->string('status', 20)->default('offline')->change();
        });

        DB::table('device_has_outlet')->update(['status' => DB::raw('LOWER(status)')]);
    }

    public function down(): void
    {
        // Values stay lowercase; the column stays varchar (the enum could
        // not hold 'rebooting' / 'faulty' anyway).
    }
};
