<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EdgeTelemetry extends Model
{
    protected $table = 'edge_telemetry';

    protected $fillable = [
        'edge_device_id',
        'client_timestamp',
        'server_timestamp',
        'sensor_data',
        'device_stats',
        'sync_status',
        'sync_attempts',
        'batch_id',
    ];

    protected $casts = [
        'client_timestamp' => 'datetime',
        'server_timestamp' => 'datetime',
        'sensor_data' => 'array',
        'device_stats' => 'array',
    ];

    /**
     * Sync status constants
     */
    const SYNC_SYNCED = 'synced';
    const SYNC_PENDING = 'pending';
    const SYNC_FAILED = 'failed';

    /**
     * Get the edge device that owns this telemetry record.
     */
    public function edgeDevice(): BelongsTo
    {
        return $this->belongsTo(EdgeDevice::class);
    }

    /**
     * Scope: Get pending sync records
     */
    public function scopePending($query)
    {
        return $query->where('sync_status', self::SYNC_PENDING);
    }

    /**
     * Scope: Get records by batch
     */
    public function scopeByBatch($query, string $batchId)
    {
        return $query->where('batch_id', $batchId);
    }

    /**
     * Mark as synced
     */
    public function markAsSynced(): bool
    {
        return $this->update([
            'sync_status' => self::SYNC_SYNCED,
            'server_timestamp' => now(),
        ]);
    }

    /**
     * Mark as failed with increment attempts
     */
    public function markAsFailed(): bool
    {
        return $this->update([
            'sync_status' => self::SYNC_FAILED,
            'sync_attempts' => $this->sync_attempts + 1,
        ]);
    }
}
