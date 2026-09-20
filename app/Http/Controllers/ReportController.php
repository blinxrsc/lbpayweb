<?php

namespace App\Http\Controllers;

use App\Services\MemberReportService;
use App\Services\MaintenanceReportService;
use App\Services\DeviceReportService; // Add this
use App\Services\DeviceRevenueReportService;
use App\Models\Outlet;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $reportService;
    protected $deviceService;

    public function __construct(
        MemberReportService $reportService,
        DeviceReportService $deviceService
        )
    {
        $this->reportService = $reportService;
        $this->deviceService = $deviceService;
    }

    public function weekly()
    {
        return response()->json($this->reportService->weeklyReport());
    }

    public function monthly()
    {
        return response()->json($this->reportService->monthlyReport());
    }

    // Blade view endpoint
    public function index()
    {
        return view('reports.members');
    }

    // New Device Maintenance Method
    public function deviceMaintenance(Request $request, MaintenanceReportService $service)
    {
        // Get stats from service
        $stats = $service->getMaintenanceStats(
            $request->query('month'), 
            $request->query('year')
        );

        // Pass $stats to the view
        return view('reports.maintenance', compact('stats'));
    }

    public function exportPdf(Request $request)
    {
        $pdf = $this->deviceService->generatePdfReport($request->month, $request->year);
        
        $fileName = "Maintenance_Report_" . $request->month . "_" . $request->year . ".pdf";
        return $pdf->download($fileName);
    }

    /**
     * Device Revenue Summary — mobile (gateway) vs member (wallet) vs
     * total revenue, filterable by outlet and date range. Defaults to the
     * current calendar month when no range is given.
     */
    public function deviceRevenue(Request $request, DeviceRevenueReportService $revenueService)
    {
        $from = $request->filled('from') ? Carbon::parse($request->from) : now()->startOfMonth();
        $to = $request->filled('to') ? Carbon::parse($request->to) : now()->endOfMonth();
        $outletId = $request->filled('outlet_id') ? (int) $request->outlet_id : null;

        $summary = $revenueService->summary($outletId, $from, $to);
        $byOutlet = $revenueService->byOutlet($outletId, $from, $to);
        $outlets = Outlet::orderBy('outlet_name')->get();

        return view('reports.device-revenue', compact('summary', 'byOutlet', 'outlets', 'from', 'to', 'outletId'));
    }
}