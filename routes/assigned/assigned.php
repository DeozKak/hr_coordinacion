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
    });
});
