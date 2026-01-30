<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TechnicianAssignment extends Model
{
    protected $fillable = [
        'technician_id',
        'rvm_machine_id',
        'assigned_by',
        'status',
        'access_pin',
        'pin_expires_at',
        'description'
    ];

    protected $casts = [
        'pin_expires_at' => 'datetime',
    ];

    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class);
    }
}

