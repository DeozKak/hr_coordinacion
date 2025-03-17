<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NotificationsController;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('notifications/get', [NotificationsController::class, 'getNotificationsData'])->name('notifications.get');
        Route::get('notifications/markAsRead', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
        Route::get('notifications/markAllAsRead', [NotificationsController::class, 'markAllAsRead'])->name('notifications.markAllAsRead');
        Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');
    });
});
