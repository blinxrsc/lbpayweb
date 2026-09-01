<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * This pivot table records which backend `users` (admin panel / outlet
     * manager logins) are allowed to access which `outlets`. A user with the
     * `outlets.view-all` permission (typically the admin role) bypasses this
     * table entirely and can see every outlet — see User::canAccessAllOutlets().
     */
    public function up(): void
    {
        Schema::create('outlet_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('outlet_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'outlet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outlet_user');
    }
};
