@extends('adminlte::page')

@section('content_header')
    <h1>Dashboard</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/homeV2.css')}}">
    <div class="mb-4">
        @haspermission('ver_residente')
        <button type="button" class="btn-dash btn-dash-lg btn-dash-solid-primary" data-toggle="modal" data-target="#modalCargarDatos">
            <i class="fas fa-upload mr-2"></i>Cargar Datos OSF
        </button>
        @endhaspermission
    </div>

    {{-- ======================================================= --}}
    {{-- FILA 1: SELECTOR DE FECHA Y REPORTE OPERATIVO DIARIO    --}}
    {{-- ======================================================= --}}
    <div class="row">
        <div class="col-12 mb-4">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-dark mr-3">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div>
                            <h3 class="dashboard-title">Reporte Operativo Diario</h3>
                            <p class="dashboard-subtitle">{{ \Carbon\Carbon::parse($fechaReporte)->format('d/m/Y') }} · {{ $localidadSeleccionada === 'TODAS' ? 'Todas las localidades' : $localidadSeleccionada }}</p>
                        </div>
                    </div>

                    {{-- Formulario para Filtros --}}
                    <form action="{{ route('home') }}" method="GET" class="form-inline">
                        <div class="form-group mr-3">
                            <label for="fecha_reporte" class="mr-2 text-secondary">Operación:</label>
                            <input type="date" class="form-control form-control-sm custom-select-modern" id="fecha_reporte" name="fecha_reporte" value="{{ $fechaReporte }}">
                        </div>

                        <div class="form-group mr-3">
                            <label for="localidad_reporte" class="mr-2 text-secondary">Municipio:</label>
                            <select class="form-control form-control-sm custom-select-modern" id="localidad_reporte" name="localidad_reporte">
                                <option value="TODAS" {{ $localidadSeleccionada === 'TODAS' ? 'selected' : '' }}>TODAS LAS LOCALIDADES</option>
                                @foreach($localidadesDisponibles as $loc)
                                    <option value="{{ $loc }}" {{ $localidadSeleccionada === $loc ? 'selected' : '' }}>
                                        {{ $loc }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn-dash btn-dash-solid-primary"><i class="fas fa-search"></i> Filtrar</button>
                    </form>
                </div>

                <div class="dashboard-card-body">
                    <div class="row">
                        {{-- MITAD IZQUIERDA: Métricas del día --}}
                        <div class="col-md-6">
                            <div class="table-responsive">
                                <table class="tabla-dashboard">
                                    <thead>
                                    <tr>
                                        <th>Operación {{ \Carbon\Carbon::parse($fechaReporte)->format('d/m/Y') }}</th>
                                        <th>Total (#)</th>
                                        <th>Detalle</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td><i class="fas fa-user-tie icono-fila"></i> Inspectores operaron</td>
                                        <td>{{ number_format($metricas['inspectores'], 0, ',', '.') }}</td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="inspectores" data-titulo="Inspectores que operaron"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-check-circle icono-fila"></i> Ejecutado (Cierres Efectivos)</td>
                                        <td><span class="badge badge-success badge-dashboard">{{ number_format($metricas['ejecutadas'], 0, ',', '.') }}</span></td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="ejecutadas" data-titulo="Tareas Efectivas"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-file-signature icono-fila"></i> Pendiente X legalizar</td>
                                        <td><span class="badge badge-warning badge-dashboard">{{ number_format($metricas['pendientes_legalizar'], 0, ',', '.') }}</span></td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="pendientes_legalizar" data-titulo="Pendientes por Legalizar"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    @php
                                        $rotuloAcumulado = $acumuladoDesde
                                            ? 'desde ' . \Carbon\Carbon::parse($acumuladoDesde)->format('d/m/Y')
                                            : 'sin histórico previo';
                                    @endphp
                                    <tr class="fila-acumulado">
                                        <td>
                                            <i class="fas fa-history icono-fila"></i> Pendiente X legalizar acumulado
                                            <small class="d-block text-muted font-weight-normal ml-4">{{ $rotuloAcumulado }}</small>
                                        </td>
                                        <td><span class="badge badge-warning badge-dashboard">{{ number_format($metricas['pendientes_legalizar_acumulado'], 0, ',', '.') }}</span></td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="pendientes_legalizar_acumulado" data-titulo="Pendientes por Legalizar acumulados ({{ $rotuloAcumulado }})"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-exclamation-triangle icono-fila"></i> Pendientes Prioridades Ejecutadas</td>
                                        <td><span class="badge badge-danger badge-dashboard">{{ number_format($metricas['prioridades'], 0, ',', '.') }}</span></td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="prioridades" data-titulo="Prioridades Pendientes por Legalizar (>= 60 Meses)"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr class="fila-acumulado">
                                        <td>
                                            <i class="fas fa-history icono-fila"></i> Prioridades acumuladas
                                            <small class="d-block text-muted font-weight-normal ml-4">{{ $rotuloAcumulado }}</small>
                                        </td>
                                        <td><span class="badge badge-danger badge-dashboard">{{ number_format($metricas['prioridades_acumulado'], 0, ',', '.') }}</span></td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="prioridades_acumulado" data-titulo="Prioridades Pendientes acumuladas ({{ $rotuloAcumulado }})"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-times-circle icono-fila"></i> Tareas Fallidas</td>
                                        <td>{{ number_format($metricas['fallidas'], 0, ',', '.') }}</td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="fallidas" data-titulo="Tareas Fallidas"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><i class="fas fa-calendar-day icono-fila"></i> Programadas por el inspector</td>
                                        <td>{{ number_format($metricas['programadas'], 0, ',', '.') }}</td>
                                        <td>
                                            <button class="btn-dash btn-dash-info btn-ver-detalle" data-tipo="programadas" data-titulo="Programadas para hoy"><i class="fas fa-eye"></i></button>
                                        </td>
                                    </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        {{-- MITAD DERECHA: Gráfica de Meses Ejecutados --}}
                        <div class="col-md-6 border-left">
                            <h6 class="text-center font-weight-bold text-secondary mb-3 mt-2">Meses Ejecutados (Tareas Efectivas)</h6>
                            <div style="height: 260px;">
                                <canvas id="chartMeses"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- FILA 2: PROGRAMACIONES DEL DÍA                          --}}
    {{-- ======================================================= --}}
    <div class="row">
        <div class="col-12 mb-4">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-warning mr-3">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div>
                            <h3 class="dashboard-title">Programaciones para el Día</h3>
                            <p class="dashboard-subtitle">
                                {{ \Carbon\Carbon::parse($fechaReporte)->format('d/m/Y') }}
                                {{ $localidadSeleccionada !== 'TODAS' ? '· ' . $localidadSeleccionada : '' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-card-body">
                    <div class="table-responsive">
                        <table id="tablaProgramacionesHoy" class="tabla-dashboard">
                            <thead>
                            <tr>
                                <th>Tipo de Trabajo</th>
                                <th>Total Programadas</th>
                                <th>Ejecutadas</th>
                                <th>Pendientes</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($estadisticasProgramadas as $est)
                                <tr>
                                    <td><i class="fas fa-briefcase icono-fila"></i> {{ $est['tipo'] }}</td>
                                    <td>{{ number_format($est['total'], 0, ',', '.') }}</td>
                                    <td>
                                        <button class="btn-dash btn-dash-success btn-ver-prog"
                                                data-tipo="{{ $est['tipo'] }}" data-estado="ejecutadas">
                                            {{ number_format($est['ejecutadas'], 0, ',', '.') }} <i class="fas fa-search ml-1"></i>
                                        </button>
                                    </td>
                                    <td>
                                        <button class="btn-dash btn-dash-danger btn-ver-prog"
                                                data-tipo="{{ $est['tipo'] }}" data-estado="pendientes">
                                            {{ number_format($est['pendientes'], 0, ',', '.') }} <i class="fas fa-search ml-1"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="celda-vacia">No hay programaciones para la fecha seleccionada.</td>
                                </tr>
                            @endforelse
                            </tbody>
                            <tfoot>
                            <tr>
                                <td class="text-right">TOTAL:</td>
                                <td>{{ number_format($totalesProg['programadas'], 0, ',', '.') }}</td>
                                <td><span class="badge badge-success badge-dashboard">{{ number_format($totalesProg['ejecutadas'], 0, ',', '.') }}</span></td>
                                <td><span class="badge badge-danger badge-dashboard">{{ number_format($totalesProg['pendientes'], 0, ',', '.') }}</span></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================= --}}
    {{-- FILA 3: PENDIENTES EN BASE + FUERZA DE TRABAJO          --}}
    {{-- ======================================================= --}}
    <div class="row">
        {{-- Columna Izquierda: Pendientes en Base --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-info mr-3">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div>
                            <h3 class="dashboard-title">Pendientes en Base</h3>
                            <p class="dashboard-subtitle">
                                Sin recepcionar · {{ number_format($baseTotalTipos, 0, ',', '.') }} de {{ number_format($baseTotalTabla, 0, ',', '.') }} registros
                            </p>
                        </div>
                    </div>

                    <select class="form-control form-control-sm custom-select-modern w-auto" id="selectorVistaBase">
                        <option value="tipos" selected>Tipo de Trabajo</option>
                        <option value="meses">Meses de Vencimiento</option>
                    </select>
                </div>

                <div class="dashboard-card-body">
                    <div class="table-responsive">
                        <table class="tabla-dashboard">
                            <thead>
                            <tr>
                                <th id="tituloColumnaBase">Tipo de Trabajo</th>
                                <th>Cantidad</th>
                            </tr>
                            </thead>

                            {{-- VISTA 1: POR TIPO DE TRABAJO --}}
                            <tbody class="vista-base" data-vista="tipos">
                            @forelse($baseTipos as $fila)
                                <tr>
                                    <td><i class="fas fa-briefcase icono-fila"></i> {{ $fila['etiqueta'] }}</td>
                                    <td>{{ number_format($fila['cantidad'], 0, ',', '.') }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="2" class="celda-vacia">Sin pendientes registrados.</td>
                                </tr>
                            @endforelse
                            </tbody>

                            {{-- VISTA 2: POR MESES DE VENCIMIENTO --}}
                            <tbody class="vista-base d-none" data-vista="meses">
                            @foreach($baseMeses as $fila)
                                <tr>
                                    <td><i class="fas fa-hourglass-half icono-fila"></i> {{ $fila['rango'] }}</td>
                                    <td class="{{ in_array($fila['rango'], ['60', '60 +']) && $fila['cantidad'] > 0 ? 'text-danger' : '' }}">
                                        {{ number_format($fila['cantidad'], 0, ',', '.') }}
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>

                            <tfoot>
                            <tr class="vista-base" data-vista="tipos">
                                <td class="text-right">TOTAL:</td>
                                <td><span class="badge badge-info badge-dashboard">{{ number_format($baseTotalTipos, 0, ',', '.') }}</span></td>
                            </tr>
                            <tr class="vista-base d-none" data-vista="meses">
                                <td class="text-right">TOTAL:</td>
                                <td><span class="badge badge-info badge-dashboard">{{ number_format($baseTotalMeses, 0, ',', '.') }}</span></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Columna Derecha: Fuerza de Trabajo --}}
        <div class="col-lg-6 col-md-12 mb-4">
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-success mr-3">
                            <i class="fas fa-users-cog"></i>
                        </div>
                        <div>
                            <h3 class="dashboard-title">Fuerza de Trabajo por Localidad</h3>
                            <p class="dashboard-subtitle">Técnicos asignados actualmente</p>
                        </div>
                    </div>

                    @can('ver_coordinacion_RP')
                        <button class="btn-dash btn-dash-solid-primary" data-toggle="modal" data-target="#modalNuevaAsignacion">
                            <i class="fas fa-plus mr-1"></i> Asignar Técnicos
                        </button>
                    @endcan
                </div>

                <div class="dashboard-card-body">
                    {{-- Alerta de éxito si se guardó correctamente --}}
                    @if(session('success'))
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle mr-2"></i> {{ session('success') }}
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table id="tablaTecnicos" class="tabla-dashboard">
                            <thead>
                            <tr>
                                <th>Localidad</th>
                                <th>Técnicos</th>
                                <th>Acción</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($tecnicos_por_localidad as $nombre_localidad => $tecnicos)
                                <tr>
                                    <td><i class="fas fa-map-marker-alt icono-fila"></i> {{ $nombre_localidad }}</td>
                                    <td><span class="badge badge-success badge-dashboard">{{ $tecnicos->count() }}</span></td>
                                    <td>
                                        <button type="button" class="btn-dash btn-dash-success" data-toggle="modal" data-target="#modal-tecnicos-{{ \Illuminate\Support\Str::slug($nombre_localidad) }}">
                                            <i class="fas fa-search"></i> Ver
                                        </button>
                                        @can('ver_coordinacion_RP')
                                            <button type="button" class="btn-dash btn-dash-primary"
                                                    onclick="editarAsignacion('{{ $nombre_localidad }}', @json($tecnicos->pluck('ID_TECNICO')))">
                                                <i class="fas fa-edit"></i> Editar
                                            </button>
                                        @endcan
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="celda-vacia">Aún no hay técnicos asignados.</td>
                                </tr>
                            @endforelse
                            </tbody>
                            <tfoot>
                            @php
                                $granTotalTecnicos = 0;
                                foreach($tecnicos_por_localidad as $tecs) {
                                    $granTotalTecnicos += $tecs->count();
                                }
                            @endphp
                            <tr>
                                <td class="text-right">TOTAL TÉCNICOS ASIGNADOS:</td>
                                <td><span class="badge badge-success badge-dashboard">{{ $granTotalTecnicos }}</span></td>
                                <td></td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>


    {{-- ======================================================= --}}
    {{-- SECCIÓN DE MODALES (Siempre deben ir al final)          --}}
    {{-- ======================================================= --}}

    {{-- MODAL DINÁMICO PARA VER DETALLES DEL REPORTE DIARIO --}}
    <div class="modal fade dashboard-modal" id="modalVerDetalles" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-info mr-3">
                            <i class="fas fa-list"></i>
                        </div>
                        <div id="tituloModalDetalle">
                            <h5 class="modal-title"><span></span></h5>
                            <p class="modal-subtitle">Detalle de la operación del día</p>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="tablaDetalleRegistros" class="tabla-dashboard">
                        <thead>
                        <tr>
                            <th>Contrato / Sitio</th>
                            <th>Nombre Operario</th>
                            <th>Municipio</th>
                            <th>Tipo Tarea</th>
                            <th>Cierre</th>
                            <th>Fecha ejecución</th>
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
        <div class="modal fade dashboard-modal" id="modal-tecnicos-{{ \Illuminate\Support\Str::slug($nombre_localidad) }}" tabindex="-1" role="dialog" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <div class="dashboard-icon bg-success mr-3">
                                <i class="fas fa-map-marker-alt"></i>
                            </div>
                            <div>
                                <h5 class="modal-title">Técnicos en {{ $nombre_localidad }}</h5>
                                <p class="modal-subtitle">{{ $tecnicos->count() }} {{ $tecnicos->count() === 1 ? 'técnico asignado' : 'técnicos asignados' }}</p>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
                        <ul class="list-group list-group-flush lista-tecnicos">
                            @foreach($tecnicos as $tec)
                                <li class="list-group-item d-flex flex-column">
                                    <div>
                                        <i class="fas fa-user-circle text-success mr-2" style="font-size: 1.2rem;"></i>
                                        <span class="nombre-tecnico">{{ $tec->NOMBRE_COMPLETO ?? 'Nombre no registrado' }}</span>
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
    <div class="modal fade dashboard-modal" id="modalNuevaAsignacion" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <form action="{{ route('asignacion.guardar_tecnicos') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <div class="dashboard-icon bg-primary mr-3">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <div>
                                <h5 class="modal-title">Asignar Técnicos a Localidad</h5>
                                <p class="modal-subtitle">Puede seleccionar varios técnicos a la vez</p>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="localidad_input">Escriba el nombre de la Localidad / Municipio:</label>
                            <input type="text" name="localidad" id="localidad_input" class="form-control" placeholder="Ej: CALI, PALMIRA, CANDELARIA..." required>
                            <small class="text-muted">Si escribe una localidad existente, se sumarán a ella. Si no existe, se creará una nueva fila en la tabla.</small>
                        </div>
                        <hr>
                        <label>Seleccione los técnicos:</label>
                        <small class="d-block text-muted mb-2">
                            <i class="fas fa-info-circle mr-1"></i>
                            Puede marcar técnicos que estén en otra localidad: al guardar se trasladan aquí automáticamente.
                        </small>
                        <input type="text" id="buscadorTecnicos" class="form-control mb-2" placeholder="🔍 Buscar técnico por nombre o ID...">
                        <div class="panel-tecnicos">
                            <div class="row" id="listaTecnicosCheckboxes">
                                @foreach($todos_los_tecnicos as $t)
                                    <div class="col-md-6 mb-2 item-tecnico">
                                        <div class="custom-control custom-checkbox caja-tecnico">
                                            <input type="checkbox"
                                                   class="custom-control-input check-tecnico"
                                                   id="tec_{{ $t->id }}"
                                                   name="tecnicos[]"
                                                   value="{{ $t->id }}"
                                                   data-asignado="{{ $t->asignado_en }}">
                                            <label class="custom-control-label w-100" style="cursor: pointer;" for="tec_{{ $t->id }}">
                                                <span class="nombre-tecnico">{{ $t->NOMBRE_COMPLETO }}</span><br>
                                                <small class="text-primary">(ID: {{ $t->id }})</small>
                                                @if($t->asignado_en)
                                                    <span id="origen_text_{{ $t->id }}" class="d-block mt-1 texto-origen">
                                                        <small><i class="fas fa-map-marker-alt"></i> Actualmente en: {{ $t->asignado_en }}</small>
                                                    </span>
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-dash btn-dash-neutral" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-dash btn-dash-solid-primary"><i class="fas fa-save"></i> Guardar Asignación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL CARGA DE ARCHIVOS OT --}}
    <div class="modal fade dashboard-modal" id="modalCargarDatos" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="formSubirArchivos" action="{{ route('insercion_estadisticas_asignacion') }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-header">
                        <div class="d-flex align-items-center">
                            <div class="dashboard-icon bg-success mr-3">
                                <i class="fas fa-file-excel"></i>
                            </div>
                            <div>
                                <h5 class="modal-title">Cargar Archivos OT</h5>
                                <p class="modal-subtitle">Formatos Excel (.xlsx, .xls) o CSV</p>
                            </div>
                        </div>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-4">Seleccione los archivos correspondientes para actualizar la base de datos.</p>
                        <div class="form-group">
                            <label for="archivo_asignacion">Archivo OT ABIERTAS (Asignación):</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo_asignacion" name="archivo_asignacion" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                                <label class="custom-file-label" for="archivo_asignacion" data-browse="Explorar">Seleccionar archivo...</label>
                            </div>
                        </div>
                        <div class="form-group mt-4">
                            <label for="archivo_cerradas">Archivo OT CERRADAS:</label>
                            <div class="custom-file">
                                <input type="file" class="custom-file-input" id="archivo_cerradas" name="archivo_cerradas" accept=".csv, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet, application/vnd.ms-excel">
                                <label class="custom-file-label" for="archivo_cerradas" data-browse="Explorar">Seleccionar archivo...</label>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-dash btn-dash-neutral" data-dismiss="modal">Cancelar</button>
                        <button type="submit" id="btnGuardarArchivos" class="btn-dash btn-dash-solid-success">
                            <i class="fas fa-cloud-upload-alt mr-1"></i> Subir e Importar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL DINÁMICO PARA VER DETALLES DE PROGRAMACIONES --}}
    <div class="modal fade dashboard-modal" id="modalVerDetallesProg" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <div class="d-flex align-items-center">
                        <div class="dashboard-icon bg-warning mr-3">
                            <i class="fas fa-clipboard-list"></i>
                        </div>
                        <div id="tituloModalProg">
                            <h5 class="modal-title"><span></span></h5>
                            <p class="modal-subtitle">Programaciones del día por tipo de trabajo</p>
                        </div>
                    </div>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <table id="tablaDetalleProg" class="tabla-dashboard">
                        <thead>
                        <tr>
                            <th>Contrato</th>
                            <th>Ordenlist</th>
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

    <script src="{{asset('js/homeV2.2.js')}}"></script>

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
