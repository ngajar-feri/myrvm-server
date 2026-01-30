<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add system_info column for storing system information from handshake.
     * 
     * This stores: jetpack_version, firmware_version, python_version, ai_models
     * Sent by Edge in the `system` object per GAI-handshake.md spec.
     */
    public function up(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            if (!Schema::hasColumn('edge_devices', 'system_info')) {
                $table->jsonb('system_info')->nullable()->after('hardware_config');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            if (Schema::hasColumn('edge_devices', 'system_info')) {
                $table->dropColumn('system_info');
            }
        });
    }
};
