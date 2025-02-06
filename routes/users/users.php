<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
        Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('update');
        Route::get('changePassword/{user}', [UserController::class, 'changePassword'])->name('changePassword');
        Route::put('uptadePassword/{user}', [UserController::class, 'updatePassword'])->name('updatePassword');
        Route::post('/profile/getDataPermissions', [UserController::class, 'getDataPermissions'])->name('profile.getDataPermissions');
    });
});
