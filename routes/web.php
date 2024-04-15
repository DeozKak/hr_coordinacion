<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AsignadasController;
use App\Http\Middleware\CheckRole;


Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');
// rutas para perfil y modificar datos ---------------------------------------------------------------------------
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('update');
Route::get('changePassword/{user}', [UserController::class, 'changePassword'])->name('changePassword');
Route::put('uptadePassword/{user}', [UserController::class, 'updatePassword'])->name('updatePassword');
//----------------------------------------------------------------------------------------------------------------
Route::get('/load', [AsignadasController::class, 'index'])->name('asignadas.load');
Route::post('/store', [AsignadasController::class, 'store'])->name('asignadas.store');
