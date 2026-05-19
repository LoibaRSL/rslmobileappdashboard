<?php

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Permission check directive
        Blade::directive('can', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->hasPermission({$expression})): ?>";
        });
        
        Blade::directive('endcan', function () {
            return "<?php endif; ?>";
        });
        
        // Role check directive
        Blade::directive('role', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->hasRole({$expression})): ?>";
        });
        
        Blade::directive('endrole', function () {
            return "<?php endif; ?>";
        });
        
        // Multiple permissions check (any)
        Blade::directive('canany', function ($expression) {
            return "<?php if (auth()->check() && auth()->user()->hasAnyPermission({$expression})): ?>";
        });
        
        Blade::directive('endcanany', function () {
            return "<?php endif; ?>";
        });
    }
}