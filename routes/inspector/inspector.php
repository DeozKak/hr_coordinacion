<?php

use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\InspectorController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/inspectores', [InspectorController::class, 'index'])->name('inspectores.index')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::get('/inspectores/create', [InspectorController::class, 'create'])->name('inspectores.create')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('/inspectores/store', [InspectorController::class, 'store'])->name('inspectores.store')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('/inspectores/update', [InspectorController::class, 'update'])->name('inspectores.update')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('/inspectores/change_state/{inspector}', [InspectorController::class, 'change_state'])->name('inspectores.change_state')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::get('/inspectores/show_disabled', [InspectorController::class, 'show_disabled'])->name('inspectores.show_disabled')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('inspectores/getData', [InspectorController::class, 'getDataInspector'])->name('inspector.getData')->middleware(CheckPermission::class . ':gestion_inspectores');
    });
});
