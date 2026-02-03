<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EdgeShellCommand extends Model
{
    protected $table = 'edge_shell_commands';
    
    protected $fillable = [
        'label',
        'command',
        'category',
        'description',
        'is_dangerous',
    ];
    
    protected $casts = [
        'is_dangerous' => 'boolean',
    ];
}
