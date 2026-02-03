<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

class LogBackupController extends Controller
{
    /**
     * List all available log backups
     */
    public function index()
    {
        $backupDir = 'logs/backups';
        
        if (!Storage::disk('local')->exists($backupDir)) {
            return response()->json(['data' => []]);
        }

        $allFiles = Storage::disk('local')->allFiles($backupDir);
        
        $backups = collect($allFiles)
            ->filter(fn($file) => str_ends_with($file, '.json.gz'))
            ->map(function ($file) {
                // Path format: logs/backups/02 February 2026/filename.json.gz
                $parts = explode('/', $file);
                $dateFolder = $parts[count($parts) - 2] ?? 'Unknown';
                
                return [
                    'filename' => basename($file),
                    'path' => $file,
                    'date_folder' => $dateFolder,
                    'size_bytes' => Storage::disk('local')->size($file),
                    'created_at' => Carbon::createFromTimestamp(Storage::disk('local')->lastModified($file))->toDateTimeString(),
                ];
            })
            ->sortByDesc('created_at')
            ->values();

        return response()->json(['data' => $backups]);
    }

    /**
     * Trigger a new backup
     */
    public function store()
    {
        try {
            $exitCode = Artisan::call('activity-log:backup');
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Backup created successfully',
                    'output' => $output
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to create backup',
                'output' => $output
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Restore logs from a specific file
     */
    public function restore(Request $request)
    {
        $request->validate([
            'file' => 'required|string'
        ]);

        $filePath = $request->file;

        if (!Storage::disk('local')->exists($filePath)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Backup file not found'
            ], 404);
        }

        try {
            $exitCode = Artisan::call('activity-log:restore', ['file' => $filePath]);
            $output = Artisan::output();

            if ($exitCode === 0) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Logs restored successfully',
                    'output' => $output
                ]);
            }

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to restore logs',
                'output' => $output
            ], 500);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Download a backup file
     */
    public function download($filename)
    {
        // We need to find the file since it's nested in date folders
        $backupDir = 'logs/backups';
        $allFiles = Storage::disk('local')->allFiles($backupDir);
        
        $filePath = collect($allFiles)
            ->first(fn($file) => basename($file) === $filename);

        if (!$filePath || !Storage::disk('local')->exists($filePath)) {
            return response()->json(['message' => 'File not found'], 404);
        }

        return Storage::disk('local')->download($filePath);
    }

    /**
     * Clear all activity logs after creating a backup
     */
    public function destroyAll(Request $request)
    {
        $request->validate([
            'password' => 'required|string'
        ]);

        try {
            // 0. Verify Super Admin password
            $superAdmin = \App\Models\User::where('role', 'super_admin')->first();
            if (!$superAdmin || !Hash::check($request->password, $superAdmin->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid Super Admin password'
                ], 401);
            }

            // 1. Create auto-backup first
            $exitCode = Artisan::call('activity-log:backup');
            $output = Artisan::output();

            if ($exitCode !== 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to create auto-backup before clearing logs',
                    'output' => $output
                ], 500);
            }

            // 2. Clear logs
            ActivityLog::query()->delete();

            // 3. Log this action (even though we just cleared logs, this is a new entry)
            ActivityLog::log('System', 'Clear', 'All activity logs cleared after auto-backup');

            return response()->json([
                'status' => 'success',
                'message' => 'All activity logs successfully backed up and cleared',
                'output' => $output
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
