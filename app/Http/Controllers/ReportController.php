<?php

namespace App\Http\Controllers;

use App\Services\MemberReportService;
use App\Services\MaintenanceReportService;
use App\Services\DeviceReportService; // Add this
use Illuminate\Http\Request;

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
}