<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Database-level backstop against double credits / double charges:
 * one ledger row per (order reference, transaction type).
 *
 * If the old duplicate-callback bug already double-credited some top-ups,
 * those duplicate rows exist and the index can't be created. Instead of
 * failing with a cryptic SQL error, this migration stops and prints the
 * affected order references so they can be reviewed (and the extra credit
 * reversed through Admin → E-Wallet → Adjust) before re-running it.
 *
 * Find them yourself with:
 *   SELECT reference, transaction_type, COUNT(*) n, SUM(amount) total
 *   FROM ewallet_transactions
 *   WHERE reference IS NOT NULL AND transaction_type IS NOT NULL
 *   GROUP BY reference, transaction_type HAVING n > 1;
 */
return new class extends Migration
{
    public function up(): void
    {
        $dupes = DB::table('ewallet_transactions')
            ->select('reference', 'transaction_type', DB::raw('COUNT(*) as n'))
            ->whereNotNull('reference')
            // Rows with transaction_type NULL are legacy ledger rows from
            // before Feb 2026 (that column didn't exist yet). A unique index
            // treats NULLs as distinct, so they can never block it and are
            // not double credits — e.g. 14 old admin adjustments that all
            // used reference "admin:1". Only real typed duplicates count.
            ->whereNotNull('transaction_type')
            ->groupBy('reference', 'transaction_type')
            ->havingRaw('COUNT(*) > 1')
            ->limit(50)
            ->get();

        if ($dupes->isNotEmpty()) {
            $list = $dupes->map(fn ($d) => "{$d->reference} ({$d->transaction_type} x{$d->n})")->implode(', ');
            throw new RuntimeException(
                "Duplicate ewallet ledger rows found — likely past double credits. Review and fix these first, "
                . "then re-run migrate: {$list}"
            );
        }

        Schema::table('ewallet_transactions', function (Blueprint $t) {
            $t->unique(['reference', 'transaction_type'], 'ewallet_txn_reference_type_unique');
        });
    }

    public function down(): void
    {
        Schema::table('ewallet_transactions', function (Blueprint $t) {
            $t->dropUnique('ewallet_txn_reference_type_unique');
        });
    }
};
