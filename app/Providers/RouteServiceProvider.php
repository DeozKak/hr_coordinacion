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
        /* `auth` además de `web`: es el grupo que lleva CheckUserStatus. Sin él,
           un administrador desactivado seguía usando el panel con su sesión
           abierta hasta que alguna petición tocara otra ruta —en la práctica,
           el sondeo de notificaciones, cada 60 s—, y una petición directa a
           estas rutas no pasaba nunca por esa comprobación. */
        Route::group([
            'namespace' => 'admin',
            'middleware' => ['web', 'auth'],
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
