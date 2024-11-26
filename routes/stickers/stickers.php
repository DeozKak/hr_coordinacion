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
        route::get('bitacora/stickers', [StickersController::class, 'index'])->name('bitacora.stickers')->middleware(CheckPermission::class . ':generar_bitacoras');



    });
    
});