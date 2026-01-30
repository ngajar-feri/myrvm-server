<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $fillable = [
        'batch_id',
        'user_id',
        'team_user_ids',
        'machine_id',
        'assigned_by',
        'status',
        'latitude',
        'longitude',
        'address',
        'notes',
        'completed_at',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'completed_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'team_user_ids' => 'array',
    ];

    /**
     * Get the primary user (technician) assigned to this task
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get all team members for this assignment
     */
    public function getTeamAttribute()
    {
        if (empty($this->team_user_ids)) {
            return $this->user ? [$this->user] : [];
        }

        return User::whereIn('id', $this->team_user_ids)->get();
    }

    /**
     * Get the RVM machine for this assignment
     */
    public function machine()
    {
        return $this->belongsTo(RvmMachine::class, 'machine_id');
    }

    /**
     * Get the user who created this assignment
     */
    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
