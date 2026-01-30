<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Edge Telemetry table stores sensor data from RVM Edge devices.
     * Designed for offline-capable sync with:
     * - client_timestamp: when data was collected on Edge device
     * - server_timestamp: when data was received by server
     * - sync_status: tracks offline → synced state
     * - batch_id: groups multiple records from same offline session
     */
    public function up(): void
    {
        Schema::create('edge_telemetry', function (Blueprint $table) {
            $table->id();
            $table->foreignId('edge_device_id')->constrained('edge_devices')->onDelete('cascade');

            // Timestamps for offline sync support
            $table->timestamp('client_timestamp');  // Edge device local time when data captured
            $table->timestamp('server_timestamp')->useCurrent(); // Server receive time

            // Flexible sensor data (JSONB for PostgreSQL)
            // Example: {"ultrasonic_level": 85, "temperature": 42.5, "door_status": "locked"}
            $table->jsonb('sensor_data');

            // Device performance stats (optional)
            // Example: {"cpu_percent": 45, "ram_mb": 2048, "disk_percent": 80}
            $table->jsonb('device_stats')->nullable();

            // Offline sync tracking
            $table->string('sync_status', 20)->default('synced'); // synced, pending, failed
            $table->unsignedSmallInteger('sync_attempts')->default(0);
            $table->uuid('batch_id')->nullable(); // Group offline uploads

            $table->timestamps();

            // Indexes for performance
            $table->index(['edge_device_id', 'client_timestamp']); // Time-series queries
            $table->index('sync_status'); // Find pending syncs
            $table->index('batch_id'); // Group operations
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edge_telemetry');
    }
};
