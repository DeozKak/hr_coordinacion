@extends('adminlte::page')


@section('title', 'Coordinación Quejas')

@section('content_header')
    <h1 class="mb-2"><i class="fas fa-tasks text-primary"></i> Coordinación Quejas</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/pqrs/coordinacion.css') }}?v={{ filemtime(public_path('css/pqrs/coordinacion.css')) }}">
    <script src="{{ asset('js/PQRS/coordinacionHistorico.js') }}?v={{ time() }}" type="text/javascript"></script>
    <script src="{{ asset('js/PQRS/coordinacion.js') }}?v={{ time() }}" type="text/javascript"></script>
    <script src="{{ asset('js/PQRS/coordinacionPQRSTbl.js') }}?v={{ time() }}" type="text/javascript"></script>

    <input type="hidden" id="url_import" value="{{ route('pqrs.coordinacion.ImportOSF') }}">
    <input type="hidden" id="url_update_asignado" value="{{ route('pqrs.coordinacion.updateAsignado') }}">
    <input type="hidden" id="url_get_historico" value="{{ route('pqrs.coordinacion.historico') }}">
    <input type="hidden" id="url_exportar_gdw" value="{{ route('pqrs.coordinacion.exportarGDW') }}">
    <input type="hidden" id="url_get_datos_actualizados" value="{{ route('pqrs.coordinacion.datosActualizados') }}">
    <input type="hidden" id="url_get_supervisores" value="{{ route('pqrs.coordinacion.getSupervisores') }}">
    <input type="hidden" id="url_export_supervisor_excel" value="{{ route('pqrs.coordinacion.exportarSupervisores') }}">
    <input type="hidden" id="url_export_historico_excel" value="{{ route('pqrs.coordinacion.exportarHistorico') }}">

    <div class="shadow-container">
        <div class="controls-header">
            <div class="actions-group">
                <button id="openModalBtn" class="btn-gradient btn-gradient-primary btn-sm">
                    <i class="fas fa-cloud-upload-alt"></i> Cargar Datos
                </button>
                <button id="openHistoricoBtn" class="btn-gradient btn-gradient-warning btn-sm">
                    <i class="fas fa-history"></i> Histórico
                </button>
                <button id="openExportarGDWBtn" class="btn-gradient btn-gradient-success btn-sm">
                    <i class="fas fa-file-excel"></i> Exportar a GDW
                </button>
                <button id="openExportarSupervisoresBtn" class="btn-gradient btn-secondary-modern btn-sm">
                    <i class="fas fa-user-shield"></i> Exportar Supervisores
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <div id="tabla" class="mt-2" style="position: relative;">
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="CargarModal" tabindex="-1" aria-labelledby="CargarModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="CargarModalLabel">
                        <i class="fas fa-file-upload text-primary"></i>
                        <span>Cargar Quejas</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="CargarOSfForm" enctype="multipart/form-data" method="POST" action="{{route('pqrs.coordinacion')}}">
                        @csrf

                        <div class="mb-4">
                            <input type="hidden" name="type" value="OSF">
                            <label for="archivoAsignadas" class="form-label"><i class="fas fa-inbox text-muted"></i> Quejas Asignadas:</label>
                            <input type="file" class="form-control" id="archivoAsignadas" name="Asignadas">
                        </div>

                        <div class="mb-4">
                            <input type="hidden" name="type" value="base">
                            <label for="archivoCerradas" class="form-label"><i class="fas fa-check-double text-muted"></i> Quejas Cerradas:</label>
                            <input type="file" class="form-control" id="archivoCerradas" name="Cerradas">
                        </div>

                        <div class="mb-3">
                            <label for="archivosHTML" class="form-label"><i class="fab fa-html5 text-muted"></i> Soportes HTML (Múltiples):</label>
                            <input type="file" class="form-control" id="archivosHTML" name="archivos_html[]" multiple accept=".html">
                        </div>

                        <div id="loader" class="text-center mt-3" style="display: none;">
                            <div class="spinner-border text-primary" role="status"></div>
                            <p class="mt-2 text-muted small">Procesando archivos, por favor espera...</p>
                        </div>

                        <div class="modal-footer px-0 pb-0 mt-4" style="border-top: none;">

                            <button id="submit-OSF" type="submit" class="btn-gradient btn-gradient-primary">
                                <i class="fas fa-upload"></i> Subir Archivos
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="historicoModal" tabindex="-1" aria-labelledby="historicoModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="historicoModalLabel">
                        <i class="fas fa-history text-warning"></i> Histórico de Quejas
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">

                    <form id="formHistorico" class="mb-4 bg-white p-3 rounded shadow-sm border">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label for="hist_orden" class="form-label">Número Orden</label>
                                <input type="text" class="form-control" id="hist_orden" name="orden" placeholder="Ej: 12345">
                            </div>
                            <div class="col-md-3">
                                <label for="hist_contrato" class="form-label">Contrato</label>
                                <input type="text" class="form-control" id="hist_contrato" name="contrato" placeholder="Ej: 98765">
                            </div>
                            <div class="col-md-2">
                                <label for="hist_fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="hist_fecha_inicio" name="fecha_inicio">
                            </div>
                            <div class="col-md-2">
                                <label for="hist_fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="hist_fecha_fin" name="fecha_fin">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn-gradient btn-gradient-primary w-100 h-100">
                                    <i class="fas fa-search"></i> Buscar
                                </button>
                            </div>
                        </div>
                    </form>
                    <div id="headerAccionesHistorico" class="text-end mb-2">
                        <button type="button" id="btnExportarHistorico" class="btn-gradient btn-gradient-success btn-sm">
                            <i class="fas fa-file-csv"></i> Exportar Resultados
                        </button>
                    </div>
                    <div class="table-responsive bg-white p-2 rounded border">
                        <div id="tabla_historico" style="position: relative; width: 100%; height: 400px; overflow: hidden;"></div>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="verMasModal" tabindex="-1" aria-labelledby="verMasModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verMasModalLabel">
                        <i class="fas fa-info-circle text-primary"></i> Información Completa
                    </h5>
                    <button type="button" class="btn-close" id="btnCerrarVerMasTop" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="bg-white p-3 rounded border shadow-sm">
                        <p id="verMasContent" style="white-space: pre-wrap; word-break: break-word; color: #4a5568; line-height: 1.6;"></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary px-4" id="btnCerrarVerMasFooter">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="exportarGDWModal" tabindex="-1" aria-labelledby="exportarGDWModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
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

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="exportar_pendientes" name="exportar_pendientes">
                            <label class="form-check-label fw-bold text-danger" for="exportar_pendientes">
                                Exportar todas las pendientes (Sin Recepción)
                            </label>
                        </div>

                        <div class="mb-4">
                            <label for="fecha_exportacion" class="form-label"><i class="far fa-calendar-alt text-muted"></i> Fecha de Asignación:</label>
                            <input type="date" class="form-control" id="fecha_exportacion" name="fecha_exportacion">
                        </div>

                        <div id="loaderExportar" class="text-center mt-3" style="display: none;">
                            <div class="spinner-border text-success" role="status"></div>
                            <p class="mt-2 text-muted small">Buscando y exportando...</p>
                        </div>

                        <div class="modal-footer px-0 pb-0 mt-4" style="border-top: none;">
                            <button type="submit" class="btn-gradient btn-gradient-success" id="btnSubmitExportar">
                                <i class="fas fa-download"></i> Buscar y Exportar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade modal-modern" id="exportarSupervisorModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-file-excel text-success"></i> Exportar por Supervisor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="formExportSupervisor">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label fw-bold">Seleccione el Supervisor:</label>
                            <select id="selectSupervisor" class="form-select form-control">
                                <option value="">Cargando supervisores...</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" id="btnEjecutarExport" class="btn-gradient btn-gradient-success w-100">
                        <i class="fas fa-download"></i> Generar Excel
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')

    <script>

            const permisoEditar = @json($permiso_editar);

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
                 'MOTIVO DE PQR','RESPONSABLE', 'ASIGNADO','SUPERVISOR' ,'FECHA ASIGNADO',
                 /* --- NUEVOS CAMPOS AQUÍ --- */
                 'TÉCNICO PROXIMA PROGRAMACION', 'FECHA AGENDAMIENTO PROGRAMACION'
                 ,'INSTRUCCIONES CAMPO','OBSERVACION SUPERVISOR','RECEPCIÓN',
                 'FECHA RECEPCIÓN','FECHA SOLICITUD CIERRE', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                  'FECHA LÍMITE', 'DÍAS RESTANTES'
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
                /* --- NUEVOS CAMPOS AQUÍ --- */
                row.TECNICO_AGENDADO,
                row.FECHA_AGENDAMIENTO,
                row.INSTRUCCIONES_CAMPO,
                row.OBSERVACION_SUPERVISOR,
                row.RECEPCION,
                row.FECHA_RECEPCION,
                row.FECHA_SOLICITUD_CIERRE,
                row.OBSERVACION_GESTION,
                row.CODIGO_AUTORIZACION,
                row.FECHA_RESPUESTA,
                row.FECHA_LIMITE,
                row.DIAS_FALTANTES,
            ]);



    </script>
@endsection
