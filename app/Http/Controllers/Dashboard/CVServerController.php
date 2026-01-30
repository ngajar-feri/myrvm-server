<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\AiModelVersion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CVServerController extends Controller
{
    /**
     * Display CV servers management page (full page load).
     */
    public function index()
    {
        return view('dashboard.cv-servers.index');
    }

    /**
     * Return content only for SPA navigation.
     */
    public function indexContent()
    {
        return view('dashboard.cv-servers.index-content');
    }

    /**
     * Get CV servers and training jobs.
     */
    public function getServers(Request $request)
    {
        // For now, return dummy server info
        // In production, this would connect to actual CV servers
        $servers = [
            [
                'id' => 1,
                'name' => 'CV-Primary-001',
                'status' => 'online',
                'gpu_count' => 4,
                'active_jobs' => 2,
                'total_models' => 15,
                'last_seen' => now()->subMinutes(2),
            ]
        ];

        return response()->json($servers);
    }

    /**
     * Get training jobs list.
     */
    public function getTrainingJobs(Request $request)
    {
        $status = $request->get('status');

        // Get training jobs from database
        // For now using ai_model_versions table
        $query = AiModelVersion::latest();

        if ($status) {
            $query->where('status', $status);
        }

        $jobs = $query->take(50)->get();

        return response()->json($jobs);
    }

    /**
     * Get model repository.
     */
    public function getModels(Request $request)
    {
        $models = AiModelVersion::where('is_active', true)
            ->orWhere('is_production', true)
            ->latest()
            ->get();

        // Add deployment stats
        $models->each(function ($model) {
            $model->deployed_devices = DB::table('edge_devices')
                ->where('current_model_version', $model->version)
                ->count();

            $model->download_count = DB::table('model_downloads')
                ->where('model_version_id', $model->id)
                ->count();
        });

        return response()->json($models);
    }
}
