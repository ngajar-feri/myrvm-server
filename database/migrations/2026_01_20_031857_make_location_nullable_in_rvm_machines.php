<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Make location column nullable in rvm_machines table.
     * Fixes: SQLSTATE[23502]: Not null violation on location column.
     */
    public function up(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            // Make location nullable if it exists
            if (Schema::hasColumn('rvm_machines', 'location')) {
                $table->string('location')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rvm_machines', function (Blueprint $table) {
            if (Schema::hasColumn('rvm_machines', 'location')) {
                $table->string('location')->nullable(false)->change();
            }
        });
    }
};
