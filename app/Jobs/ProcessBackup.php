<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use ZipArchive;

class ProcessBackup implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $format;
    protected $userId;
    protected $backupId; // Add this property

    /**
     * Create a new job instance.
     */
    public function __construct($format, $userId, $backupId)
    {
        $this->format = $format;
        $this->userId = $userId;
        $this->backupId = $backupId;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $folder = base_path();
        $backupDir = storage_path('app/backups');
        
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0775, true);
        }

        $filename = 'lbpayweb_' . now()->format('Ymd_His') . '.' . $this->format;
        $backupPath = $backupDir . '/' . $filename;

        try {
            $zip = new ZipArchive;
            if ($zip->open($backupPath, ZipArchive::CREATE) === TRUE) {
                $files = new \RecursiveIteratorIterator(
                    new \RecursiveDirectoryIterator($folder),
                    \RecursiveIteratorIterator::LEAVES_ONLY
                );

                foreach ($files as $file) {
                    if (!$file->isDir()) {
                        $filePath = $file->getRealPath();
                        $relativePath = substr($filePath, strlen($folder) + 1);

                        // Skip heavy/unnecessary Docker folders
                        if ($this->shouldSkip($relativePath)) continue;

                        $zip->addFile($filePath, $relativePath);
                    }
                }
                $zip->close();
                // CRITICAL: Ensure the web server can read/delete the file
                chmod($backupPath, 0666); 

                // Store in cache for the UI Polling
                cache()->put($this->backupId, ['filename' => $filename], now()->addMinutes(10));
                //[2026-02-17 02:47:21] local.INFO: Backup created {"file":"lbpayweb_20260217_024719.zip","format":"zip","created_by":1,"timestamp":"2026-02-17 02:47:21","deleted_after_download":true} 
                Log::channel('backup')->info("Backup created", [
                    'file' => $filename,
                    'format' => $this->format,
                    'created_by' => $this->userId,
                    'timestamp' => now()->toDateTimeString(),
                    'deleted_after_download' => true,
                ]);
                
                // Optional: Trigger an EMQX MQTT message or Event here to notify the UI
            }
        } catch (\Exception $e) {
            Log::error("Backup Job Failed: " . $e->getMessage());
        }
    }

    private function shouldSkip($path) 
    {
        $excludes = ['vendor', 'node_modules', 'storage/app/backups', '.git', 'storage/framework/cache'];
        foreach ($excludes as $exclude) {
            if (str_contains($path, $exclude)) return true;
        }
        return false;
    }
}
