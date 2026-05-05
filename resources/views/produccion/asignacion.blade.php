@extends('adminlte::page')

@section('title', 'Estado Asignación')

{{-- Importamos los estilos de DataTables para Bootstrap 4 --}}
@section('css')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
@endsection

@section('content_header')
    <h1 class="mb-2 font-weight-bold text-dark">
        <i class="fas fa-tasks text-primary mr-2"></i> Panel de Estado de Asignación
    </h1>
@endsection

@section('content')
    <style>
        /* --- Estilos Modernos Extraídos de Estadísticas --- */
        .modern-card {
            background: #ffffff;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
            border: none;
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            margin-bottom: 25px;
        }
        .modern-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }
        .modern-card-header {
            background: transparent;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem 1.5rem;
            border-radius: 15px 15px 0 0;
        }
        .modern-title {
            font-weight: 700;
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0;
        }
        .filter-bar {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.03);
            border-left: 5px solid #4F81BD;
        }
        .btn-modern {
            border-radius: 8px;
            font-weight: 600;
            padding: 0.4rem 1rem;
            transition: all 0.3s ease;
        }
        .btn-gradient-primary {
            background: linear-gradient(135deg, #4F81BD 0%, #3b608c 100%);
            color: white;
            border: none;
        }
        .btn-gradient-primary:hover {
            background: linear-gradient(135deg, #3b608c 0%, #284261 100%);
            color: white;
            box-shadow: 0 4px 10px rgba(79, 129, 189, 0.4);
        }
        .custom-select-modern {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.4rem 0.75rem;
            color: #495057;
        }
        .custom-select-modern:focus {
            border-color: #4F81BD;
            box-shadow: 0 0 0 0.2rem rgba(79, 129, 189, 0.25);
            outline: none;
        }
        /* Ajustes visuales para DataTables dentro de tarjetas modernas */
        .dataTables_wrapper .dataTables_filter input {
            border-radius: 20px;
            border: 1px solid #ddd;
            padding: 4px 15px;
            outline: none;
        }
        .dataTables_wrapper .dataTables_filter input:focus {
            border-color: #4F81BD;
            box-shadow: 0 0 5px rgba(79,129,189,0.3);
        }
    </style>

    {{-- Panel de Filtro Superior --}}
    <div class="card filter-bar mb-4 p-3">
        <form method="GET" action="{{ route('asignacion.index') }}" class="d-flex flex-wrap align-items-center" style="gap: 15px;">
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
                    <button class="btn btn-sm btn-primary" data-toggle="modal" data-target="#modalNuevaAsignacion">
                        <i class="fas fa-plus"></i> Asignar Técnicos
                    </button>
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
                                    <button type="button" class="btn btn-sm btn-primary"
                                            onclick="editarAsignacion('{{ $nombre_localidad }}', @json($tecnicos->pluck('ID_TECNICO')))">
                                        <i class="fas fa-edit"></i> Editar
                                    </button>
                                </td>
                            </tr>
                        @empty
                        @endforelse
                        </tbody>
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
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        function editarAsignacion(localidad, idsTecnicos) {
            document.getElementById('localidad_input').value = localidad;

            // 1. SOLUCIÓN: Convertimos todos los IDs del servidor a Texto
            const arrayIds = idsTecnicos.map(String);

            document.querySelectorAll('.check-tecnico').forEach(check => {
                const asignadoEn = check.getAttribute('data-asignado');
                const checkVal = check.value.toString();
                const lockSpan = document.getElementById('lock_text_' + checkVal);

                // REGLA DE ORO: Si pertenece a esta localidad que estoy editando
                if (arrayIds.includes(checkVal)) {
                    check.removeAttribute('disabled'); // <-- CLAVE: Remueve el bloqueo HTML
                    check.disabled = false;
                    check.checked = true;
                    check.closest('.custom-checkbox').classList.remove('bg-light');
                    if(lockSpan) lockSpan.style.display = 'none'; // Ocultamos el candado
                }
                // Si está en OTRA localidad
                else if (asignadoEn && asignadoEn !== "") {
                    check.setAttribute('disabled', 'disabled');
                    check.disabled = true;
                    check.checked = false;
                    check.closest('.custom-checkbox').classList.add('bg-light');
                    if(lockSpan) lockSpan.style.display = 'block'; // Mostramos el candado
                }
                // Si está totalmente libre
                else {
                    check.removeAttribute('disabled');
                    check.disabled = false;
                    check.checked = false;
                    check.closest('.custom-checkbox').classList.remove('bg-light');
                    if(lockSpan) lockSpan.style.display = 'none';
                }
            });

            $('#modalNuevaAsignacion').modal('show');
        }

        // 2. Limpiar todo correctamente al cerrar el modal
        $('#modalNuevaAsignacion').on('hidden.bs.modal', function () {
            document.getElementById('localidad_input').value = "";

            document.querySelectorAll('.check-tecnico').forEach(check => {
                const asignadoEn = check.getAttribute('data-asignado');
                const checkVal = check.value.toString();
                const lockSpan = document.getElementById('lock_text_' + checkVal);

                check.checked = false;

                if (asignadoEn && asignadoEn !== "") {
                    check.setAttribute('disabled', 'disabled');
                    check.disabled = true;
                    check.closest('.custom-checkbox').classList.add('bg-light');
                    if(lockSpan) lockSpan.style.display = 'block';
                } else {
                    check.removeAttribute('disabled');
                    check.disabled = false;
                    check.closest('.custom-checkbox').classList.remove('bg-light');
                    if(lockSpan) lockSpan.style.display = 'none';
                }
            });
        });
        document.getElementById('buscadorTecnicos').addEventListener('input', function() {
            let filtro = this.value.toLowerCase();
            let items = document.querySelectorAll('.item-tecnico');

            items.forEach(function(item) {
                let texto = item.innerText.toLowerCase();
                if(texto.includes(filtro)) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        });
        document.addEventListener('DOMContentLoaded', function () {

            // --- 1. INICIALIZACIÓN DE DATATABLES ---
            // Configuración común para ambas tablas
            const dtOptions = {
                // Se reemplaza la URL por el objeto de traducción directo para evitar errores de red (CORS o CDN)
                language: {
                    "processing": "Procesando...",
                    "lengthMenu": "Mostrar _MENU_ registros",
                    "zeroRecords": "No se encontraron resultados",
                    "emptyTable": "Ningún dato disponible en esta tabla",
                    "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
                    "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
                    "infoFiltered": "(filtrado de un total de _MAX_ registros)",
                    "search": "Buscar:",
                    "infoThousands": ",",
                    "loadingRecords": "Cargando...",
                    "paginate": {
                        "first": "Primero",
                        "last": "Último",
                        "next": "Siguiente",
                        "previous": "Anterior"
                    },
                    "aria": {
                        "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                        "sortDescending": ": Activar para ordenar la columna de manera descendente"
                    }
                },
                scrollY: "250px",      // Altura fija con scroll vertical
                scrollCollapse: true,  // Si hay pocos datos, la tabla se encoge
                paging: false,         // Quitamos la paginación para usar solo el scroll
                info: false,           // Oculta el texto "Mostrando 1 a X de X"
                order: [[1, 'desc']],  // Ordenar por la segunda columna de mayor a menor por defecto
            };

            $('#tablaResumen').DataTable(dtOptions);
            $('#tablaTecnicos').DataTable(dtOptions);


            // --- 2. CONFIGURACIÓN DE LA GRÁFICA (Estilo Moderno) ---
            Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial', sans-serif";
            Chart.defaults.color = '#6c757d';

            const rawData = @json($resumen_localidades);
            const labelsX = @json($criterios_disponibles);

            function calcularDatosGrafica(localidadSeleccionada) {
                let datosCalculados = new Array(labelsX.length).fill(0);
                if (localidadSeleccionada === 'todas') {
                    Object.keys(rawData).forEach(loc => {
                        rawData[loc].forEach(item => {
                            let indice = labelsX.indexOf(item.criterio.toString());
                            if (indice !== -1) datosCalculados[indice] += item.cantidad;
                        });
                    });
                } else {
                    if (rawData[localidadSeleccionada]) {
                        rawData[localidadSeleccionada].forEach(item => {
                            let indice = labelsX.indexOf(item.criterio.toString());
                            if (indice !== -1) datosCalculados[indice] += item.cantidad;
                        });
                    }
                }
                return datosCalculados;
            }

            const ctx = document.getElementById('pendientesChart').getContext('2d');
            let chartPendientes = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: labelsX,
                    datasets: [{
                        label: 'Trabajos Pendientes',
                        data: calcularDatosGrafica('todas'),
                        backgroundColor: 'rgba(23, 162, 184, 0.8)', // Color moderno similar al de estadísticas
                        borderRadius: 6 // Barras redondeadas
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: { legend: { display: false } }, // Ocultamos leyenda por estética
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });

            document.getElementById('localidad-chart-select').addEventListener('change', function() {
                chartPendientes.data.datasets[0].data = calcularDatosGrafica(this.value);
                chartPendientes.update();
            });

            $('#tablaProgramacionesHoy').DataTable(dtOptions);
        });
    </script>
@endsection
