<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\DescargasController;

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
            return response()->json(DB::table('jobs')->where('queue', '=','Asignacion_tec')
            ->count());
         })->name('jobs.pnd');
    });

    Route::get('/descargar-archivo', [DescargasController::class, 'descargarArchivo'])
        ->name('descargar.archivo')
        ->middleware('signed'); // Validar que la URL está firmada


});
