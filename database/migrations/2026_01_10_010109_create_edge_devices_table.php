<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('edge_devices')) {
            return; // Table already exists, skip creation
        }

        Schema::create('edge_devices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rvm_id')->nullable(); // Will add index separately
            $table->string('device_serial', 255)->unique();
            $table->ipAddress('tailscale_ip')->nullable();
            $table->timestamp('last_heartbeat')->nullable();
            $table->string('ai_model_version', 50)->nullable();
            $table->string('status', 20)->default('offline'); // online, offline, maintenance
            $table->json('hardware_info')->nullable();

            // Location Tracking (Manual or GPS Module)
            $table->decimal('latitude', 10, 8)->nullable(); // e.g., -6.20876543
            $table->decimal('longitude', 11, 8)->nullable(); // e.g., 106.84567890
            $table->decimal('location_accuracy_meters', 6, 2)->nullable(); // GPS accuracy
            $table->string('location_source', 20)->default('manual'); // 'manual' or 'gps_module'
            $table->timestamp('location_last_updated')->nullable();
            $table->text('location_address')->nullable(); // Human-readable address

            $table->timestamps();

            // Indexes for performance
            $table->index('rvm_id');
            $table->index('device_serial');
            $table->index('status');
            $table->index(['latitude', 'longitude']); // For geolocation queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('edge_devices');
    }
};
