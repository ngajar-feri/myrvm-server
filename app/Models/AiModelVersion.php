<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiModelVersion extends Model
{
    protected $fillable = [
        'model_name',
        'version',
        'file_path',
        'file_size_mb',
        'sha256_hash',
        'training_job_id',
        'metrics',
        'is_active',
        'deployed_at',
        'deployment_notes',
    ];

    protected $casts = [
        'metrics' => 'array',
        'is_active' => 'boolean',
        'deployed_at' => 'datetime',
        'file_size_mb' => 'float',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByModelName($query, $modelName)
    {
        return $query->where('model_name', $modelName);
    }
}
