<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Fix missing columns in edge_devices table.
     * This migration adds columns that are missing from the current schema.
     */
    public function up(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            // Core network columns
            if (!Schema::hasColumn('edge_devices', 'tailscale_ip')) {
                $table->ipAddress('tailscale_ip')->nullable()->after('device_id');
            }
            if (!Schema::hasColumn('edge_devices', 'ip_address_local')) {
                $table->string('ip_address_local')->nullable()->after('tailscale_ip');
            }
            if (!Schema::hasColumn('edge_devices', 'network_interfaces')) {
                $table->jsonb('network_interfaces')->nullable()->after('ip_address_local');
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

            // Health & Hardware
            if (!Schema::hasColumn('edge_devices', 'health_metrics')) {
                $table->jsonb('health_metrics')->nullable()->after('status');
            }
            if (!Schema::hasColumn('edge_devices', 'controller_type')) {
                $table->string('controller_type')->default('NVIDIA Jetson')->after('health_metrics');
            }
            if (!Schema::hasColumn('edge_devices', 'camera_id')) {
                $table->string('camera_id')->nullable()->after('controller_type');
            }
            if (!Schema::hasColumn('edge_devices', 'threshold_full')) {
                $table->integer('threshold_full')->default(90)->after('camera_id');
            }
            if (!Schema::hasColumn('edge_devices', 'ai_model_version')) {
                $table->string('ai_model_version')->nullable()->after('threshold_full');
            }

            // API Key (hashed, for device authentication)
            if (!Schema::hasColumn('edge_devices', 'api_key')) {
                $table->string('api_key')->nullable()->after('status');
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
                'tailscale_ip',
                'ip_address_local',
                'network_interfaces',
                'location_name',
                'inventory_code',
                'description',
                'latitude',
                'longitude',
                'address',
                'health_metrics',
                'controller_type',
                'camera_id',
                'threshold_full',
                'ai_model_version',
                'api_key'
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('edge_devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
