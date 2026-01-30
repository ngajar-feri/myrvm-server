<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

class RvmMachine extends Model
{
    protected $fillable = [
        'name',
        'uuid',
        'location',
        'serial_number',
        'api_key',
        'status',
        'latitude',
        'longitude',
        'location_address',
        'capacity_percentage',
        'last_ping',
        'last_maintenance',
        'last_model_sync'
    ];

    protected $hidden = [
        'api_key',
    ];

    protected $casts = [
        'uuid' => 'string',
        'last_ping' => 'datetime',
        'last_maintenance' => 'datetime',
        'last_model_sync' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    /**
     * Auto-generate serial_number on creation.
     * Note: api_key is NOT auto-generated here. It will be generated
     * when a Technician is assigned to this machine (via Assignment flow).
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            // Auto-generate UUID
            if (empty($model->uuid)) {
                $model->uuid = Str::uuid()->toString();
            }

            // Auto-generate serial number: RVM-YYYYMM-XXX
            if (empty($model->serial_number)) {
                $count = self::count() + 1;
                $model->serial_number = 'RVM-' . date('Ym') . '-' . str_pad($count, 3, '0', STR_PAD_LEFT);
            }

            // api_key is intentionally NOT auto-generated here.
            // It will be created during Technician Assignment for installation.
        });
    }

    /**
     * Get the edge device installed in this RVM machine.
     * Relationship: 1:1 (One RVM Machine has one Edge Device)
     */
    public function edgeDevice(): HasOne
    {
        return $this->hasOne(EdgeDevice::class, 'rvm_machine_id');
    }

    /**
     * Get assigned technicians via technician_assignments table.
     */
    public function technicians(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'technician_assignments', 'rvm_machine_id', 'technician_id')
            ->withPivot('status', 'assigned_at', 'description')
            ->withTimestamps();
    }

    /**
     * Get telemetry data for this machine.
     */
    public function telemetry(): HasMany
    {
        return $this->hasMany(TelemetryData::class);
    }

    /**
     * Get edge telemetry through edge device.
     */
    public function edgeTelemetry(): HasManyThrough
    {
        return $this->hasManyThrough(
            EdgeTelemetry::class,
            EdgeDevice::class,
            'rvm_machine_id',  // FK on edge_devices
            'edge_device_id', // FK on edge_telemetry
            'id',             // PK on rvm_machines
            'id'              // PK on edge_devices
        );
    }

    /**
     * Get the API key for technician configuration.
     * Only accessible via explicit method call, not in JSON.
     */
    public function getApiKeyForConfig(): string
    {
        return $this->api_key;
    }

    /**
     * Regenerate API key.
     */
    public function regenerateApiKey(): string
    {
        $this->api_key = Str::random(64);
        $this->save();
        return $this->api_key;
    }
}
