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
use App\Http\Controllers\CorteProduccionController;
use App\Http\Controllers\NotificationsController;
use App\Http\Controllers\ProgramacionController;
use Illuminate\Http\Request;


Route::get('/', function () {
    return redirect()->route('home');
});
Route::middleware('web')->group(function () {

    Route::middleware('throttle:60,1')->group(function () {
        Auth::routes();
    });
    
    Route::middleware('auth')->group(function () {
        Route::get('/home', [HomeController::class, 'index'])->name('home');

        // rutas para perfil y modificar datos -----------------------------------------------------------
        Route::get('/profile', [UserController::class, 'showProfile'])->name('profile.show');
        Route::get('/profile/edit', [UserController::class, 'editProfile'])->name('profile.edit');
        Route::put('/profile/{user}', [UserController::class, 'updateProfile'])->name('update');
        Route::get('changePassword/{user}', [UserController::class, 'changePassword'])->name('changePassword');
        Route::put('uptadePassword/{user}', [UserController::class, 'updatePassword'])->name('updatePassword');

        //rutas cargues tareas----------------------------------------------------------------------------
        Route::get('/load', [AsignadasController::class, 'index'])->name('cargues.load')->middleware(CheckPermission::class . ':cargue_tareas');
        Route::post('/store', [AsignadasController::class, 'store'])->name('cargues.store')->middleware(CheckPermission::class . ':cargue_tareas');

        //Rutas Gestion--------------------------------------------------------------------------------------------------------------------------
        
        Route::get('/gestion/coordinacion', [CoordinacionController::class, 'coordinacion'])->name('coordinacion')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::get('/gestion/getdataCoordinacionRP', [CoordinacionController::class, 'getdataCoordinacionRP'])->name('getdataCoordinacionRP')->middleware(CheckPermission::class . ':ver_coordinacion_RP');
        Route::post('/gestion/filterData', [CoordinacionController::class, 'filterData'])->name('filterData')->middleware(CheckPermission::class . ':ver_coordinacion_RP');

        //Rutas para bitacoras-----------------------------------------------------------------------------------------------------------------
        Route::get('/bitacora', [BitacoraController::class, 'ver'])->name('bitacora')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/generar_bitacora', [BitacoraController::class, 'generar_bitacora'])->name('bitacoras.generar')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/guardar_tabla/{super?}', [BitacoraController::class, 'guardar_tabla'])->name('bitacoras.guardar_tabla')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::post('/borrar_archivos', [BitacoraController::class, 'borrar_archivos'])->name('bitacoras.borrar_archivos')->middleware(CheckPermission::class . ':generar_bitacoras');
        Route::get('/storage/app/uploads/{file}',[BitacoraController::class, 'download'])->name('bitacoras.download')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/devoluciones', [BitacoraController::class, 'devoluciones'])->name('bitacora.devoluciones')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('/bitacora/exportar_devoluciones', [BitacoraController::class, 'exportar_tabla_devoluciones'])->name('bitacora.exportar_devoluciones')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/reportes', [BitacoraController::class, 'reportes'])->name('bitacoras.reportes')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('/bitacora/ver_reporte/{id_bitacora}', [BitacoraController::class, 'verReporte'])->name('bitacoras.ver_reporte')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('/bitacora/reportes/devoluciones/{ids}/{bitacora}', [BitacoraController::class, 'devolver'])->name('bitacoras.devolver')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('bitacora/consultar_reporte/{id_bitacora}', [BitacoraController::class, 'consultaReporte'])->name('bitacoras.consulta_reporte')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('bitacora/consultar_indicadores/{id_bitacora}', [BitacoraController::class, 'ConsultaIndicadores'])->name('bitacoras.Consulta_indicadores')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::post('bitacora/devoluciones/actualizar/{id}', [BitacoraController::class, 'actualizar_devolucion'])->name('bitacoras.actualizar_devolucion')->middleware(CheckPermission::class . ':mod_devoluciones');
        Route::get('bitacoras/buscar_por_contrato', [BitacoraController::class, 'buscarPorContrato'])->name('bitacoras.buscar_por_contrato')->middleware(CheckPermission::class . ':ver_bitacoras');
        Route::get('municipios/json', [BitacoraController::class, 'getMunicipiosJson'])->name('municipios.json')->middleware(CheckPermission::class . ':ver_bitacoras');
        //Rutas para inspectores-----------------------------------------------------------------------------------------------------------------
        
        Route::get('/inspectores', [InspectorController::class, 'index'])->name('inspectores.index')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::get('/inspectores/create', [InspectorController::class, 'create'])->name('inspectores.create')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('/inspectores/store', [InspectorController::class, 'store'])->name('inspectores.store')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::get('/inspectores/edit/{inspector}', [InspectorController::class, 'edit'])->name('inspectores.edit')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::put('/inspectores/update/{inspector}', [InspectorController::class, 'update'])->name('inspectores.update')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::post('/inspectores/change_state/{inspector}', [InspectorController::class, 'change_state'])->name('inspectores.change_state')->middleware(CheckPermission::class . ':gestion_inspectores');
        Route::get('/inspectores/show_disabled', [InspectorController::class, 'show_disabled'])->name('inspectores.show_disabled')->middleware(CheckPermission::class . ':gestion_inspectores');

        //Rutas para Producción
        Route::get('/produccion/detalles_corte/{id}', [ProduccionController::class, 'detallesCorte'])->name('produccion.detallesCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion', [ProduccionController::class, 'index'])->name('produccion.index')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion/detalles', [ProduccionController::class, 'detalles'])->name('produccion.detalles')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::get('/produccion/data', [ProduccionController::class, 'datosDetalles'])->name('produccion.datosDetalles')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::get('/produccion/detalles_diario/{fecha}/{inspector}', [ProduccionController::class, 'detallesDiario'])->name('produccion.detallesDiario')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        Route::post('/produccion/detalles_diario/actualizar/{id}', [ProduccionController::class, 'ActualizarDetallesDiario'])->name('produccion.ActualizarDetallesDiario')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/desasociar/{id}', [ProduccionController::class, 'eliminarDetallesDiario'])->name('produccion.eliminarDetallesDiario')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/insertar', [ProduccionController::class, 'insertarContrato'])->name('produccion.insertarContrato')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/produccion/detalles_diario/bitacora/{fecha}/{ccOperario}', [ProduccionController::class, 'consultarBitacora'])->name('produccion.bitacora')->middleware(CheckPermission::class . ':ver_residente');
        Route::post('/produccion/detalles_diario/diseño_especial/{id}', [ProduccionController::class, 'diseñoEspecial'])->name('produccion.diseñoEspecial')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/obtener-url-detalles', function (Request $request) {
            return route('produccion.detallesDiario', [
                'fecha' => $request->fecha,
                'inspector' => $request->cc_inspector
            ]);
        })->name('obtener-url-detalles');
        Route::get('/obtener-url-bitacoras', function (Request $request) {
            return route('produccion.bitacora', [
                'fecha' => $request->fecha,
                'ccOperario' => $request->cc_inspector
            ]);
        })->name('obtener-url-bitacoras');
        Route::post('/crear-session-corte', [ProduccionController::class, 'crearSession'])->name('produccion.crearSession')->middleware(CheckPermission::class . ':ver_residente');
        //---------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------
        //Rutas Zonas
        Route::get('/produccion/zonas', [ProduccionController::class, 'zonas'])->name('produccion.zonas')->middleware(CheckPermission::class . ':ver_residente,ver_produccion');
        //Rutas Cortes Producción
        Route::get('/cortes_produccion', [CorteProduccionController::class, 'index'])->name('cortes_produccion.index')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Corte', [CorteProduccionController::class, 'storeCorte'])->name('cortes_produccion.store')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Municipio', [CorteProduccionController::class, 'storeMunicipio'])->name('cortes_produccion.storeMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Sede', [CorteProduccionController::class, 'storeSede'])->name('cortes_produccion.storeSede')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::post('/cortes_produccion/store/Causal', [CorteProduccionController::class, 'storeCausal'])->name('cortes_produccion.storeCausal')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::get('/cortes_producction/{id}/editCorte', [CorteProduccionController::class, 'editCorte'])->name('cortes_produccion.editCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/cortes_producction/{id}/editCausal', [CorteProduccionController::class, 'editCausal'])->name('cortes_produccion.editCausal')->middleware(CheckPermission::class . ':ver_residente');
        Route::put('/cortes_produccion/{id}/updateCausal', [CorteProduccionController::class, 'updateCausal'])->name('cortes_produccion.updateCausal')->middleware(CheckPermission::class . ':ver_residente');
        Route::put('/cortes_produccion/{id}/updateCorte', [CorteProduccionController::class, 'updateCorte'])->name('cortes_produccion.updateCorte')->middleware(CheckPermission::class . ':ver_residente');
        Route::get('/cortes_producction/{id}/editMunicipio', [CorteProduccionController::class, 'editMunicipio'])->name('cortes_produccion.editMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
        Route::put('/cortes_produccion/{id}/updateMunicipio', [CorteProduccionController::class, 'updateMunicipio'])->name('cortes_produccion.updateMunicipio')->middleware(CheckPermission::class . ':ver_residente,ver_coordinacion_RP');
    });
    //Rutas para notificaciones
    Route::get('notifications/get', [NotificationsController::class, 'getNotificationsData'])->name('notifications.get');
    Route::get('notifications/markAsRead', [NotificationsController::class, 'markAsRead'])->name('notifications.markAsRead');
    Route::get('notifications', [NotificationsController::class, 'index'])->name('notifications.index');

    //Rutas Programación
    Route::get('/programacion', [ProgramacionController::class, 'index'])->name('programacion.index');
    Route::get('/programacion/create', [ProgramacionController::class, 'create'])->name('programacion.create');
    Route::post('/programacion/base', [ProgramacionController::class, 'base'])->name('programacion.base');
});

