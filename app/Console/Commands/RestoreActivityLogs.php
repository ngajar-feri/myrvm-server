<?php

namespace App\Console\Commands;

use App\Models\ActivityLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class RestoreActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-log:restore {file : The path to the backup file relative to storage/app}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore activity logs from a compressed JSON file';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $filePath = $this->argument('file');

        if (!Storage::disk('local')->exists($filePath)) {
            $this->error("Backup file not found: {$filePath}");
            return 1;
        }

        $this->info("Restoring logs from: {$filePath}...");

        $compressedData = Storage::disk('local')->get($filePath);
        
        // Decompress
        $jsonData = gzdecode($compressedData);
        
        if ($jsonData === false) {
            $this->error('Failed to decompress log data.');
            return 1;
        }

        $logs = json_decode($jsonData, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON data in backup file.');
            return 1;
        }

        $this->info("Found " . count($logs) . " records. Restoring to database...");

        // Start transaction for safety
        DB::beginTransaction();
        try {
            foreach (array_chunk($logs, 100) as $chunk) {
                // Prepare data for insertion (strip ID to let DB generate new unique ones)
                $dataToInsert = array_map(function ($log) {
                    unset($log['id']);
                    // Ensure timestamps are in correct format if they are strings
                    return $log;
                }, $chunk);

                ActivityLog::insert($dataToInsert);
            }

            DB::commit();
            $this->info('Restore completed successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Failed to restore logs: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
