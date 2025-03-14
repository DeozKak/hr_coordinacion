<?php
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AsignadasController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/load', [AsignadasController::class, 'index'])->name('cargues.load')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::post('/store', [AsignadasController::class, 'store'])->name('cargues.store')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::post('/storeClosed', [AsignadasController::class, 'storeClosed'])->name('cargues.storeClosed')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::get('/receptionLoad',[AsignadasController::class, 'receptionLoad'])->name('load.receptionLoad')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::post('/receptionLoad/store',[AsignadasController::class, 'receptionStore'])->name('load.receptionStore')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::get('/gestion/viewReception', [AsignadasController::class, 'getReceptions'])->middleware(CheckPermission::class . ':ver_coordinacion_RN');
        Route::get('/gestion/reception', [AsignadasController::class, 'getDataReception'])->name('management.reception')->middleware(CheckPermission::class . ':ver_coordinacion_RN');
        Route::get('/gestion/filterDataReception', [AsignadasController::class, 'filterData'])->name('management.filterDataReception')->middleware(CheckPermission::class . ':ver_coordinacion_RN');
        
    });
});
