<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class EdgeDevice extends Model
{
    use SoftDeletes;
    /**
     * Edge Device model - represents hardware installed in RVM machines.
     * Columns based on 2026_01_08 + 2026_01_14 migrations.
     */
    protected $fillable = [
        // Identity
        'rvm_machine_id',
        'device_id',
        'location_name',
        'inventory_code',
        'description',

        // Network (auto-updated via heartbeat)
        'ip_address_local',
        'tailscale_ip',
        'network_interfaces',

        // Hardware config
        'type',
        'controller_type',
        'camera_id',
        'threshold_full',
        'firmware_version',
        'health_metrics',

        // AI
        'ai_model_version',

        // Status
        'status',
        'api_key',

        // Geolocation
        'latitude',
        'longitude',
        'address',

        // Handshake data (from Setup Wizard)
        'hardware_config',
        'system_info',
        'diagnostics_log',
        'timezone',
        'last_handshake_at',
    ];

    protected $casts = [
        'health_metrics' => 'array',
        'network_interfaces' => 'array',
        'hardware_config' => 'array',
        'system_info' => 'array',
        'diagnostics_log' => 'array',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'threshold_full' => 'integer',
        'last_handshake_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    /**
     * Get the RVM machine this edge device is installed in.
     */
    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class, 'rvm_machine_id');
    }

    /**
     * Get telemetry records for this edge device.
     */
    public function telemetry(): HasMany
    {
        return $this->hasMany(EdgeTelemetry::class);
    }

    /**
     * Check if device is online (heartbeat within last 5 minutes).
     */
    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    /**
     * Get formatted IP display.
     */
    public function getDisplayIpAttribute(): string
    {
        return $this->tailscale_ip ?: $this->ip_address_local ?: 'N/A';
    }
}
