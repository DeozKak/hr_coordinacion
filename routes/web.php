<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DescargasController;
use App\Http\Controllers\UserActivityController;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');
        Route::get('/jobs-pnd', function () {

            $job = DB::table('job_status')->where('status', 'running')->first();

            if (!$job) {
                return response()->json(['percentage' => null]); // No hay jobs corriendo
            }

            if ($job->total == 0 || $job->processed == 0) {
                $percentage = 0; // Prevenir divisiones inválidas si no tiene datos todavía
            } else {
                $percentage = round(($job->processed / $job->total) * 100);
            }

            return response()->json([
                'percentage' => $percentage,
                'status' => $job->status,
                'details' => $job->details,
            ]);


        })->name('jobs.pnd');


        Route::get('/descargar-archivo', [DescargasController::class, 'descargarArchivo'])
            ->name('descargar.archivo')
            ->middleware('signed'); // Validar que la URL está firmada


        // Si quieres una ruta para listar usuarios y desde ahí acceder a su actividad
        Route::get('/admin/users-activity', [UserActivityController::class, 'listUsers'])->name('admin.users.activity.list');

        // Ruta para ver la actividad de un usuario específico
        Route::get('/admin/user/{user}/activity', [UserActivityController::class, 'showUserActivity'])->name('admin.user.activity.show');


        // Ruta para la actividad de Spatie de un usuario
        Route::get('/admin/user/{user}/http-activity', [UserActivityController::class, 'showUserSpatieActivity'])
            ->name('admin.user.http_activity.show'); // Nombre de ruta diferente

        Route::get('/admin/fetch-global-audits', [UserActivityController::class, 'fetchGlobalAudits'])->name('admin.global_audit.fetch');


    });
});
