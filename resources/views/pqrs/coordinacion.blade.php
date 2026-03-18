@extends('adminlte::page')


@section('title', 'Coordinación Quejas')

@section('content_header')
    <h1>Coordinación Quejas</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/pqrs/coordinacion.css')}}">
    <script src="{{ asset('js/PQRS/coordinacionHistorico.js') }}?v={{ time() }}" type="text/javascript"></script>
    <script src="{{ asset('js/PQRS/coordinacion.js') }}?v={{ time() }}" type="text/javascript"></script>
    <script src="{{ asset('js/PQRS/coordinacionPQRSTbl.js') }}?v={{ time() }}" type="text/javascript"></script>
    <input type="hidden" id="url_import" value="{{ route('pqrs.coordinacion.ImportOSF') }}">
    <input type="hidden" id="url_update_asignado" value="{{ route('pqrs.coordinacion.updateAsignado') }}">
    <input type="hidden" id="url_get_historico" value="{{ route('pqrs.coordinacion.historico') }}">
    <input type="hidden" id="url_exportar_gdw" value="{{ route('pqrs.coordinacion.exportarGDW') }}">
    <div class="shadow-container">
        <div class="controls-header">
            <div class="actions-group">
                <button id="openModalBtn" class="btn-gradient btn-gradient-primary btn-sm">Cargar</button>
                <button id="openHistoricoBtn" class="btn-gradient btn-gradient-warning btn-sm">Histórico</button>
                <button id="openExportarGDWBtn" class="btn-gradient btn-gradient-success btn-sm">Exportar a GDW</button>
            </div>
        </div>

        <div class="table-responsive">
            <div id="tabla" class="mt-3" style="position: relative;">
                <!-- tabla coordinacion -->
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="CargarModal" tabindex="-1"
         aria-labelledby="CargarModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <h5 class="modal-title" id="CargarModalLabel">
                        <i class="fas fa-file-upload text-primary"></i>
                        <span>Cargar Quejas Asignadas y Cerradas</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="CargarOSfForm" enctype="multipart/form-data" method="POST"
                          action="{{route('pqrs.coordinacion')}}">
                        @csrf
                        <div class="mb-3">
                            <input type="hidden" name="type" id="type" value="OSF">
                            <label for="archivo" class="form-label">Asignadas:</label>
                            <input type="file" class="form-control" id="archivoAsignadas" name="Asignadas">
                            <br>
                            <input type="hidden" name="type" id="type" value="base">
                            <label for="archivo" class="form-label">Cerradas:</label>
                            <input type="file" class="form-control" id="archivoCerradas" name="Cerradas">
                            <br>
                            <label for="archivosHTML" class="form-label">Soportes HTML (Múltiples):</label>
                            <input type="file" class="form-control" id="archivosHTML" name="archivos_html[]" multiple accept=".html">
                            <br>
                            <div id="loader" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1rem 0 0 0;">
                            <button id="submit-OSF" type="submit" class="btn-gradient btn-gradient-primary">
                                Subir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>



    <!-- NUEVO MODAL HISTÓRICO -->
    <div class="modal fade modal-modern" id="historicoModal" tabindex="-1" aria-labelledby="historicoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl"> <!-- Tamaño extra grande para la tabla -->
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historicoModalLabel">
                        <i class="fas fa-history text-warning"></i> Histórico de Quejas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Filtros -->
                    <form id="formHistorico" class="mb-4">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="hist_orden" class="form-label">Orden</label>
                                <input type="text" class="form-control form-control-sm" id="hist_orden" name="orden">
                            </div>
                            <div class="col-md-3">
                                <label for="hist_contrato" class="form-label">Contrato</label>
                                <input type="text" class="form-control form-control-sm" id="hist_contrato" name="contrato">
                            </div>
                            <div class="col-md-2">
                                <label for="hist_fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control form-control-sm" id="hist_fecha_inicio" name="fecha_inicio">
                            </div>
                            <div class="col-md-2">
                                <label for="hist_fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control form-control-sm" id="hist_fecha_fin" name="fecha_fin">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn-gradient btn-gradient-primary w-100">Buscar</button>
                            </div>
                        </div>
                    </form>

                    <!-- Contenedor para Handsontable del Histórico -->
                    <div class="table-responsive">
                        <div id="tabla_historico" style="position: relative; width: 100%; height: 400px; overflow: hidden;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Nuevo Modal para Ver Más -->
    <div class="modal fade" id="verMasModal" tabindex="-1" aria-labelledby="verMasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verMasModalLabel">
                        <i class="fas fa-info-circle text-primary"></i> Información Completa
                    </h5>
                    <!-- Agregamos ID y quitamos data-bs-dismiss -->
                    <button type="button" class="btn-close" id="btnCerrarVerMasTop" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="verMasContent" style="white-space: pre-wrap; word-break: break-word;"></p>
                </div>
                <div class="modal-footer">
                    <!-- Agregamos ID y quitamos data-bs-dismiss -->
                    <button type="button" class="btn btn-secondary" id="btnCerrarVerMasFooter">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="exportarGDWModal" tabindex="-1" aria-labelledby="exportarGDWModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="exportarGDWModalLabel">
                        <i class="fas fa-file-export text-success"></i> Exportar a GDW
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formExportarGDW">
                        @csrf
                        <div class="mb-3">
                            <label for="fecha_exportacion" class="form-label">Fecha de Asignación:</label>
                            <input type="date" class="form-control" id="fecha_exportacion" name="fecha_exportacion" required>
                        </div>
                        <div id="loaderExportar" style="display: none; text-align: center;">
                            <div class="spinner-border text-success" role="status"></div>
                            <span class="visually-hidden">Buscando...</span>
                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1rem 0 0 0;">
                            <button type="submit" class="btn-gradient btn-gradient-success" id="btnSubmitExportar">
                                Buscar y Exportar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')

    <script>


            // Pasamos los datos de Blade a JavaScript
            const dataFromPHP = @json($completeData);
            const listaInspectores = @json($listaInspectoresArray);

            // Extraemos las cabeceras (nombres de las columnas)
             colHeaders = [
                'NÚMERO ORDEN', 'CONTRATO', 'CÉDULA', 'NOMBRE', 'DEPARTAMENTO',
                'LOCALIDAD', 'BARRIO', 'DIRECCIÓN', 'CATEGORÍA',
                'COD UNIDAD OPERATIVA', 'TIPO TRABAJO', 'FECHA ASIGNACIÓN',
                'OBSERVACIÓN SOLICITUD', 'FECHA CIERRE ÚLTIMA', 'OBSERVACIÓN CIERRE ÚLTIMA',
                'TIPO TRABAJO CIERRE ÚLTIMA', 'CAUSAL CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA',
                'OBSERVACIÓN ASIGNACIÓN ÚLTIMA', 'GESTIÓN ASIGNACIÓN ÚLTIMA', 'TIPO TRABAJO ASIGNACIÓN ÚLTIMA',
                 'MOTIVO DE PQR','RESPONSABLE', 'ASIGNADO','SUPERVISOR' ,'FECHA ASIGNADO','RECEPCIÓN',
                 'FECHA RECEPCIÓN','FECHA SOLICITUD CIERRE', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                 'FECHA LÍMITE', 'DÍAS FALTANTES'
             ];



            // Mapeamos los datos
            tableData = dataFromPHP.map(row => [
                row.NUMERO_ORDEN,
                row.CONTRATO,
                row.CEDULA,
                row.NOMBRE,
                row.DESC_DEPART,
                row.DESC_LOCALIDAD,
                row.BARRIO,
                row.DIRECCION,
                row.DESC_CATEGORIA,
                row.COD_UNIDAD_OPER,
                row.DESC_TIPO_TRABAJO,
                row.FECHA_ASIGNACION,
                row.OBSERVACION_SOLICITUD,
                row.FECHA_CIERRE_ULTIMA,
                row.OBSERVACIÓN_CIERRE_ULTIMA,
                row.TIPO_TRABAJO_CIERRE_ULTIMA,
                row.DESC_CAUSAL_CIERRE_ULTIMA,
                row.FECHA_ASIGNACIÓN_ULTIMA,
                row.OBSERVACIÓN_ASIGNACIÓN_ULTIMA,
                row.GESTIÓN_ASIGNACIÓN_ULTIMA,
                row.TIPO_TRABAJO_ASIGNACIÓN_ULTIMA,
                row.MOTIVO_DE_PQR,
                row.RESPONSABLE,
                row.ASIGNADO,
                row.SUPERVISOR,
                row.FECHA_ASIGNADO,
                row.RECEPCION,
                row.FECHA_RECEPCION,
                row.FECHA_SOLICITUD_CIERRE,
                row.OBSERVACION_GESTION,
                row.CODIGO_AUTORIZACION,
                row.FECHA_RESPUESTA,
                row.FECHA_LIMITE,
                row.DIAS_FALTANTES

            ]);



    </script>
@endsection
