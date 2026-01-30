<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TelemetryData extends Model
{
    protected $fillable = [
        'rvm_machine_id',
        'plastic_weight',
        'aluminum_weight',
        'glass_weight',
        'total_items',
        'battery_level',
        'temperature'
    ];

    public function rvmMachine(): BelongsTo
    {
        return $this->belongsTo(RvmMachine::class);
    }
}
