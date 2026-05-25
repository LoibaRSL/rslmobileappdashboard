<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\CheckPermission;
use App\Http\Middleware\DigitalServicesMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {

         // 1. CSRF EXCEPTIONS 
    $middleware->validateCsrfTokens(except: [
        'tin-registration/*',
        'business-registration/*',
        'api/riit/submit',
    ]);

        // Register middleware aliases
        $middleware->alias([
            'wso2.auth' => \App\Http\Middleware\WSO2Authenticate::class,
            'admin' => AdminMiddleware::class,
            'check.permission' => CheckPermission::class,
            'digital.services' => DigitalServicesMiddleware::class,
        ]);
        
        // Add middleware to web group if needed
        $middleware->web(append: [
            // Add any custom web middleware here
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
