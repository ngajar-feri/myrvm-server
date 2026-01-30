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
        Schema::table('rvm_machines', function (Blueprint $table) {
            // Location fields (will be synced from edge_devices)
            if (!Schema::hasColumn('rvm_machines', 'latitude')) {
                $table->decimal('latitude', 10, 8)->nullable()->after('status');
            }
            if (!Schema::hasColumn('rvm_machines', 'longitude')) {
                $table->decimal('longitude', 11, 8)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('rvm_machines', 'location_address')) {
                $table->text('location_address')->nullable()->after('longitude');
            }

            // Maintenance and model tracking
            if (!Schema::hasColumn('rvm_machines', 'last_maintenance')) {
                $table->timestamp('last_maintenance')->nullable();
            }
            if (!Schema::hasColumn('rvm_machines', 'last_model_sync')) {
                $table->timestamp('last_model_sync')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->dropColumn([
                'latitude',
                'longitude',
                'location_address',
                'last_maintenance',
                'last_model_sync'
            ]);
        });
    }
};
