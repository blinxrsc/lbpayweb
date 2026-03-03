<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthStatus;
use App\Models\Outlet;
use App\Models\DeviceOutlet;
use App\Models\TypeOutlet;
use App\Models\TypeStatus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $status = HealthStatus::find(1);
        $services = $status ? json_decode($status->data, true) : [];
        // Pass roles and permissions to the view
        $user = auth()->user();
        /**
        return view('dashboard.index', [
            'roles' => $user->roles->pluck('name'),
            'permissions' => $user->getAllPermissions()->pluck('name'),
        ]);
        //$start = $request->get('start_date');
        //$end = $request->get('end_date');
        */
        $start = $request->get('from');
        $end = $request->get('to');
        
        // Create a unique cache name based on the dates
        // Example: outlet_stats_2023-01-01_2023-12-31
        $cacheKey = 'outlet_stats_' . ($start ?? 'all') . '_' . ($end ?? 'all');
        // We use "Cache::remember" so the database only works once per hour.
        $stats = Cache::remember($cacheKey, 3600, function () use ($start, $end) 
        {
            $query = Outlet::query();

            // Apply date filters if the user provided them
            /**
            if ($start) { $query->whereDate('created_at', '>=', $start); }
            if ($end)   { $query->whereDate('created_at', '<=', $end); }
            */
            if ($start) {
                $query->where('created_at', '>=', Carbon::parse($start)->startOfDay());
            }
            if ($end) {
                // End of day ensures you get transactions at 11:59:59 PM
                $query->where('created_at', '<=', Carbon::parse($end)->endOfDay());
            }
            // 1. Device Connectivity (Online vs Offline)
            $deviceConnectivity = DB::table('device_has_outlet')
                ->select('status as label', DB::raw('count(*) as total'))
                ->groupBy('status')
                ->get();

            // 2. Device Availability (Available vs Busy)
            $deviceAvailability = DB::table('device_has_outlet')
                ->select('availability as label', DB::raw('count(*) as total'))
                ->groupBy('availability')
                ->get();
            // We use a clone of the query for each stat so the filters apply to all
            return [
                'deviceConnectivity' => $deviceConnectivity,
                'deviceAvailability' => $deviceAvailability,
                'byBrand'   => Outlet::join('brands', 'outlets.brand_id', '=', 'brands.id')
                            ->select('brands.name as label', DB::raw('count(*) as total'))
                            ->groupBy('brands.name')->get(),

                'byType'    => Outlet::join('type_outlets', 'outlets.type_id', '=', 'type_outlets.id')
                            ->select('type_outlets.name as label', DB::raw('count(*) as total'))
                            ->groupBy('type_outlets.name')
                            ->get(),

                'byStatus'  => Outlet::join('type_statuses', 'outlets.status_id', '=', 'type_statuses.id')
                            ->select('type_statuses.name as label', DB::raw('count(*) as total'))
                            ->groupBy('type_statuses.name')
                            ->get(),
                                
                'byCity'    => Outlet::select('city as label', DB::raw('count(*) as total'))
                            ->orderBy('total', 'desc')->limit(10)->groupBy('city')->get(),
                                
                'byState'   => Outlet::select('province as label', DB::raw('count(*) as total'))
                            ->groupBy('province')->get(),
            ];
        });

        return view('dashboard.index', array_merge($stats, [ 
            'roles' => $user->roles->pluck('name'), 
            'permissions' => $user->getAllPermissions()->pluck('name'), 
            'totalDevices' => \DB::table('device_has_outlet')->count(),
            'topPerformingDevices' => DeviceOutlet::with('outlet')
                    ->orderBy('lifetime_coins', 'desc')
                    ->limit(5)
                    ->get(),
            'start' => $start, 
            'end' => $end,
            'status' => $status, 
            'services' => $services 
        ]));
    }

    public function exportPdf(Request $request)
    {
        // We receive the chart images as Base64 strings from the frontend
        $data = [
            'statusChart' => $request->input('statusChart'),
            'typeChart'   => $request->input('typeChart'),
            'start'       => $request->input('start_date'),
            'end'         => $request->input('end_date'),
        ];

        $pdf = Pdf::loadView('dashboard.pdf', $data);
        return $pdf->download('outlet-report.pdf');
    }
}


