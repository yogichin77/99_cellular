<?php

namespace App\Providers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;
use Inertia\Inertia;
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

        Inertia::share([
            'user' => fn() => Auth::check() ? [
                'name' => Auth::user()->name,
                'role' => Auth::user()->role,
                'id' => Auth::user()->id,
            ] : null,
        ]);
    }
}
