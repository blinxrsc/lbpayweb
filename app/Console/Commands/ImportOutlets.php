<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Outlet;
use App\Models\Brand;
use App\Models\Manager;
use Illuminate\Support\Facades\DB;

class ImportOutlets extends Command
{
    protected $signature = 'import:outlets {file=Stores.csv}';
    protected $description = 'Import outlets from a specific CSV format';

    public function handle()
    {
        $file = $this->argument('file');

        if (!file_exists($file)) {
            $this->error("File $file not found.");
            return;
        }

        $handle = fopen($file, "r");
        
        // Skip the first line "Stores"
        fgets($handle);
        
        // Get Headers
        $headers = fgetcsv($handle);
        $count = 0;

        $this->info("Starting import...");

        DB::beginTransaction();
        try {
            while (($row = fgetcsv($handle)) !== FALSE) {
                // Combine headers with row values
                $data = array_combine($headers, $row);

                // Clean the ="value" formatting from CSV
                foreach ($data as $key => $value) {
                    $data[$key] = preg_replace('/^="|"$/', '', $value);
                }

                // 1. Resolve Brand
                $brandName = $data['Brands'] === '---' ? 'Unknown' : $data['Brands'];
                $brand = Brand::firstOrCreate(['name' => $brandName]);

                // 2. Resolve Manager (Defaulting to the first manager if ID not known)
                // Or you can create/find based on $data['Manager']
                $manager = Manager::firstOrCreate(['name' => $data['Manager'] ?: 'Default Manager']);

                // 3. Determine Type
                $type = 'own';
                if (str_contains($data['Store Name'], '(FR)')) {
                    $type = 'franchise';
                } elseif ($brandName === 'ALACART') {
                    $type = 'alacart';
                }

                // 4. Create Outlet
                // Note: Swapping Long/Lat from CSV because labels are swapped in the file
                Outlet::updateOrCreate(
                    ['outlet_name' => $data['Store Name']], // Match by name to avoid duplicates
                    [
                        'machine_number' => $data['Machine Nums'],
                        'business_hours' => $data['Business Hours'],
                        'country'        => $data['Country'],
                        'province'       => $data['Province'],
                        'city'           => $data['City'],
                        'address'        => $data['Address'],
                        'latitude'       => $data['Longitude'], // CSV 'Longitude' column actually contains Lat (e.g. 3.16)
                        'longitude'      => $data['Latitude'],  // CSV 'Latitude' column actually contains Long (e.g. 101.6)
                        'phone'          => $data['Phone #'],
                        'brand_id'       => $brand->id,
                        'manager_id'     => $manager->id,
                        'status'         => strtolower($data['Status']) === 'enabled' ? 'active' : 'closed',
                        'type'           => $type,
                    ]
                );

                $count++;
            }
            DB::commit();
            $this->info("Successfully imported $count outlets.");
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error("Error at row $count: " . $e->getMessage());
        }

        fclose($handle);
    }
}