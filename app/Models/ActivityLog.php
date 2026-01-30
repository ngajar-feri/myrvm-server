<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Jenssegers\Agent\Agent;

class ActivityLog extends Model
{
    use HasFactory;

    protected $table = 'activity_logs';

    protected $fillable = [
        'user_id',
        'module',
        'action',
        'description',
        'ip_address',
        'user_agent',
        'browser',
        'browser_version',
        'platform',
        'device',
        'device_name',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the activity log.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope to filter by module.
     */
    public function scopeModule($query, $module)
    {
        return $query->where('module', $module);
    }

    /**
     * Scope to filter by action.
     */
    public function scopeAction($query, $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by date range.
     */
    public function scopeDateBetween($query, $from, $to)
    {
        if ($from) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to) {
            $query->whereDate('created_at', '<=', $to);
        }
        return $query;
    }

    /**
     * Log an activity with device tracking.
     * 
     * Captures: IP address, browser, OS, device type, and device name
     * from the User-Agent header for comprehensive audit trail.
     */
    public static function log($module, $action, $description = null, $userId = null)
    {
        $agent = new Agent();
        $userAgent = request()->userAgent();
        $agent->setUserAgent($userAgent);

        // Determine device type
        $deviceType = 'desktop';
        if ($agent->isTablet()) {
            $deviceType = 'tablet';
        } elseif ($agent->isMobile()) {
            $deviceType = 'phone';
        }

        return self::create([
            'user_id' => $userId ?? auth()->id(),
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => $userAgent,
            'browser' => $agent->browser() ?: null,
            'browser_version' => $agent->version($agent->browser()) ?: null,
            'platform' => $agent->platform() ?: null,
            'device' => $deviceType,
            'device_name' => $agent->device() ?: null,
        ]);
    }
}
