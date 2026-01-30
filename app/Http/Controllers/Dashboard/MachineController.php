<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\RvmMachine;
use App\Models\TelemetryData;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MachineController extends Controller
{
    /**
     * Display machines management page (full page load).
     */
    public function index()
    {
        return view('dashboard.machines.index');
    }

    /**
     * Return content only for SPA navigation.
     */
    public function indexContent()
    {
        return view('dashboard.machines.index-content');
    }

    /**
     * Get machines list via AJAX.
     */
    public function getMachines(Request $request)
    {
        $status = $request->get('status');
        $location = $request->get('location');

        $query = RvmMachine::with('edgeDevice');

        // Status filter
        if ($status) {
            $query->where('status', $status);
        }

        // Location filter
        if ($location) {
            $query->where('location_address', 'like', "%{$location}%");
        }

        $machines = $query->latest()->get();

        // Add today's transaction count
        $machines->each(function ($machine) {
            $machine->today_count = DB::table('transactions')
                ->where('rvm_machine_id', $machine->id)
                ->whereDate('created_at', today())
                ->count();

            $machine->total_count = DB::table('transactions')
                ->where('rvm_machine_id', $machine->id)
                ->count();
        });

        return response()->json($machines);
    }

    /**
     * Get machine statistics.
     */
    public function getMachineStats($id)
    {
        $machine = RvmMachine::with('edgeDevice')->findOrFail($id);

        // Capacity trend (last 7 days)
        $capacityTrend = TelemetryData::where('rvm_machine_id', $id)
            ->where('created_at', '>=', now()->subDays(7))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('AVG(capacity_percentage) as avg_capacity')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        // Transaction stats (last 30 days)
        $transactionStats = DB::table('transactions')
            ->where('rvm_machine_id', $id)
            ->where('created_at', '>=', now()->subDays(30))
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(total_points) as points')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return response()->json([
            'machine' => $machine,
            'capacity_trend' => $capacityTrend,
            'transaction_stats' => $transactionStats
        ]);
    }
}
