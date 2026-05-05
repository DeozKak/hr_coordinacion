@extends('adminlte::page')

@section('content_header')

<h1>Dashboard</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/home.css')}}">


    {{-- Panel de Filtro Superior --}}
    <div class="card filter-bar mb-4 p-3">
        <form method="GET" action="{{ route('home') }}" class="d-flex flex-wrap align-items-center" style="gap: 15px;">
            <div class="d-flex align-items-center">
                <label for="agrupacion" class="mr-2 mb-0 font-weight-bold text-secondary">
                    <i class="fas fa-filter text-primary"></i> Agrupar por:
                </label>
                <select name="agrupacion" id="agrupacion" class="custom-select-modern" onchange="this.form.submit()" style="min-width: 250px;">
                    <option value="tipo_trabajo" {{ $agrupacion == 'tipo_trabajo' ? 'selected' : '' }}>Tipo de Trabajo</option>
                    <option value="meses" {{ $agrupacion == 'meses' ? 'selected' : '' }}>Meses de Vencimiento (-55 hasta 60+)</option>
                </select>
            </div>
        </form>
    </div>

    {{-- Fila para agrupar las dos tablas --}}
    <div class="row">
        {{-- Columna Izquierda: Tabla 1 --}}
        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-list-ol text-white"></i>
                    </div>
                    <h3 class="modern-title">Resumen por {{ $titulo_columna }}</h3>
                </div>
                <div class="card-body">
                    {{-- Agregamos ID a la tabla y w-100 para DataTables --}}
                    <table id="tablaResumen" class="table table-bordered table-hover w-100">
                        <thead class="bg-light">
                        <tr>
                            <th>{{ $titulo_columna }}</th>
                            <th>Asignados</th>
                            <th>Ejecutados</th>
                            <th>Pendientes</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($resumen_asignaciones as $fila)
                            <tr>
                                <td class="font-weight-bold">{{ $fila->criterio }}</td>
                                <td>{{ $fila->total_asignados }}</td>
                                <td class="text-success">{{ $fila->total_ejecutados }}</td>
                                <td class="text-danger">{{ $fila->total_asignados - $fila->total_ejecutados }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        {{-- NUEVO: Pie de tabla con totales --}}
                        <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td class="text-right">TOTAL GENERAL:</td>
                            <td>{{ $resumen_asignaciones->sum('total_asignados') }}</td>
                            <td class="text-success">{{ $resumen_asignaciones->sum('total_ejecutados') }}</td>
                            <td class="text-danger">
                                {{ $resumen_asignaciones->sum('total_asignados') - $resumen_asignaciones->sum('total_ejecutados') }}
                            </td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

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
                        {{-- PRIMER CICLO: Solo dibujamos las filas de la tabla --}}
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
                        {{-- NUEVO: Pie de tabla con totales de técnicos --}}
                        <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td class="text-right align-middle">TOTAL TÉCNICOS ASIGNADOS:</td>
                            <td class="text-center align-middle">
                                @php
                                    // Sumamos la cantidad de técnicos de cada grupo
                                    $granTotalTecnicos = 0;
                                    foreach($tecnicos_por_localidad as $tecs) {
                                        $granTotalTecnicos += $tecs->count();
                                    }
                                @endphp
                                <span class="badge badge-success px-3 py-2" style="font-size: 13px;">
                                        {{ $granTotalTecnicos }}
                                    </span>
                            </td>
                            <td></td> {{-- Dejamos la última columna vacía por estética --}}
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
        {{-- SEGUNDO CICLO: Dibujamos los modales completamente AFUERA de la tabla --}}
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
                                        <small class="text-muted ml-4 pl-1">Código ID: {{ $tec->ID_TECNICO }}</small>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach

        {{-- ======================================================= --}}
        {{-- NUEVO MODAL: Formulario de Asignación Manual            --}}
        {{-- ======================================================= --}}
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
                            {{-- Input de Localidad --}}
                            <div class="form-group">
                                <label for="localidad_input" class="font-weight-bold text-secondary">Escriba el nombre de la Localidad / Municipio:</label>
                                <input type="text" name="localidad" id="localidad_input" class="form-control" placeholder="Ej: CALI, PALMIRA, CANDELARIA..." required style="border-radius: 8px;">
                                <small class="text-muted">Si escribe una localidad existente, se sumarán a ella. Si no existe, se creará una nueva fila en la tabla.</small>
                            </div>

                            <hr>

                            {{-- Lista de Técnicos con Scroll --}}
                            <label class="font-weight-bold text-secondary">Seleccione los técnicos (Puede elegir varios):</label>

                            {{-- Buscador interno simple con JS (Opcional pero muy útil) --}}
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
                                                    <span class="font-weight-bold">{{ $t->NOMBRE_COMPLETO }}</span>
                                                    <br>
                                                    <small class="text-primary">(ID: {{ $t->id }})</small>
                                                    @if($t->asignado_en)
                                                        {{-- NUEVO: Agregamos un ID a este span para poder ocultarlo con JS al editar --}}
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

        {{-- SECCIÓN: Gráfica de Barras Interactiva --}}
        <div class="modern-card">
            <div class="modern-card-header d-flex align-items-center justify-content-between">
                <div class="d-flex align-items-center">
                    <div class="bg-info rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-chart-bar text-white"></i>
                    </div>
                    <h3 class="modern-title">Gráfica de Pendientes por Localidad y {{ $titulo_columna }}</h3>
                </div>

                {{-- Movimos el selector de la gráfica aquí arriba para que se vea más moderno --}}
                <select id="localidad-chart-select" class="custom-select-modern" style="max-width: 300px;">
                    <option value="todas">📊 Todas las localidades (Consolidado)</option>
                    @foreach($resumen_localidades->keys() as $loc)
                        <option value="{{ $loc }}">📍 {{ $loc }}</option>
                    @endforeach
                </select>
            </div>
            <div class="card-body">
                <div style="position: relative; height:350px; width:100%">
                    <canvas id="pendientesChart"></canvas>
                </div>
            </div>
        </div>


        {{-- NUEVA SECCIÓN: Programaciones del Día Actual --}}
        <div class="row">
            <div class="col-12">
                <div class="modern-card">
                    <div class="modern-card-header d-flex align-items-center">
                        <div class="bg-warning rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                            <i class="fas fa-calendar-day text-white"></i>
                        </div>
                        <h3 class="modern-title">Programaciones para el Día de Hoy ({{ \Carbon\Carbon::now()->format('d/m/Y') }})</h3>
                    </div>
                    <div class="card-body">
                        <table id="tablaProgramacionesHoy" class="table table-bordered table-hover w-100">
                            <thead class="bg-light">
                            <tr>
                                {{-- 1. Cambiamos el título de la columna --}}
                                <th>Tipo de Trabajo</th>
                                <th>Total Programadas</th>
                                <th>Ejecutadas</th>
                                <th>Pendientes</th>
                            </tr>
                            </thead>
                            <tbody>
                            @forelse($programaciones_hoy as $prog)
                                <tr>
                                    {{-- 2. Cambiamos la variable a TIPO_TRABAJO --}}
                                    <td class="font-weight-bold text-primary">{{ $prog->TIPO_TRABAJO }}</td>
                                    <td>{{ $prog->total_programadas }}</td>
                                    <td>
                                    <span class="badge badge-success px-2 py-1">
                                        {{ $prog->total_ejecutadas }}
                                    </span>
                                    </td>
                                    <td>
                                    <span class="badge {{ ($prog->total_programadas - $prog->total_ejecutadas) > 0 ? 'badge-danger' : 'badge-secondary' }} px-2 py-1">
                                        {{ $prog->total_programadas - $prog->total_ejecutadas }}
                                    </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">No hay programaciones registradas para el día de hoy.</td>
                                </tr>
                            @endforelse
                            </tbody>
                            {{-- NUEVO: Pie de tabla con totales del día --}}
                            <tfoot class="bg-light font-weight-bold">
                            <tr>
                                <td class="text-right">TOTAL HOY:</td>
                                <td>{{ $programaciones_hoy->sum('total_programadas') }}</td>
                                <td>
                                        <span class="badge badge-success px-2 py-1">
                                            {{ $programaciones_hoy->sum('total_ejecutadas') }}
                                        </span>
                                </td>
                                <td>
                                    @php
                                        $totalPendientesHoy = $programaciones_hoy->sum('total_programadas') - $programaciones_hoy->sum('total_ejecutadas');
                                    @endphp
                                    <span class="badge {{ $totalPendientesHoy > 0 ? 'badge-danger' : 'badge-secondary' }} px-2 py-1">
                                            {{ $totalPendientesHoy }}
                                        </span>
                                </td>
                            </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endsection
        @section('js')
        <script>
            let rowDaraV = @json($resumen_localidades);
            let labelsXV =  @json($criterios_disponibles)
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
@if (session('success'))
<script>
     document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: "top-end",
                type: "success",
                title: "{{ session('success') }}",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
        });
</script>
@endif
@endsection
