<?php

namespace App\Providers;

use Laravel\Socialite\Facades\Socialite; // Add this import
use Laravel\Socialite\Contracts\Factory as SocialiteFactory;
use Illuminate\Support\ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;

class SocialiteWSO2ServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->app->make(SocialiteFactory::class)->extend('wso2', function ($app) {
            $config = $app['config']['services.wso2'];
            return Socialite::buildProvider(\App\Services\Socialite\WSO2\WSO2Provider::class, $config);
        });
    }
}
