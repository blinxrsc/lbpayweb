<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeStatusesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the table name matches your migration 'type_statuses'
        DB::table('type_statuses')->insert([
            ['name' => 'active', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'closed', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'pending', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
