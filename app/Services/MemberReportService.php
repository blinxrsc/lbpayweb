<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class MemberReportService
{
    public function weeklyReport()
    {
        $data = DB::table('ewallet_transactions')
            ->selectRaw("
                YEARWEEK(transaction_time, 1) AS period,
                SUM(amount) AS total_transaction_amount,
                SUM(deduct_amount) AS total_usage,
                SUM(bonus_amount) AS bonus_credited,
                SUM(deduct_bonus) AS bonus_usage,
                COUNT(DISTINCT user_id) AS active_members
            ")
            ->groupByRaw("YEARWEEK(transaction_time, 1)")
            ->orderByDesc('period')
            ->get();

        return $data->map(function ($row) 
        {
            $totalMembers = DB::table('customers')->count();
            $newRegistrations = DB::table('customers')
                ->whereRaw("YEARWEEK(created_at, 1) = ?", [$row->period])
                ->count();

            $row->active_member_percentage = $totalMembers > 0
                ? round(($row->active_members / $totalMembers) * 100, 2)
                : 0;
            $row->new_registrations = $newRegistrations;
            $row->total_registered = $totalMembers;
            return $row;
        });
    }

    public function monthlyReport()
    {
        $data = DB::table('ewallet_transactions')
            ->selectRaw("
                DATE_FORMAT(transaction_time, '%Y-%m') AS period,
                SUM(amount) AS total_transaction_amount,
                SUM(deduct_amount) AS total_usage,
                SUM(bonus_amount) AS bonus_credited,
                SUM(deduct_bonus) AS bonus_usage,
                COUNT(DISTINCT user_id) AS active_members
            ")
            ->groupByRaw("DATE_FORMAT(transaction_time, '%Y-%m')")
            ->orderByDesc('period')
            ->get();

        return $data->map(function ($row) {
            $totalMembers = DB::table('customers')->count();
            $newRegistrations = DB::table('customers')
                ->whereRaw("DATE_FORMAT(created_at, '%Y-%m') = ?", [$row->period])
                ->count();

            $row->active_member_percentage = $totalMembers > 0
                ? round(($row->active_members / $totalMembers) * 100, 2)
                : 0;
            $row->new_registrations = $newRegistrations;
            $row->total_registered = $totalMembers;
            return $row;
        });
    }
}