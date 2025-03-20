<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use App\Http\Middleware\CheckUserStatus;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
       web: [
            __DIR__ . '/../routes/web.php',
            __DIR__ . '/../routes/users/users.php',
            __DIR__ . '/../routes/assigned/assigned.php',
            __DIR__ . '/../routes/autosave/autosave.php',
            __DIR__ . '/../routes/coordination/coordination.php',
            __DIR__ . '/../routes/inspector/inspector.php',
            __DIR__ . '/../routes/log/log.php',
            __DIR__ . '/../routes/notifications/notifications.php',
            __DIR__ . '/../routes/payroll/payroll.php',
            __DIR__ . '/../routes/production/production.php',
            __DIR__ . '/../routes/productionCut/productionCut.php',
            __DIR__ . '/../routes/productionReport/productionReport.php',
            __DIR__ . '/../routes/scheduling/scheduling.php',
            __DIR__ . '/../routes/users/users.php',
            __DIR__ . '/../routes/stickers/stickers.php',
            __DIR__ . '/../routes/failed_visits/failed_visits.php',
           __DIR__ . '/../routes/zoning/zoning.php',
        ],
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
       )
    ->withMiddleware(callback: function (Middleware $middleware) {
        $middleware->group('public', []);
        $middleware->group('auth', [CheckUserStatus::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
