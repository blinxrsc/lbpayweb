<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DeviceOutlet;
use App\Models\Outlet;
use Illuminate\Http\Request;

/**
 * Outlet / device-list / dashboard endpoints for the LB Admin app.
 * Everything here is scoped to Outlet::accessibleBy() — the same
 * outlet_user pivot the web admin's role/permission system already uses,
 * so a user only ever sees the outlets and devices bound to their account.
 */
class TechnicianOutletController extends Controller
{
    /**
     * Outlets this user can access, each with a live online/offline count
     * so the app can show a health indicator per outlet in the list.
     */
    public function outlets(Request $request)
    {
        $outlets = Outlet::accessibleBy($request->user())
            ->withCount('deviceOutlets')
            ->orderBy('outlet_name')
            ->get();

        return response()->json([
            'outlets' => $outlets->map(function (Outlet $outlet) {
                $online = $outlet->deviceOutlets->filter(fn ($d) => $d->is_online)->count();
                return [
                    'id' => $outlet->id,
                    'name' => $outlet->outlet_name,
                    'city' => $outlet->city,
                    'device_count' => $outlet->device_outlets_count,
                    'online_count' => $online,
                ];
            }),
        ]);
    }

    /**
     * Devices at one outlet — used by "Device Install" when the technician
     * browses instead of scanning a QR (e.g. picking from a list to see
     * what's already installed at a site before adding a new one).
     */
    public function devices(Request $request, Outlet $outlet)
    {
        abort_unless($request->user()->canAccessOutlet($outlet->id), 403, 'You do not have access to this outlet.');

        $devices = DeviceOutlet::where('outlet_id', $outlet->id)
            ->with('device')
            ->orderBy('machine_num')
            ->get();

        return response()->json([
            'outlet' => ['id' => $outlet->id, 'name' => $outlet->outlet_name],
            'devices' => $devices->map(fn (DeviceOutlet $d) => [
                'serial_number' => $d->device_serial_number,
                'machine_type' => $d->machine_type,
                'machine_num' => $d->machine_num,
                'machine_name' => $d->machine_name,
                'is_online' => (bool) $d->is_online,
                'availability' => (bool) $d->availability,
                'status' => $d->status,
            ]),
        ]);
    }

    /**
     * Home-screen summary: outlet count, device count, and a live online
     * ratio across everything this user can access.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();
        $outlets = Outlet::accessibleBy($user)->withCount('deviceOutlets')->get();

        $ids = $user->accessibleOutletIds(); // null = every outlet
        $deviceQuery = DeviceOutlet::query();
        if ($ids !== null) {
            $deviceQuery->whereIn('outlet_id', $ids);
        }
        $totalDevices = (clone $deviceQuery)->count();
        $onlineDevices = (clone $deviceQuery)->where('status', 'online')->count();

        return response()->json([
            'user' => [
                'name' => $user->name,
                'roles' => $user->getRoleNames(),
            ],
            'outlet_count' => $outlets->count(),
            'device_count' => $totalDevices,
            'online_count' => $onlineDevices,
        ]);
    }

    public function profile(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $user->getRoleNames(),
            'permissions' => $user->getAllPermissions()->pluck('name'),
            'can_access_all_outlets' => $user->canAccessAllOutlets(),
        ]);
    }
}
