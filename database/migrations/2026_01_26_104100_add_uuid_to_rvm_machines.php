<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Add UUID column to rvm_machines table.
     * 
     * UUID is a 36-character unique identifier used for:
     * - Signed URL generation for Kiosk access
     * - WebSocket channel identification
     * - External API references (more opaque than sequential ID)
     */
    public function up(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('rvm_machines', 'uuid')) {
                $table->uuid('uuid')->unique()->after('id')->nullable();
            }
        });

        // Generate UUIDs for existing records
        $machines = \DB::table('rvm_machines')->whereNull('uuid')->get();
        foreach ($machines as $machine) {
            \DB::table('rvm_machines')
                ->where('id', $machine->id)
                ->update(['uuid' => Str::uuid()->toString()]);
        }

        // Make uuid NOT NULL after seeding
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->uuid('uuid')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            if (Schema::hasColumn('rvm_machines', 'uuid')) {
                $table->dropColumn('uuid');
            }
        });
    }
};
