<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File;

class BackupController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.backup.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        // 1. Prevent timeouts for large projects
        set_time_limit(600); // 10 minutes
        ini_set('memory_limit', '512M');

        $request->validate(['format' => 'required|in:zip,tar.gz']);

        // Create a unique ID for this specific backup session
        $backupId = 'backup_' . auth()->id() . '_' . now()->timestamp;
        
        // Dispatch Job with the ID
        \App\Jobs\ProcessBackup::dispatch($request->format, auth()->id(), $backupId);

        return response()->json([
            'backup_id' => $backupId,
            'message' => 'Job dispatched'
        ]);
    }
    
    public function checkStatus($backupId)
    {
        // Check if the backup URL is stored in Cache (placed there by the Job)
        $data = cache()->get($backupId);

        if ($data) {
            return response()->json([
                'status' => 'completed',
                'url' => route('backup.download', ['filename' => $data['filename']])
            ]);
        }

        return response()->json(['status' => 'pending']);
    }

    public function download($filename)
    {
        $backupDir = storage_path('app/backups');
        $filePath = $backupDir . '/' . $filename;

        if (!file_exists($filePath)) {
            return back()->with('error', 'Backup file not found.');
        }

        // Stream the file to the browser, then delete it after sending
        return response()->download($filePath, $filename, [
            'Content-Type' => 'application/octet-stream',
            'Cache-Control' => 'no-cache, must-revalidate',
        ])->deleteFileAfterSend(true);
    }

    public function history()
    {
        $logPath = storage_path('logs/backup.log');
        $entries = [];

        if (File::exists($logPath)) {
            $lines = File::lines($logPath);

            foreach ($lines as $line) {
                // Example log format:
                // [2026-01-31 11:00:00] local.INFO: Backup created {"file":"lbpayweb_20260131.tar.gz","format":"tar.gz","created_by":1,"timestamp":"2026-01-31 11:00:00","deleted_after_download":true}
                preg_match('/\[(.*?)\].*Backup (created|downloaded).*({.*})/', $line, $matches);

                if ($matches) {
                    $details = json_decode($matches[3], true);

                    $entries[] = [
                        'datetime' => $matches[1],
                        'action'   => $matches[2],
                        'file'     => $details['file'] ?? null,
                        'format'   => $details['format'] ?? null,
                        'created_by' => $details['created_by'] ?? ($details['downloaded_by'] ?? null),
                        'timestamp'  => $details['timestamp'] ?? null,
                        'deleted_after_download' => $details['deleted_after_download'] ?? false,
                        //'details'   => $details, // keep raw for flexibility
                    ];
                }
            }
        }

        return view('admin.backup.history', compact('entries'));
    }

}
