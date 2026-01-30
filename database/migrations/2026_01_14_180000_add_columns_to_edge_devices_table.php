<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Add new columns to edge_devices table per revised schema.
     */
    public function up(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            // Network interfaces (IP auto-updated via heartbeat, not manual)
            if (!Schema::hasColumn('edge_devices', 'ip_address_local')) {
                $table->string('ip_address_local')->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('edge_devices', 'network_interfaces')) {
                $table->jsonb('network_interfaces')->nullable()->after('tailscale_ip');
            }

            // Identity & Registration
            if (!Schema::hasColumn('edge_devices', 'location_name')) {
                $table->string('location_name')->nullable()->after('network_interfaces');
            }
            if (!Schema::hasColumn('edge_devices', 'inventory_code')) {
                $table->string('inventory_code')->nullable()->after('location_name');
            }
            if (!Schema::hasColumn('edge_devices', 'description')) {
                $table->text('description')->nullable()->after('inventory_code');
            }

            // Hardware configuration
            if (!Schema::hasColumn('edge_devices', 'controller_type')) {
                $table->string('controller_type')->default('NVIDIA Jetson')->after('health_metrics');
            }
            if (!Schema::hasColumn('edge_devices', 'camera_id')) {
                $table->string('camera_id')->nullable()->after('controller_type');
            }
            if (!Schema::hasColumn('edge_devices', 'threshold_full')) {
                $table->integer('threshold_full')->default(90)->after('camera_id');
            }

            // API Key (hashed)
            if (!Schema::hasColumn('edge_devices', 'api_key')) {
                $table->string('api_key')->nullable()->after('status');
            }

            // Geolocation (for map)
            if (!Schema::hasColumn('edge_devices', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('description');
            }
            if (!Schema::hasColumn('edge_devices', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('edge_devices', 'address')) {
                $table->text('address')->nullable()->after('longitude');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            $columns = [
                'ip_address_local',
                'network_interfaces',
                'location_name',
                'inventory_code',
                'description',
                'controller_type',
                'camera_id',
                'threshold_full',
                'api_key',
                'latitude',
                'longitude',
                'address'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('edge_devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
