<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\EdgeDevice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeviceController extends Controller
{
    /**
     * Display edge devices management page (full page load).
     */
    public function index()
    {
        return view('dashboard.devices.index');
    }

    /**
     * Return content only for SPA navigation.
     */
    public function indexContent()
    {
        return view('dashboard.devices.index-content');
    }

    /**
     * Get devices list via AJAX.
     */
    public function getDevices(Request $request)
    {
        $status = $request->get('status');

        $query = EdgeDevice::with('rvmMachine');

        // Status filter
        if ($status) {
            $query->where('status', $status);
        }

        $devices = $query->latest()->get();

        // Add additional info
        $devices->each(function ($device) {
            // Calculate uptime
            if ($device->last_seen) {
                $device->uptime_minutes = now()->diffInMinutes($device->last_seen);
                $device->is_online = $device->uptime_minutes < 5; // Online if seen in last 5 min
            } else {
                $device->is_online = false;
            }
        });

        return response()->json($devices);
    }

    /**
     * Get device telemetry data.
     */
    public function getDeviceTelemetry($id, Request $request)
    {
        $device = EdgeDevice::with('rvmMachine')->findOrFail($id);

        $minutes = $request->get('minutes', 60); // Last 60 minutes by default

        // Get telemetry data
        $telemetry = DB::table('device_telemetry')
            ->where('edge_device_id', $id)
            ->where('created_at', '>=', now()->subMinutes($minutes))
            ->orderBy('created_at')
            ->get();

        return response()->json([
            'device' => $device,
            'telemetry' => $telemetry,
            'current' => [
                'cpu_usage' => $telemetry->last()->cpu_usage ?? 0,
                'gpu_usage' => $telemetry->last()->gpu_usage ?? 0,
                'temperature' => $telemetry->last()->temperature ?? 0,
                'memory_usage' => $telemetry->last()->memory_usage ?? 0,
            ]
        ]);
    }
}
