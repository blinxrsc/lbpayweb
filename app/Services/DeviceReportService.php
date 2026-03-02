<?php

namespace App\Services;

use App\Models\Device;
use App\Models\DeviceMovementLog;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class DeviceReportService
{
    public function maintenanceReport($month = null, $year = null)
    {
        $month = $month ?? now()->month;
        $year = $year ?? now()->year;

        $logs = DeviceMovementLog::with(['user', 'device'])
            ->whereMonth('created_at', $month)
            ->whereYear('created_at', $year)
            ->get();

        // Calculate last month for comparison
        $lastMonth = $month == 1 ? 12 : $month - 1;
        $lastYear = $month == 1 ? $year - 1 : $year;

        $previousCount = DeviceMovementLog::where('action', 'Marked Faulty')
            ->whereMonth('created_at', $lastMonth)
            ->whereYear('created_at', $lastYear)
            ->count();

        // Aggregate statistics
        return [
            'month' => $month,
            'year' => $year,
            'period' => date("F Y", mktime(0, 0, 0, $month, 10, $year)),
            'total_faulty' => $logs->where('action', 'Marked Faulty')->count(),
            'total_repaired' => $logs->where('action', 'Repair Completed')->count(),
            'trend' => $previousCount > 0 ? (($stats['total_faulty'] - $previousCount) / $previousCount) * 100 : 0,

            // Grouping by model to find hardware reliability issues
            'faults_by_model' => Device::whereIn('serial_number', $logs->where('action', 'Marked Faulty')->pluck('device_serial_number'))
                ->get()
                ->groupBy('model')
                ->map->count(),

            // Technician performance tracking
            'staff_activity' => $logs->groupBy('user_id')->map(function($group) {
                return [
                    'name' => $group->first()->user->name ?? 'System',
                    'count' => $group->count()
                ];
            })->values()
        ];
    }

    public function generatePdfReport($month, $year)
    {
        $stats = $this->maintenanceReport($month, $year);
        
        // We pass the data to a dedicated PDF blade view
        $pdf = Pdf::loadView('reports.maintenance_pdf', compact('stats'));
        
        return $pdf;
    }
}