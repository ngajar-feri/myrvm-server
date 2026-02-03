<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class BackupActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-log:backup';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backup all activity logs to a compressed JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting Activity Logs backup...');

        $now = Carbon::now();
        // Path: storage/logs/backups/{tanggal bulan tahun log dibackups}/
        // Format requested: {tanggal bulan tahun}
        $dateFolder = $now->format('d F Y'); // e.g., "02 February 2026"
        $directory = "logs/backups/{$dateFolder}";
        
        $filename = "activity_logs_backup_" . $now->format('Ymd_His') . ".json.gz";
        $fullPath = "{$directory}/{$filename}";

        $logs = ActivityLog::all();
        
        if ($logs->isEmpty()) {
            $this->warn('No activity logs found to backup.');
            return 0;
        }

        $this->info("Found " . $logs->count() . " logs. Preparing data...");

        $jsonData = json_encode($logs->toArray(), JSON_PRETTY_PRINT);
        
        // Compress data
        $compressedData = gzencode($jsonData, 9);
        
        if ($compressedData === false) {
            $this->error('Failed to compress log data.');
            return 1;
        }

        // Save to public or local storage? 
        // User asked for myrvm-server/storage/logs/backups/
        // Laravel's Storage::put uses the 'local' disk by default which is storage/app
        // We can use a custom path or specific disk. 
        // Let's ensure the directory exists first.
        
        if (!Storage::disk('local')->exists($directory)) {
            Storage::disk('local')->makeDirectory($directory);
        }

        Storage::disk('local')->put($fullPath, $compressedData);

        $this->info("Backup successfully saved to: " . Storage::disk('local')->path($fullPath));
        $this->info("File size: " . round(strlen($compressedData) / 1024, 2) . " KB");

        return 0;
    }
}
