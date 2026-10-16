<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * ewallet_transactions.user_id stores a CUSTOMER id (same as
 * ewallet_accounts.user_id, which already references customers), but the
 * original migration declared ->constrained() — i.e. a foreign key to the
 * staff `users` table. On MySQL that rejects ledger rows for any customer
 * whose id has no matching staff user (top-up / spend fails with
 * "Cannot add or update a child row").
 *
 * Safe to run on a DB where this was already fixed by hand: it only acts
 * when the FK still points at `users`, and it refuses to proceed if some
 * ledger rows reference ids that don't exist in `customers`.
 */
return new class extends Migration
{
    public function up(): void
    {
        $fk = collect(Schema::getForeignKeys('ewallet_transactions'))
            ->first(fn ($k) => $k['columns'] === ['user_id']);

        if ($fk && $fk['foreign_table'] !== 'users') {
            return; // already points somewhere else (e.g. customers) — nothing to do
        }

        $orphans = DB::table('ewallet_transactions as t')
            ->leftJoin('customers as c', 'c.id', '=', 't.user_id')
            ->whereNull('c.id')
            ->count();

        if ($orphans > 0) {
            throw new RuntimeException(
                "{$orphans} ewallet_transactions rows have a user_id that is not a customer id. "
                . "Inspect with: SELECT t.* FROM ewallet_transactions t LEFT JOIN customers c ON c.id=t.user_id WHERE c.id IS NULL;"
            );
        }

        Schema::table('ewallet_transactions', function (Blueprint $t) use ($fk) {
            if ($fk) {
                // MySQL: drop by the real constraint name. SQLite (tests)
                // can only drop by column list.
                DB::getDriverName() === 'sqlite'
                    ? $t->dropForeign(['user_id'])
                    : $t->dropForeign($fk['name']);
            }
            $t->foreign('user_id')->references('id')->on('customers')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        // Intentionally not restoring the incorrect FK to `users`.
    }
};
