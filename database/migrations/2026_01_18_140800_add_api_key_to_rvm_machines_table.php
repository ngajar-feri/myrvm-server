<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Adds API Key column to rvm_machines for Edge Device authentication.
     */
    public function up(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            if (!Schema::hasColumn('rvm_machines', 'api_key')) {
                $table->string('api_key', 64)->unique()->nullable()->after('serial_number');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            $table->dropColumn('api_key');
        });
    }
};
