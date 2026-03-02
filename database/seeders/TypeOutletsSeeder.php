<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TypeOutletsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure the table name matches your migration 'type_outlet'
        DB::table('type_outlets')->insert([
            ['name' => 'own', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'joint', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'franchise', 'created_at' => now(), 'updated_at' => now()],
            ['name' => 'alacart', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }
}
