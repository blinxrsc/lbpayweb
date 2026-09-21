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
			'manage backup',
			// Bypasses outlet-level scoping (see User::canAccessAllOutlets()).
			// Only give this to roles that should see every outlet.
			'outlets.view-all',
			// Dot-notation permissions added manually via Admin > Permissions
			// and wired into routes/web.php + Blade @can() checks. Listed here
			// too so a fresh environment's seed matches what's already live.
			'devices.manage',
			'devices_outlet.manage',
			'devices_outlet.edit',
			'devices_outlet.delete',
			'ewallet.manage',
			'setting.backup',
			'logs.manage',
			'logs.health',
			'logs.user',
			'logs.mailserver',
			// Also already live via Admin > Permissions but missing from
			// this seeder (same situation as the devices_outlet.* block
			// above) — the sidebar's Transaction section already checks
			// these. transactions.remote-start is new, added alongside the
			// Remote Start log feature.
			'transactions.manage',
			'transactions.device',
			'transactions.member',
			'transactions.topup',
			'transactions.remote-start',
			'reports.device-revenue',
			'reports.outlet-revenue',
			'transactions.refund',
		];

		foreach ($permissions as $perm) 
		{
			Permission::firstOrCreate(['name' => $perm]);
		}
		//default roles
		$admin = Role::firstOrCreate(['name' => 'admin']);
		$user = Role::firstOrCreate(['name' => 'user']);
		// Outlet manager: scoped to whichever outlets are assigned to them
		// via the outlet_user pivot table (see Admin > Users > assign outlets).
		$outletManager = Role::firstOrCreate(['name' => 'outlet_manager']);
		//assign permissions
		$admin->givePErmissionTo(Permission::all());
		$user->givePermissionTo('manage shop detail');
		// NOTE: 'devices.manage' / 'devices_outlet.manage' / 'devices_outlet.edit'
		// are the ones routes/web.php actually checks now (can:devices.manage etc.) —
		// keeping the older 'manage device' / 'manage device revenue' strings too
		// only because other, not-yet-updated parts of the app may still check them.
		// Deliberately NOT granting 'devices_outlet.delete' here — removing a
		// device/outlet assignment is treated as an admin-level action.
		$outletManager->givePermissionTo([
			'manage shop detail',
			'manage device',
			'manage device revenue',
			'devices.manage',
			'devices_outlet.manage',
			'devices_outlet.edit',
		]);
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
