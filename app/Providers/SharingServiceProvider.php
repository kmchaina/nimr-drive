<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class SharingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(\App\Services\SharingService::class, function ($app) {
            return new \App\Services\SharingService();
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
