<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FallidasController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {

        route::get('/produccion/fallidas', [FallidasController::class, 'index'])->name('produccion.fallidas')->middleware(CheckPermission::class . ':ver_residente');
      
    });
});