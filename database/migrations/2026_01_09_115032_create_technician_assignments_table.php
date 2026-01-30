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
        Schema::create('technician_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users');
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines');
            $table->string('status')->default('assigned'); // assigned, in_progress, completed
            $table->string('access_pin')->nullable(); // Temporary PIN
            $table->timestamp('pin_expires_at')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('technician_assignments');
    }
};
