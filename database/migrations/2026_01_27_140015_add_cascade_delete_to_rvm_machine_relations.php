<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Transactions - add cascade delete
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines')
                ->onDelete('cascade');
        });

        // 2. Maintenance Logs - add cascade delete
        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines')
                ->onDelete('cascade');
        });

        // 3. Maintenance Tickets - add cascade delete
        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines')
                ->onDelete('cascade');
        });

        // 4. Technician Assignments - add cascade delete
        Schema::table('technician_assignments', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to non-cascade (restrict is default)
        
        Schema::table('technician_assignments', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines');
        });

        Schema::table('maintenance_tickets', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines');
        });

        Schema::table('maintenance_logs', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines');
        });

        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['rvm_machine_id']);
            $table->foreign('rvm_machine_id')
                ->references('id')
                ->on('rvm_machines');
        });
    }
};
