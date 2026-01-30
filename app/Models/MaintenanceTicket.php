<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * MaintenanceTicket Model
 * 
 * Represents a work order/task for RVM machine maintenance.
 * 
 * Status Flow: pending -> assigned -> in_progress -> resolved -> closed
 * 
 * Business Rule: assignee_id must exist in technician_assignments for the related RVM
 */
class MaintenanceTicket extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'rvm_machine_id',
        'created_by',
        'assignee_id',
        'category',
        'description',
        'priority',
        'status',
        'assigned_at',
        'started_at',
        'completed_at',
        'resolution_notes',
        'proof_image_path',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    /**
     * Ticket categories
     */
    public const CATEGORIES = [
        'Installation',
        'Sensor Fault',
        'Motor Jammed',
        'Network Issue',
        'Full Bin',
        'Other',
    ];

    /**
     * Status flow
     */
    public const STATUSES = [
        'pending',
        'assigned',
        'in_progress',
        'resolved',
        'closed',
    ];

    /**
     * Auto-generate ticket number on creation
     */
    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($ticket) {
            if (empty($ticket->ticket_number)) {
                $prefix = 'TKT-' . date('Ym') . '-';
                $lastTicket = static::where('ticket_number', 'like', $prefix . '%')
                    ->orderBy('ticket_number', 'desc')
                    ->first();

                $sequence = 1;
                if ($lastTicket) {
                    $lastNumber = (int) substr($lastTicket->ticket_number, -3);
                    $sequence = $lastNumber + 1;
                }

                $ticket->ticket_number = $prefix . str_pad((string) $sequence, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    /**
     * RVM Machine this ticket belongs to
     */
    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class);
    }

    /**
     * User who created/reported this ticket
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Technician assigned to this ticket
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    /**
     * Start work on this ticket
     */
    public function startWork(): bool
    {
        if ($this->status !== 'assigned') {
            return false;
        }

        $this->status = 'in_progress';
        $this->started_at = now();
        return $this->save();
    }

    /**
     * Complete this ticket
     */
    public function complete(string $notes = null, string $proofPath = null): bool
    {
        if ($this->status !== 'in_progress') {
            return false;
        }

        $this->status = 'resolved';
        $this->completed_at = now();
        $this->resolution_notes = $notes;
        $this->proof_image_path = $proofPath;
        return $this->save();
    }
}
