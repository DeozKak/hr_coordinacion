<?php

use App\Http\Controllers\Bitacoras\BitacoraController;
use App\Http\Middleware\CheckPermission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });

    Route::middleware('auth')->group(function () {
        Route::get('/bitacora', [BitacoraController::class, 'ver'])->name('bitacora')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/generar_bitacora', [BitacoraController::class, 'generar_bitacora'])->name('bitacoras.generar')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/guardar_tabla/{super?}', [BitacoraController::class, 'guardar_tabla'])->name('bitacoras.guardar_tabla')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/borrar_archivos', [BitacoraController::class, 'borrar_archivos'])->name('bitacoras.borrar_archivos')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::get('/storage/app/uploads/{file}', [BitacoraController::class, 'download'])->name('bitacoras.download')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/devoluciones', [BitacoraController::class, 'devoluciones'])->name('bitacora.devoluciones')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('/bitacora/exportar_devoluciones', [BitacoraController::class, 'exportar_tabla_devoluciones'])->name('bitacora.exportar_devoluciones')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/reportes', [BitacoraController::class, 'reportes'])->name('bitacoras.reportes')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/ver_reporte/{id_bitacora}', [BitacoraController::class, 'verReporte'])->name('bitacoras.ver_reporte')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('/bitacora/reportes/devoluciones/{ids}/{bitacora}', [BitacoraController::class, 'devolver'])->name('bitacoras.devolver')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('bitacora/consultar_reporte/{id_bitacora}', [BitacoraController::class, 'consultaReporte'])->name('bitacoras.consulta_reporte')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('bitacora/consultar_indicadores/{id_bitacora}', [BitacoraController::class, 'ConsultaIndicadores'])->name('bitacoras.Consulta_indicadores')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('bitacora/devoluciones/actualizar/{id}', [BitacoraController::class, 'actualizar_devolucion'])->name('bitacoras.actualizar_devolucion')->middleware(CheckPermission::class . ':mod_devoluciones');
        Route::get('bitacoras/buscar_por_contrato', [BitacoraController::class, 'buscarPorContrato'])->name('bitacoras.buscar_por_contrato')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('municipios/json', [BitacoraController::class, 'getMunicipiosJson'])->name('municipios.json')->middleware(CheckPermission::class . ':ver_bitacoras,generar_programacion');
    });
});
