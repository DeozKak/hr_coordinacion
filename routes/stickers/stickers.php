<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StickersController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });


    Route::middleware( 'auth')->group(function () {
        Route::get('bitacora/stickers', [StickersController::class, 'index'])->name('bitacora.stickers')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::get('bitacora/stickers/ver/{id}', [StickersController::class, 'show'])->name('bitacora.stickers.show')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/stickers/getData/{id}', [StickersController::class, 'getData'])->name('bitacora.stickers.getData')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('bitacora/stickers/update', [StickersController::class, 'update'])->name('bitacora.stickers.update')->middleware(CheckPermission::class . ':generar_bitacoras');
    });
    
});