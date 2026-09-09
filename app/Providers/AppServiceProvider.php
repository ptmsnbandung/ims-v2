<?php

namespace App\Providers;

use App\Auth\LegacyEloquentUserProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

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
        Auth::provider('legacy_eloquent', function ($app, array $config) {
            return new LegacyEloquentUserProvider($app['hash'], $config['model']);
        });
    }
}
