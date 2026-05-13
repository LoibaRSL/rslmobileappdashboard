<?php

namespace App\Providers;

use App\Services\WSO2Service;
use Illuminate\Support\ServiceProvider;

class WSO2ServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WSO2Service::class, function ($app) {
            return new WSO2Service();
        });
    }

    public function boot(): void
    {
        //
    }
}