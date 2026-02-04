<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DatasetImageRaw extends Model
{
    protected $table = 'dataset_images_raw';

    protected $fillable = [
        'rvm_machine_id',
        'file_path',
        'filename',
        'camera_port',
        'captured_at'
    ];

    protected $casts = [
        'captured_at' => 'datetime',
    ];

    public function rvmMachine()
    {
        return $this->belongsTo(RvmMachine::class);
    }
}
