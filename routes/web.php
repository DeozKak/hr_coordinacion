<?php

use App\Http\Controllers\ProduccionController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckPermission;
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
Route::middleware('auth')->group(function () {
Route::get('/home', [HomeController::class, 'index'])->name('home');

// rutas para perfil y modificar datos -----------------------------------------------------------
Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('update');
Route::get('changePassword/{user}', [UserController::class, 'changePassword'])->name('changePassword');
Route::put('uptadePassword/{user}', [UserController::class, 'updatePassword'])->name('updatePassword');

//rutas cargues tareas----------------------------------------------------------------------------
Route::get('/load', [AsignadasController::class, 'index'])->name('cargues.load')->middleware(CheckPermission::class.':cargue_tareas');
Route::post('/store', [AsignadasController::class, 'store'])->name('cargues.store')->middleware(CheckPermission::class.':cargue_tareas');

//Rutas Gestion-----------------------------------------------------------------------------------
Route::get('/gestion/coordinacion', [CoordinacionController::class, 'coordinacion'])->name('coordinacion')->middleware(CheckPermission::class.':ver_coordinacion_RP');
Route::get('/gestion/getdataCoordinacionRP', [CoordinacionController::class, 'getdataCoordinacionRP'])->name('getdataCoordinacionRP')->middleware(CheckPermission::class.':ver_coordinacion_RP');
Route::post('/gestion/filterData', [CoordinacionController::class, 'filterData'])->name('filterData')->middleware(CheckPermission::class.':ver_coordinacion_RP');

//Rutas para bitacoras-----------------------------------------------------------------------------
Route::get('/bitacora', [BitacoraController::class, 'ver'])->name('bitacora')->middleware(CheckPermission::class.':generar_bitacoras');
Route::post('/generar_bitacora', [BitacoraController::class, 'generar_bitacora'])->name('bitacoras.generar')->middleware(CheckPermission::class.':generar_bitacoras');
Route::post('/guardar_tabla/{super}', [BitacoraController::class, 'guardar_tabla'])->name('bitacoras.guardar_tabla')->middleware(CheckPermission::class.':generar_bitacoras');
Route::post('/borrar_archivos', [BitacoraController::class, 'borrar_archivos'])->name('bitacoras.borrar_archivos')->middleware(CheckPermission::class.':generar_bitacoras');
Route::post('/storage/app/uploads/{file}', function($nombreArchivo){
    return response()->download(storage_path('app/uploads/').$nombreArchivo);
})->name('bitacoras.download')->middleware(CheckPermission::class.':ver_bitacoras');
Route::get('/bitacora/devoluciones', [BitacoraController::class, 'devoluciones'])->name('bitacora.devoluciones')->middleware(CheckPermission::class.':ver_bitacoras');
Route::post('/bitacora/exportar_devoluciones', [BitacoraController::class, 'exportar_tabla_devoluciones'])->name('bitacora.exportar_devoluciones')->middleware(CheckPermission::class.':ver_bitacoras');
Route::get('/bitacora/reportes',[BitacoraController::class, 'reportes'])->name('bitacoras.reportes')->middleware(CheckPermission::class.':ver_bitacoras');
Route::post('/bitacora/ver_reporte/{id_bitacora}', [BitacoraController::class, 'verReporte'])->name('bitacoras.ver_reporte')->middleware(CheckPermission::class.':ver_bitacoras');
Route::get('bitacora/consultar_reporte/{id_bitacora}', [BitacoraController::class, 'consultaReporte'])->name('bitacoras.consulta_reporte')->middleware(CheckPermission::class.':ver_bitacoras');
Route::get('bitacora/consultar_indicadores/{id_bitacora}', [BitacoraController::class, 'ConsultaIndicadores'])->name('bitacoras.Consulta_indicadores')->middleware(CheckPermission::class.':ver_bitacoras');

//Rutas para inspectores----------------------------------------------------------------------------
Route::get('/inspectores', [InspectorController::class, 'index'])->name('inspectores.index')->middleware(CheckPermission::class.':gestion_inspectores');
Route::get('/inspectores/create', [InspectorController::class, 'create'])->name('inspectores.create')->middleware(CheckPermission::class.':gestion_inspectores');
Route::post('/inspectores/store', [InspectorController::class, 'store'])->name('inspectores.store')->middleware(CheckPermission::class.':gestion_inspectores');
Route::get('/inspectores/edit/{inspector}', [InspectorController::class, 'edit'])->name('inspectores.edit')->middleware(CheckPermission::class.':gestion_inspectores');
Route::put('/inspectores/update/{inspector}', [InspectorController::class, 'update'])->name('inspectores.update')->middleware(CheckPermission::class.':gestion_inspectores');
Route::post('/inspectores/change_state/{inspector}', [InspectorController::class, 'change_state'])->name('inspectores.change_state')->middleware(CheckPermission::class.':gestion_inspectores');
Route::get('/inspectores/show_disabled', [InspectorController::class, 'show_disabled'])->name('inspectores.show_disabled')->middleware(CheckPermission::class.':gestion_inspectores');

//Rutas para Producción
Route::get('/produccion', [ProduccionController::class, 'index'])->name('produccion.index')->middleware(CheckPermission::class.':ver_residente');
Route::get('/produccion/detalles',[ProduccionController::class,'detalles'])->name('produccion.detalles')->middleware(CheckPermission::class.':ver_residente');
Route::get('/produccion/data',[ProduccionController::class,'datosDetalles'])->name('produccion.datosDetalles')->middleware(CheckPermission::class.':ver_residente');
Route::get('/produccion/detalles_diario/{fecha}/{inspector}',[ProduccionController::class,'detallesDiario'])->name('produccion.detallesDiario')->middleware(CheckPermission::class.':ver_residente');
Route::post('/produccion/detalles_diario/actualizar/{id}',[ProduccionController::class,'ActualizarDetallesDiario'])->name('produccion.ActualizarDetallesDiario')->middleware(CheckPermission::class.':ver_residente');
Route::post('/produccion/detalles_diario/desasociar/{id}',[ProduccionController::class,'eliminarDetallesDiario'])->name('produccion.eliminarDetallesDiario')->middleware(CheckPermission::class.':ver_residente');
Route::post('/produccion/detalles_diario/insertar',[ProduccionController::class,'insertarContrato'])->name('produccion.insertarContrato')->middleware(CheckPermission::class.':ver_residente');
Route::get('/produccion/detalles_diario/bitacora/{fecha}/{ccOperario}',[ProduccionController::class,'consultarBitacora'])->name('produccion.bitacora')->middleware(CheckPermission::class.':ver_residente');
});