<?php

namespace App\Services;

use App\Models\DeviceTransaction;
use App\Models\Outlet;
use Carbon\Carbon;

class DeviceRevenueReportService
{
    /**
     * Transaction statuses that represent money actually collected.
     * 'initiated' (payment not yet confirmed) and 'failed' are excluded.
     */
    private const SUCCESSFUL_STATUSES = ['paid', 'activated', 'completed'];

    public function summary(?int $outletId, Carbon $from, Carbon $to): array
    {
        $base = DeviceTransaction::whereIn('status', self::SUCCESSFUL_STATUSES)
            ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()]);

        if ($outletId) {
            $base->whereHas('deviceOutlet', fn ($q) => $q->where('outlet_id', $outletId));
        }

        // Mobile = paid directly via the payment gateway (Fiuu). Member =
        // paid from wallet/balance. Any other provider value (future
        // gateways) still counts toward total, just not toward either
        // named bucket — kept out of mobile/member so those two numbers
        // stay meaningful rather than silently absorbing unrelated methods.
        $mobileRevenue = (clone $base)->where('provider', DeviceTransaction::PROVIDER_FIUU)->sum('amount');
        $memberRevenue = (clone $base)->where('provider', 'ewallet')->sum('amount');
        $totalRevenue  = (clone $base)->sum('amount');

        $mobileCount = (clone $base)->where('provider', DeviceTransaction::PROVIDER_FIUU)->count();
        $memberCount = (clone $base)->where('provider', 'ewallet')->count();
        $totalCount  = (clone $base)->count();

        return [
            'mobile_revenue' => (float) $mobileRevenue,
            'mobile_count'   => $mobileCount,
            'member_revenue' => (float) $memberRevenue,
            'member_count'   => $memberCount,
            'total_revenue'  => (float) $totalRevenue,
            'total_count'    => $totalCount,
        ];
    }

    /**
     * Per-outlet breakdown for the same filters, used for the detail table
     * beneath the summary cards.
     */
    public function byOutlet(?int $outletId, Carbon $from, Carbon $to)
    {
        $outlets = $outletId ? Outlet::where('id', $outletId)->get() : Outlet::orderBy('outlet_name')->get();

        return $outlets->map(function (Outlet $outlet) use ($from, $to) {
            $base = DeviceTransaction::whereIn('status', self::SUCCESSFUL_STATUSES)
                ->whereBetween('created_at', [$from->copy()->startOfDay(), $to->copy()->endOfDay()])
                ->whereHas('deviceOutlet', fn ($q) => $q->where('outlet_id', $outlet->id));

            return [
                'outlet' => $outlet,
                'mobile_revenue' => (float) (clone $base)->where('provider', DeviceTransaction::PROVIDER_FIUU)->sum('amount'),
                'member_revenue' => (float) (clone $base)->where('provider', 'ewallet')->sum('amount'),
                'total_revenue'  => (float) (clone $base)->sum('amount'),
                'total_count'    => (clone $base)->count(),
            ];
        })->filter(fn ($row) => $row['total_count'] > 0 || $outletId)->values();
    }
}
