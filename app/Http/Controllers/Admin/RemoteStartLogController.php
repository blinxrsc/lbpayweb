<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RemoteStartLog;
use Illuminate\Http\Request;

class RemoteStartLogController extends Controller
{
    public function index(Request $request)
    {
        $query = RemoteStartLog::with('user')->latest();

        if ($request->filled('device_serial_number')) {
            $query->where('device_serial_number', 'like', '%' . $request->device_serial_number . '%');
        }
        if ($request->filled('from')) {
            $query->where('created_at', '>=', $request->from . ' 00:00:00');
        }
        if ($request->filled('to')) {
            $query->where('created_at', '<=', $request->to . ' 23:59:59');
        }

        $logs = $query->paginate(20)->withQueryString();

        return view('admin.remote-start-logs.index', compact('logs'));
    }
}
