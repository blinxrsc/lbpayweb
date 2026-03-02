<?php

namespace App\Http\Controllers;

use App\Services\MemberReportService;
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
    public function deviceMaintenance(Request $request)
    {
        // Default to current month and year if not provided in the request
        $month = $request->input('month', now()->month);
        $year = $request->input('year', now()->year);
        
        $data = $this->deviceService->maintenanceReport(
            $request->month, 
            $request->year
        );

        // If it's an AJAX call, return JSON, otherwise return the view
        if ($request->wantsJson()) {
            return response()->json($data);
        }

        return view('reports.maintenance', ['stats' => $data]);
    }

    public function exportPdf(Request $request)
    {
        $pdf = $this->deviceService->generatePdfReport($request->month, $request->year);
        
        $fileName = "Maintenance_Report_" . $request->month . "_" . $request->year . ".pdf";
        return $pdf->download($fileName);
    }
}