<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * customer_id was a required foreign key, which meant a truly
     * anonymous (not-logged-in) "Mobile Payment" from the QR page could
     * never actually create a device_transactions row — initiateQRPayment()
     * was passing customer_id => 0, which doesn't exist as a customer and
     * would fail this FK constraint outright. Guest contact details
     * (name/email/phone, needed for the payment gateway's billing fields)
     * are now captured into `meta` instead of requiring a real Customer
     * record.
     */
    public function up(): void
    {
        Schema::table('device_transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('device_transactions', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable()->change();
        });

        Schema::table('device_transactions', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_transactions', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });

        Schema::table('device_transactions', function (Blueprint $table) {
            $table->foreignId('customer_id')->nullable(false)->change();
        });

        Schema::table('device_transactions', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }
};
