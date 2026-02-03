<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CvModel extends Model
{
    protected $table = 'cv_models';
    
    protected $fillable = [
        'name',
        'slug',
        'download_url',
        'local_path',
        'status',
        'size_bytes',
    ];
    
    protected $casts = [
        'size_bytes' => 'integer',
    ];
}
