<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Device;
use App\Models\Outlet;
use App\Models\DeviceOutlet;
use Illuminate\Support\Facades\DB;

class ImportDevices extends Command
{
    // Usage: php artisan import:devices
    protected $signature = 'import:devices {file=Devices.csv}';
    protected $description = 'Import devices and link them to outlets with coin data';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File $file not found. Please ensure the CSV is in the root folder.");
            return;
        }

        $handle = fopen($file, "r");
        fgets($handle); // Skip the "Devices" label row
        $headers = fgetcsv($handle); // Get actual column headers
        
        $count = 0;
        $skipped = 0;

        $this->info("Starting device and coin data import...");

        while (($row = fgetcsv($handle)) !== FALSE) {
            $data = array_combine($headers, $row);

            // Clean the formatting (removes =" ")
            foreach ($data as $key => $value) {
                $data[$key] = preg_replace('/^="|"$/', '', $value);
            }

            // 1. Find the Outlet by Name
            $outlet = Outlet::where('outlet_name', $data['Store Name'])->first();

            if (!$outlet) {
                $this->warn("Skipped: Outlet '{$data['Store Name']}' not found in database.");
                $skipped++;
                continue;
            }

            DB::beginTransaction();
            try {
                // 2. Create/Update the physical hardware record
                $device = Device::updateOrCreate(
                    ['serial_number' => $data['Machine SN']],
                    [
                        'type' => $data['Machine Type'],
                        'model_name' => $data['Machine Name'],
                        'firmware_version' => $data['Version #'],
                        'supplier_id' => 1,
                        'model' => 'ESP32',
                        'outlet_id' => $outlet->id,
                        'status' => $outlet->id !== null ? 'assigned' : 'unassigned',
                    ]
                );

                // 3. Link Device to Outlet and save Coin Counts
                DeviceOutlet::updateOrCreate(
                    [
                        'outlet_id' => $outlet->id,
                        'device_serial_number' => $device->serial_number
                    ],
                    [
                        'machine_num'     => $data['Machine ID'],
                        'machine_name'     => $data['Machine Name'],
                        'machine_type'     => $data['Machine Type'],
                        'status'         => strtolower($data['Status']),
                        //'availability'   => strtolower($data['Availability']),
                        'availability'   => 1,
                        
                        // NEW FIELDS MAPPED HERE:
                        'current_coins'  => (int) ($data['Current Coins'] ?? 0),
                        'lifetime_coins' => (int) ($data['Life time Coins'] ?? 0),
                        
                        'created_at'     => $data['Created At'],
                        'updated_at'     => $data['Updated At'],
                    ]
                );

                DB::commit();
                $count++;
                
                if ($count % 500 === 0) {
                    $this->info("Processed $count records...");
                }
            } catch (\Exception $e) {
                DB::rollBack();
                $this->error("Error at record $count: " . $e->getMessage());
            }
        }

        fclose($handle);
        $this->info("DONE!");
        $this->info("✅ Successfully imported/updated: $count devices.");
        if ($skipped > 0) {
            $this->warn("⚠️ Skipped $skipped devices because the Store Name didn't match.");
        }
    }
}