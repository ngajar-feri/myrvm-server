<?php

namespace App\Console\Commands;

use App\Models\EdgeDevice;
use App\Models\RvmMachine;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckOfflineDevices extends Command
{
    /**
     * The name and signature of the console command.
     * 
     * Usage: php artisan edge:check-offline --threshold=120
     *
     * @var string
     */
    protected $signature = 'edge:check-offline {--threshold=120 : Seconds of inactivity before marking offline}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check for stale Edge Devices and mark them as offline if no heartbeat received within threshold';

    /**
     * Execute the console command.
     * 
     * Logic:
     * 1. Find all EdgeDevices where status = 'online'
     * 2. Check if updated_at > threshold seconds ago
     * 3. Mark as 'offline' and also update parent RvmMachine
     */
    public function handle()
    {
        $threshold = (int) $this->option('threshold');
        $cutoff = now()->subSeconds($threshold);
        
        $this->info("Starting optimized Offline Checker...");
        $this->info("Threshold: {$threshold}s (Cutoff: {$cutoff})");

        // 1. Process ACTIVE/ASSIGNED: Monitor for heartbeats
        $staleDevices = EdgeDevice::where('status', 'online')
            ->whereHas('rvmMachine.technicians', function ($query) {
                $query->whereIn('technician_assignments.status', ['assigned', 'active']);
            })
            ->where('updated_at', '<', $cutoff)
            ->get();

        foreach ($staleDevices as $device) {
            $this->markAs($device, 'offline', "Inactive heartbeat");
        }

        // 2. Process OTHERS (Suspended, etc.): Switch to maintenance mode
        // Only if they are currently marked 'online' but their assignments are not active
        $maintenanceDevices = EdgeDevice::where('status', 'online')
            ->whereHas('rvmMachine.technicians') // Has assignments
            ->whereDoesntHave('rvmMachine.technicians', function ($query) {
                $query->whereIn('technician_assignments.status', ['assigned', 'active']);
            })
            ->get();

        foreach ($maintenanceDevices as $device) {
            $this->markAs($device, 'maintenance', "Assignment suspended or revoked");
        }

        $this->info("Check completed.");
        return 0;
    }

    /**
     * Helper to update status for both EdgeDevice and parent RvmMachine
     */
    private function markAs(EdgeDevice $device, string $status, string $reason)
    {
        $device->update(['status' => $status]);
        
        if ($device->rvm_machine_id) {
            RvmMachine::where('id', $device->rvm_machine_id)
                ->update(['status' => $status]);
        }

        $this->warn("Marked " . strtoupper($status) . ": {$device->device_id} (Reason: {$reason})");
        Log::warning("[OfflineChecker] Device {$device->device_id} marked {$status}. Reason: {$reason}");
    }
}
