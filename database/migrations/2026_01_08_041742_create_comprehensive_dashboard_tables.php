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
        Schema::create('rvm_machines', function (Blueprint $table) {
            $table->id();
            $table->string('serial_number')->unique();
            $table->string('location_name');
            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('status')->default('offline'); // online, offline, maintenance, full
            $table->integer('capacity_percentage')->default(0);
            $table->timestamps();
        });

        Schema::create('edge_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_id')->unique(); // e.g., MAC address or custom ID
            $table->foreignId('rvm_machine_id')->nullable()->constrained('rvm_machines')->onDelete('set null');
            $table->string('type'); // jetson, microcontroller, camera, etc.
            $table->string('ip_address')->nullable();
            $table->string('firmware_version')->nullable();
            $table->string('status')->default('unknown');
            $table->json('health_metrics')->nullable(); // CPU, RAM, Temp
            $table->timestamps();
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users');
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines');
            $table->string('waste_type'); // plastic_bottle, can, glass
            $table->integer('item_count')->default(1);
            $table->decimal('weight_kg', 8, 3)->default(0);
            $table->integer('points_earned')->default(0);
            $table->string('status')->default('completed');
            $table->timestamp('transaction_time')->useCurrent();
            $table->timestamps();
        });

        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('module'); // Auth, Device, Machine, System
            $table->string('action'); // Login, Update, Error, Warning
            $table->text('description')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('edge_devices');
        Schema::dropIfExists('rvm_machines');
    }
};
