<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\NotificationsController;




Route::middleware(CheckRole::class)->group(function () {
    Route::get('/users', [UserController::class, 'index'])->name('index');
    Route::post('/users', [UserController::class, 'update'])->name('update');
    Route::post('/users/changeStatus/{user}', [UserController::class, 'changeStatus'])->name('changeStatus');
    Route::get('notifications/manage', [NotificationsController::class, 'manage'])->name('notifications.manage');
    Route::post('/notifications/getUserNotifications', [NotificationsController::class, 'getUserNotifications'])->name('notifications.getUserNotifications');
    Route::post('/notifications/update', [NotificationsController::class, 'update'])->name('notifications.update');
    Route::post('/notifications/store', [NotificationsController::class, 'store'])->name('notifications.store');
});
