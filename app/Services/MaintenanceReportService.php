<?php

namespace App\Services;

use Carbon\Carbon;
use App\Models\Device;
use App\Models\DeviceMovementLog;
use Illuminate\Support\Facades\DB;

class MaintenanceReportService
{
    public function getMaintenanceStats($month = null, $year = null)
    {
        $month = $month ?? date('m');
        $year = $year ?? date('Y');

        // 1. Basic Stats for the current month
        $stats = [
            'month' => $month,
            'year' => $year,
            'period' => Carbon::create($year, $month)->format('F Y'),
            'total_faulty' => DeviceMovementLog::where('action', 'Marked Faulty')
                ->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
            'total_repaired' => DeviceMovementLog::where('action', 'Repair Completed')
                ->whereMonth('created_at', $month)->whereYear('created_at', $year)->count(),
        ];

        // 2. MTTR Calculation (The logic you wrote)
        $logs = DeviceMovementLog::whereIn('action', ['Marked Faulty', 'Repair Completed'])
            // Filter by the selected month/year
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->orderBy('device_serial_number')
            ->orderBy('created_at')
            ->get()
            ->groupBy('device_serial_number');

        $totalMinutes = 0;
        $repairCount = 0;

        foreach ($logs as $deviceLogs) {
            for ($i = 0; $i < count($deviceLogs) - 1; $i++) {
                if ($deviceLogs[$i]->action === 'Marked Faulty' && 
                    $deviceLogs[$i+1]->action === 'Repair Completed') {
                    
                    $totalMinutes += $deviceLogs[$i]->created_at->diffInMinutes($deviceLogs[$i+1]->created_at);
                    $repairCount++;
                }
            }
        }

        $stats['avg_repair_days'] = $repairCount > 0 ? round(($totalMinutes / $repairCount) / 1440, 1) : 0;

        // 3. Faults by Model (For the Bar Chart)
        $stats['faults_by_model'] = Device::join('device_movement_logs', 'devices.serial_number', '=', 'device_movement_logs.device_serial_number')
            ->where('device_movement_logs.action', 'Marked Faulty')
            ->groupBy('devices.model')
            ->select('devices.model', DB::raw('count(*) as count'))
            ->pluck('count', 'model')
            ->toArray();

        // If you used movement_logs or movementLogs, it must match the function name
        // 4. "Lemon" Devices (Top 5 offenders with repair status)
        // 4. "Lemon" Devices with Counts and Last Repair Note
        $stats['top_faulty_devices'] = Device::withCount([
            'movementLogs as faults_count' => function($q) {
                $q->where('action', 'Marked Faulty');
            },
            'movementLogs as repairs_count' => function($q) {
                $q->where('action', 'Repair Completed');
            }
        ])
        // Add a subquery to get the latest repair note
        ->addSelect(['last_repair_note' => DeviceMovementLog::select('notes')
            ->whereColumn('device_serial_number', 'devices.serial_number')
            ->where('action', 'Repair Completed')
            ->latest()
            ->limit(1)
        ])
        ->orderBy('faults_count', 'desc')
        ->take(5)
        ->get();

        return $stats; // CRITICAL: This sends the data back to the controller!
    }
}