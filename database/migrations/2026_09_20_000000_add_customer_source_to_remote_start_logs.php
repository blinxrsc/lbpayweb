<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * user_id was a required FK to `users` (admins only). Now that
     * REMOTE_START can also be triggered by a paying customer or a guest
     * checkout (no admin involved at all), every one of those would have
     * failed this constraint outright. Adding customer_id (nullable, no
     * admin equivalent) and a `source` column so the admin "Remote Start"
     * log can tell the three cases apart.
     */
    public function up(): void
    {
        Schema::table('remote_start_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('remote_start_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->change();
            $table->foreignId('customer_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            // 'admin' (dashboard/technician app), 'customer_payment' (paid, logged in),
            // 'guest_payment' (paid, no account)
            $table->string('source')->default('admin')->after('customer_id');
        });

        Schema::table('remote_start_logs', function (Blueprint $table) {
            $table->foreign('user_id')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('remote_start_logs', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->dropForeign(['customer_id']);
            $table->dropColumn(['customer_id', 'source']);
        });

        Schema::table('remote_start_logs', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }
};
