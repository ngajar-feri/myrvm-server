<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserSession extends Model
{
    protected $fillable = [
        'user_id',
        'rvm_machine_id',
        'session_code',
        'status',
        'qr_generated_at',
        'expires_at',
        'activated_at',
        'cancelled_at',
    ];

    protected $casts = [
        'qr_generated_at' => 'datetime',
        'expires_at' => 'datetime',
        'activated_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && now()->greaterThan($this->expires_at);
    }
}
