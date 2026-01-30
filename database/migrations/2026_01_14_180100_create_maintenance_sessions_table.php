<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Create maintenance_sessions table for technician PIN auth.
     */
    public function up(): void
    {
        Schema::create('maintenance_sessions', function (Blueprint $table) {
            $table->id();

            // Relation to RVM machine
            $table->foreignId('rvm_machine_id')
                ->constrained('rvm_machines')
                ->onDelete('cascade');

            // Relation to technician/admin who requested the PIN
            $table->foreignId('technician_id')
                ->constrained('users');

            // PIN hash (IMPORTANT: never store plain text)
            $table->string('pin_hash');

            // Expiration (e.g., 1 hour after generation)
            $table->timestamp('expires_at');

            // When PIN was successfully used (for audit)
            $table->timestamp('used_at')->nullable();

            // IP address of device that used the PIN
            $table->string('used_from_ip')->nullable();

            $table->timestamps();

            // Index for faster lookup
            $table->index(['rvm_machine_id', 'expires_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_sessions');
    }
};
