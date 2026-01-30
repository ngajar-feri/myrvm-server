<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds soft delete support for Edge Devices.
     */
    public function up(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            $table->softDeletes(); // Adds deleted_at column
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('edge_devices', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
};
