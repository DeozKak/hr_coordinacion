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

        Route::get('/prueba-ia', function () {
            // 1. Instanciamos tu servicio
            $extractor = new ExtraerFechas();

            // 2. Colocamos uno de tus textos de prueba reales
            $texto = "CALL CENTER - LUIS ANGULO - CEL: 3225829831  - AUTORIZA NOTIFICACIÓN ELECTRÓNICA: SI  - CORREO: PSERVICOSPUBLICOSOCC@OLIMPICA.COM.CO  SOLICITA RP COSTO $ 238.200  IVA INCLUIDO + RECONEXIÓN $ 44.142  FECHA SUGERIDA: 2026 -MAYO -MARTES 05  JORNADA PM SC  -)  - TIEMPO DE PRESTACIÓN 1 A 4 DH// DIANAMQ";

            $fechaReferencia = date('Y-m-d');
           // dd($texto);
            // 4. Ejecutamos el servicio
            $resultado = $extractor->findDates($texto, $fechaReferencia, 999);

            // 5. Mostramos el resultado en pantalla
            return response()->json([
                'texto_evaluado' => $texto,
                'fecha_referencia' => $fechaReferencia,
                'resultado_ia' => $resultado
            ]);
        });

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
    // Ruta para verificación (GET) y recepción de eventos (POST)
});
