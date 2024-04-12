<?php

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AsignadasController;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::get('/load',[AsignadasController::class, 'index'])->name('asignadas.load');
Route::post('/store',[AsignadasController::class, 'store'])->name('asignadas.store');