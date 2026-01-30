<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('user_sessions')) {
            return;
        }

        Schema::create('user_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('rvm_machine_id')->nullable();
            $table->string('session_code', 100)->unique(); // UUID for QR code
            $table->string('status', 20)->default('pending'); // pending, active, completed, expired, cancelled
            $table->timestamp('qr_generated_at')->nullable();
            $table->timestamp('expires_at'); // Valid for 5 minutes
            $table->timestamp('activated_at')->nullable(); // When RVM scans QR
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index('user_id');
            $table->index('session_code');
            $table->index('status');
            $table->index('expires_at'); // For cleanup queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_sessions');
    }
};
