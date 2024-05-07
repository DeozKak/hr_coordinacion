<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AsignadasController;
use App\Http\Controllers\CoordinacionController;
use App\Http\Controllers\BitacoraController;
use App\Http\Controllers\InspectorController;

Route::get('/', function () {
    return view('auth.login');
});

Auth::routes();

Route::get('/home', [HomeController::class, 'index'])->name('home');

// rutas para perfil y modificar datos -----------------------------------------------------------
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('update');
Route::get('changePassword/{user}', [UserController::class, 'changePassword'])->name('changePassword');
Route::put('uptadePassword/{user}', [UserController::class, 'updatePassword'])->name('updatePassword');

//rutas cargues tareas----------------------------------------------------------------------------
Route::get('/load', [AsignadasController::class, 'index'])->name('cargues.load');
Route::post('/store', [AsignadasController::class, 'store'])->name('cargues.store');

//Rutas Gestion-----------------------------------------------------------------------------------
Route::get('/gestion/coordinacion', [CoordinacionController::class, 'coordinacion'])->name('coordinacion');
Route::get('/gestion/getdataCoordinacionRP', [CoordinacionController::class, 'getdataCoordinacionRP'])->name('getdataCoordinacionRP');
Route::post('/gestion/filterData', [CoordinacionController::class, 'filterData'])->name('filterData');

//Rutas para bitacoras-----------------------------------------------------------------------------
Route::get('/bitacora', [BitacoraController::class, 'ver'])->name('bitacora');
Route::post('/generar_bitacora', [BitacoraController::class, 'generar_bitacora'])->name('bitacoras.generar');
Route::post('/guardar_tabla/{super}', [BitacoraController::class, 'guardar_tabla'])->name('bitacoras.guardar_tabla');
Route::post('/borrar_archivos', [BitacoraController::class, 'borrar_archivos'])->name('bitacoras.borrar_archivos');
Route::get('/storage/app/uploads/{file}', function($nombreArchivo){
    return response()->download(storage_path('app/uploads/').$nombreArchivo);
})->name('bitacoras.download');
Route::get('/bitacora/devoluciones', [BitacoraController::class, 'devoluciones'])->name('bitacora.devoluciones');
Route::post('/bitacora/exportar_devoluciones', [BitacoraController::class, 'exportar_tabla_devoluciones'])->name('bitacora.exportar_devoluciones');

//Rutas para inspectores----------------------------------------------------------------------------
Route::get('/inspectores', [InspectorController::class, 'index'])->name('inspectores.index');
Route::get('/inspectores/create', [InspectorController::class, 'create'])->name('inspectores.create');
Route::post('/inspectores/store', [InspectorController::class, 'store'])->name('inspectores.store');
Route::get('/inspectores/edit/{inspector}', [InspectorController::class, 'edit'])->name('inspectores.edit');
Route::put('/inspectores/update/{inspector}', [InspectorController::class, 'update'])->name('inspectores.update');
Route::post('/inspectores/change_state/{inspector}', [InspectorController::class, 'change_state'])->name('inspectores.change_state');
Route::get('/inspectores/show_disabled', [InspectorController::class, 'show_disabled'])->name('inspectores.show_disabled');
