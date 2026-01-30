<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * 
     * Table: maintenance_tickets (Work Order / Tugas)
     * Purpose: Transactional tracking for repair/installation tasks
     * 
     * Flow: pending -> assigned -> in_progress -> resolved -> closed
     * Rule: assignee_id must exist in technician_assignments for this RVM
     */
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();

            // Ticket identifier: TKT-YYYYMM-XXX
            $table->string('ticket_number')->unique();

            // Relations
            $table->foreignId('rvm_machine_id')->constrained('rvm_machines');
            $table->foreignId('created_by')->constrained('users'); // Admin who reported

            // Assignee (must be validated against technician_assignments)
            $table->foreignId('assignee_id')->nullable()->constrained('users');

            // Issue Details
            // Categories: 'Installation', 'Sensor Fault', 'Motor Jammed', 'Network Issue', 'Full Bin', 'Other'
            $table->string('category');
            $table->text('description');
            $table->enum('priority', ['low', 'medium', 'high', 'critical'])->default('medium');

            // Status Tracking
            // Flow: pending -> assigned -> in_progress -> resolved -> closed
            $table->string('status')->default('pending');

            // Timeline
            $table->timestamp('assigned_at')->nullable(); // When admin assigns
            $table->timestamp('started_at')->nullable();  // When technician starts work
            $table->timestamp('completed_at')->nullable(); // When technician completes

            // Resolution Evidence
            $table->text('resolution_notes')->nullable(); // Technician notes
            $table->string('proof_image_path')->nullable(); // Photo proof

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
