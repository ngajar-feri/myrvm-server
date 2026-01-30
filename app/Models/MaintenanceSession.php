<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;

class MaintenanceSession extends Model
{
    /**
     * Maintenance Session model - for technician PIN authentication.
     */
    protected $fillable = [
        'rvm_machine_id',
        'technician_id',
        'pin_hash',
        'expires_at',
        'used_at',
        'used_from_ip',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
    ];

    /**
     * Get the RVM machine for this session.
     */
    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class);
    }

    /**
     * Get the technician who created this session.
     */
    public function technician(): BelongsTo
    {
        return $this->belongsTo(User::class, 'technician_id');
    }

    /**
     * Check if session is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if session has been used.
     */
    public function isUsed(): bool
    {
        return $this->used_at !== null;
    }

    /**
     * Check if session is valid (not expired and not used).
     */
    public function isValid(): bool
    {
        return !$this->isExpired() && !$this->isUsed();
    }

    /**
     * Verify PIN against hash.
     */
    public function verifyPin(string $pin): bool
    {
        return Hash::check($pin, $this->pin_hash);
    }

    /**
     * Mark session as used.
     */
    public function markAsUsed(?string $ip = null): void
    {
        $this->update([
            'used_at' => now(),
            'used_from_ip' => $ip,
        ]);
    }

    /**
     * Generate a new PIN (returns plain PIN, stores hash).
     */
    public static function generatePin(int $rvmMachineId, int $technicianId, int $expiryMinutes = 60): array
    {
        // Generate 6-digit PIN
        $pin = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $session = self::create([
            'rvm_machine_id' => $rvmMachineId,
            'technician_id' => $technicianId,
            'pin_hash' => Hash::make($pin),
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        return [
            'session_id' => $session->id,
            'pin' => $pin, // Only returned once, never stored
            'expires_at' => $session->expires_at->toIso8601String(),
        ];
    }
}
