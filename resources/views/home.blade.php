@extends('adminlte::page')

@section('content_header')
    <h1>Dashboard</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/home.css')}}">

    <div class="mb-4">
        <button type="button" class="btn btn-primary font-weight-bold" data-toggle="modal" data-target="#modalCargarDatos">
            <i class="fas fa-upload mr-2"></i>Cargar Datos OSF
        </button>
    </div>

    {{-- ======================================================= --}}
    {{-- FILA 1: SELECTOR DE FECHA Y REPORTE OPERATIVO DIARIO      --}}
    {{-- ======================================================= --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0"><i class="fas fa-chart-line mr-2"></i> Reporte Operativo Diario</h4>

                    {{-- Formulario para cambiar la fecha --}}
                    {{-- Formulario para Filtros --}}
                    <form action="{{ route('home') }}" method="GET" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="fecha_reporte" class="mr-2 text-white">Operación:</label>
                            <input type="date" class="form-control form-control-sm" id="fecha_reporte" name="fecha_reporte" value="{{ $fechaReporte }}">
                        </div>

                        <div class="form-group mr-3">
                            <label for="localidad_reporte" class="mr-2 text-white">Municipio:</label>
                            <select class="form-control form-control-sm" id="localidad_reporte" name="localidad_reporte">
                                <option value="TODAS" {{ $localidadSeleccionada === 'TODAS' ? 'selected' : '' }}>TODAS LAS LOCALIDADES</option>
                                @foreach($localidadesDisponibles as $loc)
                                    <option value="{{ $loc }}" {{ $localidadSeleccionada === $loc ? 'selected' : '' }}>
                                        {{ $loc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn btn-sm btn-primary"><i class="fas fa-search"></i> Filtrar</button>
                    </form>
                </div>

                <div class="card-body p-0">
                    <div class="row m-0">
                        {{-- MITAD IZQUIERDA: Tabla estilo captura --}}
                        <div class="col-md-6 p-0 border-right">
                            <table class="table table-bordered table-striped table-hover m-0 table-dark text-center">
                                <thead>
                                <tr>
                                    <th class="text-left">Operación {{ \Carbon\Carbon::parse($fechaReporte)->format('d/m/Y') }}</th>
                                    <th>Total (#)</th>
                                </tr>
                                </thead>
                                <tbody>
                                <tr>
                                    <td class="text-left font-weight-bold align-middle">Inspectores operaron</td>
                                    <td>
                                        <span style="font-size: 15px;">{{ $metricas['inspectores'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="inspectores" data-titulo="Inspectores que operaron"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left font-weight-bold align-middle">Ejecutado (Cierres Efectivos)</td>
                                    <td>
                                        <span class="badge badge-success" style="font-size: 15px;">{{ $metricas['ejecutadas'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="ejecutadas" data-titulo="Tareas Efectivas"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left font-weight-bold text-warning align-middle">Pendiente X legalizar</td>
                                    <td>
                                        <span class="text-warning font-weight-bold" style="font-size: 15px;">{{ $metricas['pendientes_legalizar'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="pendientes_legalizar" data-titulo="Pendientes por Legalizar"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left font-weight-bold text-danger align-middle">Pendientes Prioridades Ejecutadas</td>
                                    <td>
                                        <span class="text-danger font-weight-bold" style="font-size: 15px;">{{ $metricas['prioridades'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="prioridades" data-titulo="Prioridades Pendientes por Legalizar (>= 60 Meses)"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left font-weight-bold align-middle">Tareas Fallidas</td>
                                    <td>
                                        <span style="font-size: 15px;">{{ $metricas['fallidas'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="fallidas" data-titulo="Tareas Fallidas"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="text-left font-weight-bold align-middle">Programadas por el inspector</td>
                                    <td>
                                        <span style="font-size: 15px;">{{ $metricas['programadas'] }}</span>
                                        <button class="btn btn-sm btn-outline-info ml-3 btn-ver-detalle" data-tipo="programadas" data-titulo="Programadas para hoy"><i class="fas fa-eye"></i></button>
                                    </td>
                                </tr>
                                </tbody>
                            </table>
                        </div>

                        {{-- MITAD DERECHA: Gráfica de Meses Ejecutados (¡Este bloque faltaba!) --}}
                        <div class="col-md-6 bg-white p-3">
                            <h5 class="text-center font-weight-bold text-secondary mb-3">Meses Ejecutados (Tareas Efectivas)</h5>
                            <div style="height: 250px;">
                                <canvas id="chartMeses"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- SECCIÓN NUEVA: ESTADÍSTICAS DE PROGRAMACIONES           --}}
    {{-- ======================================================= --}}
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0" style="background-color: #f8f9fa;">
                <div class="card-header bg-white d-flex align-items-center border-0 pt-4 pb-3">
                    <div class="bg-warning rounded d-flex justify-content-center align-items-center mr-3 shadow-sm" style="width: 45px; height: 45px; border-radius: 12px !important;">
                        <i class="fas fa-calendar-check text-white" style="font-size: 1.2rem;"></i>
                    </div>
                    <h4 class="mb-0 font-weight-bold text-dark">
                        Programaciones para el Día ({{ \Carbon\Carbon::parse($fechaReporte)->format('d/m/Y') }})
                        {!! $localidadSeleccionada !== 'TODAS' ? '<span class="text-primary">- ' . $localidadSeleccionada . '</span>' : '' !!}
                    </h4>
                </div>

                <div class="card-body pt-0">
                    <div class="table-responsive px-2">
                        <table id="tablaProgramacionesHoy" class="table table-borderless text-center w-100" style="border-collapse: separate; border-spacing: 0 10px;">
                            <thead>
                            <tr class="text-secondary">
                                <th class="align-middle pb-3">Tipo de Trabajo</th>
                                <th class="align-middle pb-3">Total Programadas</th>
                                <th class="align-middle pb-3">Ejecutadas</th>
                                <th class="align-middle pb-3">Pendientes</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($estadisticasProgramadas as $est)
                                <tr class="bg-white shadow-sm" style="border-radius: 10px; transition: transform 0.2s;">
                                    <td class="font-weight-bold text-primary align-middle py-3" style="font-size: 16px; border-radius: 10px 0 0 10px;">
                                        <i class="fas fa-briefcase text-muted mr-2" style="font-size: 0.9rem;"></i> {{ $est['tipo'] }}
                                    </td>
                                    <td class="align-middle py-3 font-weight-bold text-dark" style="font-size: 15px;">{{ $est['total'] }}</td>
                                    <td class="align-middle py-3">
                                        <button class="btn btn-sm btn-light text-success font-weight-bold px-4 shadow-sm btn-ver-prog"
                                                data-tipo="{{ $est['tipo'] }}" data-estado="ejecutadas"
                                                style="border-radius: 20px; transition: all 0.3s;"
                                                onmouseover="this.classList.replace('btn-light', 'btn-success'); this.classList.replace('text-success', 'text-white');"
                                                onmouseout="this.classList.replace('btn-success', 'btn-light'); this.classList.replace('text-white', 'text-success');">
                                            {{ $est['ejecutadas'] }} <i class="fas fa-search ml-1 opacity-50"></i>
                                        </button>
                                    </td>
                                    <td class="align-middle py-3" style="border-radius: 0 10px 10px 0;">
                                        <button class="btn btn-sm btn-light text-danger font-weight-bold px-4 shadow-sm btn-ver-prog"
                                                data-tipo="{{ $est['tipo'] }}" data-estado="pendientes"
                                                style="border-radius: 20px; transition: all 0.3s;"
                                                onmouseover="this.classList.replace('btn-light', 'btn-danger'); this.classList.replace('text-danger', 'text-white');"
                                                onmouseout="this.classList.replace('btn-danger', 'btn-light'); this.classList.replace('text-white', 'text-danger');">
                                            {{ $est['pendientes'] }} <i class="fas fa-search ml-1 opacity-50"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                            <tfoot class="font-weight-bold">
                            <tr>
                                <td class="text-right align-middle py-4" style="font-size: 15px;">TOTAL:</td>
                                <td class="align-middle py-4 text-dark" style="font-size: 17px;">{{ $totalesProg['programadas'] }}</td>
                                <td class="align-middle py-4">
                                    <span class="badge badge-success px-3 py-2 shadow-sm" style="font-size: 15px; border-radius: 6px;">{{ $totalesProg['ejecutadas'] }}</span>
                                </td>
                                <td class="align-middle py-4">
                                    <span class="badge badge-danger px-3 py-2 shadow-sm" style="font-size: 15px; border-radius: 6px;">{{ $totalesProg['pendientes'] }}</span>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ======================================================= --}}
    {{-- FILA 2: TABLAS INFERIORES (Fuerza de trabajo)           --}}
    {{-- ======================================================= --}}
    <div class="row">
        {{-- Columna Derecha: Tabla 3 (Técnicos) --}}
        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center">
                        <div class="bg-success rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                            <i class="fas fa-users-cog text-white"></i>
                        </div>
                        <h3 class="modern-title">Fuerza de Trabajo por Localidad</h3>
                    </div>
                    {{-- NUEVO BOTÓN PARA ASIGNAR --}}
                    @can('ver_coordinacion_RP')
                        <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalNuevaAsignacion">
                            <i class="fas fa-plus"></i> Asignar Técnicos
                        </button>
                    @endcan
                </div>
                <div class="card-body">
                    {{-- Alerta de éxito si se guardó correctamente --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <table id="tablaTecnicos" class="table table-bordered table-hover w-100">
                        <thead class="bg-light">
                        <tr>
                            <th>Localidad</th>
                            <th>Técnicos</th>
                            <th>Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        @forelse($tecnicos_por_localidad as $nombre_localidad => $tecnicos)
                            <tr>
                                <td class="font-weight-bold align-middle">{{ $nombre_localidad }}</td>
                                <td class="align-middle text-center">
                                    <span class="badge badge-success px-3 py-2" style="font-size: 13px;">
                                        {{ $tecnicos->count() }}
                                    </span>
                                </td>
                                <td class="text-center align-middle">
                                    <button type="button" class="btn btn-sm btn-outline-success" data-toggle="modal" data-target="#modal-tecnicos-{{ \Illuminate\Support\Str::slug($nombre_localidad) }}">
                                        <i class="fas fa-search"></i> Ver
                                    </button>
                                    @can('ver_coordinacion_RP')
                                        <button type="button" class="btn btn-sm btn-primary"
                                                onclick="editarAsignacion('{{ $nombre_localidad }}', @json($tecnicos->pluck('ID_TECNICO')))">
                                            <i class="fas fa-edit"></i> Editar
                                        </button>
                                    @endcan
                                </td>
                            </tr>
                        @empty
                        @endforelse
                        </tbody>
                        <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td class="text-right align-middle">TOTAL TÉCNICOS ASIGNADOS:</td>
                            <td class="text-center align-middle">
                                @php
                                    $granTotalTecnicos = 0;
                                    foreach($tecnicos_por_localidad as $tecs) {
                                        $granTotalTecnicos += $tecs->count();
                                    }
                                @endphp
                                <span class="badge badge-success px-3 py-2" style="font-size: 13px;">
                                    {{ $granTotalTecnicos }}
                                </span>
                            </td>
                            <td></td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>


    {{-- ======================================================= --}}
    {{-- SECCIÓN DE MODALES (Siempre deben ir al final)          --}}
    {{-- ======================================================= --}}

    {{-- MODAL DINÁMICO PARA VER DETALLES DEL REPORTE DIARIO --}}
    <div class="modal fade" id="modalVerDetalles" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content" style="border-radius: 10px;">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title font-weight-bold" id="tituloModalDetalle"><i class="fas fa-list mr-2"></i> <span></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <table id="tablaDetalleRegistros" class="table table-bordered table-striped table-hover w-100">
                        <thead class="bg-light">
                        <tr>
                            <th>Contrato / Sitio</th>
                            <th>Nombre Operario</th>
                            <th>Municipio</th> {{-- Nueva columna --}}
                            <th>Tipo Tarea</th>
                            <th>Cierre</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTablaDetalles">
                        {{-- Se llena por JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- MODALES DE TÉCNICOS (Uno por localidad) --}}
    @foreach($tecnicos_por_localidad as $nombre_localidad => $tecnicos)
        <div class="modal fade" id="modal-tecnicos-{{ \Illuminate\Support\Str::slug($nombre_localidad) }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content" style="border-radius: 15px; border: none;">
                    <div class="modal-header bg-success text-white" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-map-marker-alt mr-2"></i> Técnicos en {{ $nombre_localidad }}</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <ul class="list-group list-group-flush">
                            @foreach($tecnicos as $tec)
                                <li class="list-group-item d-flex flex-column hover-bg-light">
                                    <div>
                                        <i class="fas fa-user-circle text-success mr-2" style="font-size: 1.2rem;"></i>
                                        <strong style="font-size: 1.1rem;">{{ $tec->NOMBRE_COMPLETO ?? 'Nombre no registrado' }}</strong>
                                    </div>
                                    <small class="text-muted ml-4 pl-1">Supervisor: {{ $tec->supervisor->name }}</small>
                                    <small class="text-muted ml-4 pl-1">Código ID: {{ $tec->ID_TECNICO }}</small>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endforeach

    {{-- MODAL NUEVA ASIGNACIÓN DE TÉCNICOS --}}
    <div class="modal fade" id="modalNuevaAsignacion" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form action="{{ route('asignacion.guardar_tecnicos') }}" method="POST">
                    @csrf
                    <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-user-plus mr-2"></i> Asignar Técnicos a Localidad</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="localidad_input" class="font-weight-bold text-secondary">Escriba el nombre de la Localidad / Municipio:</label>
                            <input type="text" name="localidad" id="localidad_input" class="form-control" placeholder="Ej: CALI, PALMIRA, CANDELARIA..." required style="border-radius: 8px;">
                            <small class="text-muted">Si escribe una localidad existente, se sumarán a ella. Si no existe, se creará una nueva fila en la tabla.</small>
                        </div>
                        <hr>
                        <label class="font-weight-bold text-secondary">Seleccione los técnicos (Puede elegir varios):</label>
                        <input type="text" id="buscadorTecnicos" class="form-control mb-2" placeholder="🔍 Buscar técnico por nombre o ID..." style="border-radius: 8px;">
                        <div class="border p-3" style="max-height: 250px; overflow-y: auto; border-radius: 8px; background: #f8f9fa;">
                            <div class="row" id="listaTecnicosCheckboxes">
                                @foreach($todos_los_tecnicos as $t)
                                    <div class="col-md-6 mb-2 item-tecnico">
                                        <div class="custom-control custom-checkbox p-2 border rounded {{ $t->asignado_en ? 'bg-light' : '' }}">
                                            <input type="checkbox"
                                                   class="custom-control-input check-tecnico"
                                                   id="tec_{{ $t->id }}"
                                                   name="tecnicos[]"
                                                   value="{{ $t->id }}"
                                                   data-asignado="{{ $t->asignado_en }}"
                                                {{ $t->asignado_en ? 'disabled' : '' }}>
                                            <label class="custom-control-label w-100" style="cursor: pointer;" for="tec_{{ $t->id }}">
                                                <span class="font-weight-bold">{{ $t->NOMBRE_COMPLETO }}</span><br>
                                                <small class="text-primary">(ID: {{ $t->id }})</small>
                                                @if($t->asignado_en)
                                                    <span id="lock_text_{{ $t->id }}" class="d-block mt-1">
                                                        <small class="text-danger"><i class="fas fa-lock"></i> Ya está en: {{ $t->asignado_en }}</small>
                                                    </span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary"><i class="fas fa-save mr-1"></i> Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL CARGA DE ARCHIVOS OT --}}
    <div class="modal fade" id="modalCargarDatos" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content" style="border-radius: 15px; border: none;">
                <form id="formSubirArchivos" action="{{ route('insercion_estadisticas_asignacion') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header bg-primary text-white" style="border-radius: 15px 15px 0 0;">
                        <h5 class="modal-title font-weight-bold"><i class="fas fa-file-excel mr-2"></i> Cargar Archivos OT</h5>
                        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-4">
                        <p class="text-muted mb-4">Seleccione los archivos correspondientes para actualizar la base de datos. Solo se permiten formatos de Excel (.xlsx, .xls) o CSV.</p>
                        <div class="form-group">
                            <label for="archivo_asignacion" class="font-weight-bold text-secondary">Archivo OT ABIERTAS (Asignación):</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo_asignacion" name="archivo_asignacion" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                                <label class="custom-file-label" for="archivo_asignacion" data-browse="Explorar">Seleccionar archivo...</label>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <label for="archivo_cerradas" class="font-weight-bold text-secondary">Archivo OT CERRADAS:</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo_cerradas" name="archivo_cerradas" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                                <label class="custom-file-label" for="archivo_cerradas" data-browse="Explorar">Seleccionar archivo...</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light" style="border-radius: 0 0 15px 15px;">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btnGuardarArchivos" class="btn btn-success">
                            <i class="fas fa-cloud-upload-alt mr-1"></i> Subir e Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL DINÁMICO PARA VER DETALLES DE PROGRAMACIONES --}}
    <div class="modal fade" id="modalVerDetallesProg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content" style="border-radius: 10px;">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title font-weight-bold" id="tituloModalProg"><i class="fas fa-clipboard-list mr-2"></i> <span></span></h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-3">
                    <table id="tablaDetalleProg" class="table table-bordered table-striped table-hover w-100">
                        <thead class="bg-light">
                        <tr>
                            <th>Contrato</th>
                            <th>Cliente</th>
                            <th>Técnico Asignado</th>
                            <th>Municipio</th>
                            <th>Estado</th>
                        </tr>
                        </thead>
                        <tbody id="cuerpoTablaProg">
                        {{-- Se llena por JavaScript --}}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    {{-- Inyección de datos para JavaScript --}}
    <script>
        window.datosProgramaciones = @json($detallesProgramaciones ?? []);
        window.datosMeses = @json($mesesData ?? []);
        window.datosDetalles = @json($detalles ?? []);
    </script>

    <script src="{{asset('js/home.js')}}"></script>

    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: "Error",
                    text: "{{session('error')}}",
                    icon: "error"
                });
            });
        </script>
    @endif

    @if ($errors->any())
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                let errores = "";
                @foreach ($errors->all() as $error)
                    errores += "{{ $error }}\n";
                @endforeach

                Swal.fire({
                    title: "Faltan datos",
                    text: errores,
                    icon: "warning"
                });
            });
        </script>
    @endif

    @if (session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    position: "top-end",
                    type: "success", // Cambiado de 'type' a 'icon' si usas SweetAlert2
                    icon: "success",
                    title: "{{ session('success') }}",
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });
            });
        </script>
    @endif
@endsection
