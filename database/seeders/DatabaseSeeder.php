<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        /**
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        */

        //add 29-12-25
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call([
            TypeStatusesSeeder::class,
            TypeOutletsSeeder::class,
            // OutletSeeder should come AFTER these
        ]);

    }
}
