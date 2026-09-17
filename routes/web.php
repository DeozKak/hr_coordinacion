<?php
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CausalesLegalizacionController;
use App\Http\Controllers\HomeController;
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DescargasController;
use App\Http\Controllers\UserActivityController;
use App\Services\ExtraerFechas;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        Route::post('/insercion_estadisticas_asignacion', [HomeController::class, 'insercion_estadisticas_asignacion'])
            ->name('insercion_estadisticas_asignacion')
            ->middleware(CheckPermission::class . ':ver_residente');

        Route::get('/home/reporte', [HomeController::class, 'reporte'])->name('home.reporte');
        Route::get('/home/programaciones', [HomeController::class, 'programaciones'])->name('home.programaciones');
        Route::post('/estado-asignacion/guardar-tecnicos', [HomeController::class, 'guardarAsignacion'])
            ->name('asignacion.guardar_tecnicos')
            ->middleware(CheckPermission::class . ':ver_coordinacion_RP,ver_residente');
        Route::post('/corte-gdo', [HomeController::class, 'guardarCorte'])
            ->name('corte.guardar')
            ->middleware(CheckPermission::class . ':ver_residente');

        /* Causales que cuentan como legalización. Mismo permiso que los cortes
           de producción: es quien lleva la legalización quien las conoce. */
        Route::middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP')->group(function () {
            Route::get('/causales-legalizacion', [CausalesLegalizacionController::class, 'index'])
                ->name('causales.index');
            Route::post('/causales-legalizacion', [CausalesLegalizacionController::class, 'store'])
                ->name('causales.store');
            Route::post('/causales-legalizacion/{causal}/alternar', [CausalesLegalizacionController::class, 'alternar'])
                ->name('causales.alternar');
            Route::delete('/causales-legalizacion/{causal}', [CausalesLegalizacionController::class, 'destroy'])
                ->name('causales.destroy');
        });
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


        })->name('jobs.pnd')->middleware(CheckPermission::class . ':ver_programacion');


        Route::get('/descargar-archivo', [DescargasController::class, 'descargarArchivo'])
            ->name('descargar.archivo')
            ->middleware('signed'); // Validar que la URL está firmada


        /* Auditoría: quién cambió qué, con los valores antes y después, sus IP
           y su navegación. Sólo pedían sesión, así que cualquier usuario —un
           inspector sin un solo permiso— podía leer la de todos. Se exige el
           mismo permiso con el que el menú de configuración enseña el enlace. */
        Route::middleware(CheckPermission::class . ':gestion_usuarios')->group(function () {
            Route::get('/admin/users-activity', [UserActivityController::class, 'listUsers'])->name('admin.users.activity.list');
            Route::get('/admin/user/{user}/activity', [UserActivityController::class, 'showUserActivity'])->name('admin.user.activity.show');
            Route::get('/admin/user/{user}/http-activity', [UserActivityController::class, 'showUserSpatieActivity'])
                ->name('admin.user.http_activity.show');
            Route::get('/admin/fetch-global-audits', [UserActivityController::class, 'fetchGlobalAudits'])->name('admin.global_audit.fetch');
        });


    });
    // Ruta para verificación (GET) y recepción de eventos (POST)
});
