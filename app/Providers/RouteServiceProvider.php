<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
    // Tambahkan ini
    $router = $this->app['router'];
    $router->aliasMiddleware('role', \App\Http\Middleware\RoleMiddleware::class);
    }
}
