<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ============================================
// Scheduled Tasks
// ============================================

// Check for offline Edge Devices every minute
// Marks devices as 'offline' if no heartbeat for > 2 minutes (120 seconds)
Schedule::command('edge:check-offline --threshold=120')
    ->everyMinute()
    ->withoutOverlapping()
    ->runInBackground();
