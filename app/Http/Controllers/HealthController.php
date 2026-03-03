<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\HealthStatus;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class HealthController extends Controller
{
    public function index() {
        $status = HealthStatus::find(1);
        $services = $status ? json_decode($status->data, true) : [];
        return view('dashboard.health', compact('services', 'status'));
    }

    public function sync() {
        // Prevent overlapping runs for 30 seconds
        $lock = Cache::lock('health_checking', 30);

        if ($lock->get()) {
            try {
                Artisan::call('app:health-check'); // Your specific command
                return response()->json(['status' => 'success', 'message' => 'System health updated!']);
            } catch (\Exception $e) {
            // This helps you see the error in your browser's "Network" tab
            return response()->json(['error' => $e->getMessage()], 500);
            } finally {
                $lock->release();
            }
        }

        return response()->json(['status' => 'busy', 'message' => 'Check already in progress...'], 429);
    }
}
