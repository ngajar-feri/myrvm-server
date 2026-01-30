<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add columns for Edge Handshake endpoint.
     * 
     * These columns store hardware configuration, diagnostics,
     * and handshake metadata from RVM-Edge Setup Wizard.
     */
    public function up(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            // Hardware configuration (cameras, sensors, actuators, microcontroller)
            if (!Schema::hasColumn('edge_devices', 'hardware_config')) {
                $table->jsonb('hardware_config')->nullable()->after('health_metrics');
            }

            // Diagnostics log from last handshake
            if (!Schema::hasColumn('edge_devices', 'diagnostics_log')) {
                $table->jsonb('diagnostics_log')->nullable()->after('hardware_config');
            }

            // Timezone for display synchronization
            if (!Schema::hasColumn('edge_devices', 'timezone')) {
                $table->string('timezone')->default('Asia/Jakarta')->after('diagnostics_log');
            }

            // Last handshake timestamp
            if (!Schema::hasColumn('edge_devices', 'last_handshake_at')) {
                $table->timestamp('last_handshake_at')->nullable()->after('timezone');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            $columns = ['hardware_config', 'diagnostics_log', 'timezone', 'last_handshake_at'];
            foreach ($columns as $col) {
                if (Schema::hasColumn('edge_devices', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
