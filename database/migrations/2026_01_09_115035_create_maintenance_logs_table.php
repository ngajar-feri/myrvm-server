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
        Schema::create('maintenance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('technician_id')->constrained('users');
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines');
            $table->foreignId('assignment_id')->nullable()->constrained('technician_assignments');
            $table->string('action_type'); // repair, cleaning, collection, update
            $table->text('notes')->nullable();
            $table->json('evidence_photos')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_logs');
    }
};
