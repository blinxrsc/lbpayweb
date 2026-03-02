<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
//add from doc 29-12-25
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use App\Models\User;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        //add from doc 29-12-25
		//default permissions
		$permissions = [
			'manage shop detail',
			'manage shop revenue',
			'manage device',
			'manage device revenue',
			'manage ewallet',
			'manage backup'
		];

		foreach ($permissions as $perm) 
		{
			Permission::firstOrCreate(['name' => $perm]);
		}
		//default roles
		$admin = Role::firstOrCreate(['name' => 'admin']);
		$user = Role::firstOrCreate(['name' => 'user']);
		//assign permissions
		$admin->givePErmissionTo(Permission::all());
		$user->givePermissionTo('manage shop detail');
		// Create demo users for each role 
		$admin = User::firstOrCreate( ['email' => 'admin@example.com'], ['name' => 'Admin User', 'password' => bcrypt('password')] ); 
		$admin->assignRole('admin'); 
		$user = User::firstOrCreate( ['email' => 'user@example.com'], ['name' => 'User', 'password' => bcrypt('password')] ); 
		$user->assignRole('user');
/**
		$user = User::find(1);
        if ($user) {
            $user->assignRole('admin');
        }

        $user2 = User::find(2);
        if ($user2) {
            $user2->assignRole('user');
        }
*/
    }
}
