<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Models\User;
use App\Observers\UserObserver;
use Illuminate\Support\Facades\URL;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (\Illuminate\Support\Str::startsWith(config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
        // Paksa HTTPS agar signature valid meskipun di belakang proxy HTTP
        if($this->app->environment('production') || env('APP_ENV') === 'production') {
            URL::forceScheme('https');
        }
        // 2. PERBAIKAN BARU: Paksa Objek Request dianggap HTTPS (Secure)
        // Ini penting untuk validasi signature
        if (isset($this->app['request'])) {
            $this->app['request']->server->set('HTTPS', 'on');
        }
        User::observe(UserObserver::class);
    }
}
