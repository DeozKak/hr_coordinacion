<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Route;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Route::group([
            'namespace' => 'admin',
            'middleware' => 'web',
            'as' => 'admin.',
            'prefix' => 'admin',
        ], function ($router) {
            require base_path('routes/admin.php');
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
