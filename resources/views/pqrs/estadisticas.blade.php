@extends('adminlte::page')

@section('title', 'Estadísticas Quejas')

@section('content_header')
    <h1 class="mb-2 font-weight-bold text-dark">
        <i class="fas fa-chart-line text-primary mr-2"></i> Estadísticas Quejas
    </h1>
@endsection

@section('content')
    <style>
        /* --- Estilos Modernos --- */
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
        .custom-select-modern, .custom-input-modern {
            border-radius: 8px;
            border: 1px solid #ced4da;
            padding: 0.4rem 0.75rem;
            color: #495057;
        }
        .custom-select-modern:focus, .custom-input-modern:focus {
            border-color: #4F81BD;
            box-shadow: 0 0 0 0.2rem rgba(79, 129, 189, 0.25);
        }
    </style>

    <div class="card filter-bar mb-4 p-3">
        <form method="GET" action="{{ route('pqrs.coordinacion.estadisticas') }}" class="d-flex flex-wrap align-items-center gap-3" id="formFiltros" style="gap: 15px;">

            <div class="d-flex align-items-center mr-3 mb-2 mb-md-0">
                <label for="filtroEstado" class="mr-2 mb-0 font-weight-bold text-secondary">
                    <i class="fas fa-filter text-primary"></i> Estado:
                </label>
                <select name="estado" id="filtroEstado" class="custom-select-modern w-auto">
                    <option value="1" {{ $estadoFiltro == '1' ? 'selected' : '' }}>🟢 Activas (Pendientes)</option>
                    <option value="0" {{ $estadoFiltro == '0' ? 'selected' : '' }}>🔴 Cerradas (Histórico)</option>
                    <option value="todos" {{ $estadoFiltro == 'todos' ? 'selected' : '' }}>🔵 Todas (Global)</option>
                </select>
            </div>

            <div class="d-flex align-items-center mr-3 mb-2 mb-md-0">
                <label for="filtroInspector" class="mr-2 mb-0 font-weight-bold text-secondary">
                    <i class="fas fa-hard-hat text-primary"></i> Inspector:
                </label>
                <select name="inspector" id="filtroInspector" class="custom-select-modern w-auto" style="max-width: 250px;">
                    <option value="">Todos los inspectores...</option>
                    @foreach($listaInspectores as $insp)
                        <option value="{{ $insp }}" {{ $inspectorFiltro == $insp ? 'selected' : '' }}>
                            {{ $insp }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div id="contenedorFechas" class="align-items-center mr-3 mb-2 mb-md-0" style="display: {{ $estadoFiltro == '1' ? 'none !important' : 'flex' }}; gap: 10px;">
                <label class="mb-0 font-weight-bold text-secondary">
                    <i class="far fa-calendar-alt text-primary"></i> Rango:
                </label>
                <select name="tipo_fecha" id="tipoFecha" class="custom-select-modern w-auto">
                    <option value="asignacion" {{ isset($tipoFecha) && $tipoFecha == 'asignacion' ? 'selected' : '' }}>F. Asignación</option>
                    <option value="legalizacion" {{ isset($tipoFecha) && $tipoFecha == 'legalizacion' ? 'selected' : '' }}>F. Legalización</option>
                </select>
                <input type="date" name="fecha_inicio" id="fechaInicio" class="custom-input-modern w-auto" value="{{ $fechaInicio ?? '' }}" title="Fecha de inicio">
                <span class="text-secondary font-weight-bold px-1">al</span>
                <input type="date" name="fecha_fin" id="fechaFin" class="custom-input-modern w-auto" value="{{ $fechaFin ?? '' }}" title="Fecha de fin">
            </div>

            <div class="ml-auto d-flex" style="gap: 10px;">
                <button type="submit" class="btn-modern btn-gradient-primary">
                    <i class="fas fa-search"></i> Aplicar
                </button>
                <a href="{{ route('pqrs.coordinacion.estadisticas') }}" class="btn-modern btn-light border shadow-sm text-secondary">
                    <i class="fas fa-sync-alt"></i> Limpiar
                </a>
            </div>
        </form>
    </div>

    <div class="row">
        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center">
                    <div class="bg-primary rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-users text-white"></i>
                    </div>
                    <h3 class="modern-title">Top 10: Técnicos con más quejas</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartTecnicos" style="min-height: 280px; height: 280px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center">
                    <div class="bg-success rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-clipboard-list text-white"></i>
                    </div>
                    <h3 class="modern-title">Motivos de Queja (Global)</h3>
                </div>
                <div class="card-body">
                    <canvas id="chartMotivos" style="min-height: 280px; height: 280px; max-width: 100%;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center">
                    <div class="bg-warning rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-balance-scale text-white"></i>
                    </div>
                    <h3 class="modern-title">Resolución: Accede vs No Accede</h3>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center" style="height: 280px;">
                    <canvas id="chartAcceso" style="max-height: 300px; max-width: 300px;"></canvas>
                </div>
            </div>
        </div>

        <div class="col-lg-6 col-md-12">
            <div class="modern-card">
                <div class="modern-card-header d-flex align-items-center">
                    <div class="bg-danger rounded-circle d-flex justify-content-center align-items-center mr-3" style="width: 35px; height: 35px;">
                        <i class="fas fa-stopwatch text-white"></i>
                    </div>
                    <h3 class="modern-title">Eficiencia: A tiempo vs Vencidas</h3>
                </div>
                <div class="card-body d-flex justify-content-center align-items-center" style="height: 280px;">
                    <canvas id="chartTiempos" style="max-height: 300px; max-width: 300px;"></canvas>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function() {

            // --- LÓGICA DEL FORMULARIO DE FILTROS ---
            const filtroEstado = document.getElementById('filtroEstado');
            const contenedorFechas = document.getElementById('contenedorFechas');
            const fechaInicio = document.getElementById('fechaInicio');
            const fechaFin = document.getElementById('fechaFin');

            function verificarFiltroEstado() {
                if (filtroEstado.value === '1') {
                    contenedorFechas.style.setProperty('display', 'none', 'important');
                    fechaInicio.value = '';
                    fechaFin.value = '';
                } else {
                    contenedorFechas.style.setProperty('display', 'flex', 'important');
                }
            }
            filtroEstado.addEventListener('change', verificarFiltroEstado);


            // --- CONFIGURACIÓN DE GRÁFICAS (CHART.JS) ---
            // Defaults globales para fuentes más modernas
            Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial', sans-serif";
            Chart.defaults.color = '#6c757d';

            const tecnicosData = @json($tecnicosTop);
            const motivosData = @json($motivosAgrupados);
            const accesoData = @json($accesoStats);
            const tiemposData = @json($tiemposStats);

            // Paletas de colores modernas (con gradientes/opacidad simulada)
            const modernBlue = 'rgba(79, 129, 189, 0.8)';
            const modernGreen = 'rgba(40, 167, 69, 0.8)';
            const modernRed = 'rgba(220, 53, 69, 0.8)';
            const modernYellow = 'rgba(255, 193, 7, 0.8)';
            const modernCyan = 'rgba(23, 162, 184, 0.8)';
            const modernGray = 'rgba(108, 117, 125, 0.8)';

            // 1. CHART TÉCNICOS
            new Chart(document.getElementById('chartTecnicos'), {
                type: 'bar',
                data: {
                    labels: Object.keys(tecnicosData).map(nombre => nombre.substring(0, 18) + '...'),
                    datasets: [{
                        label: 'Cantidad de Quejas',
                        data: Object.values(tecnicosData),
                        backgroundColor: modernBlue,
                        borderRadius: 6 // Barras redondeadas
                    }]
                },
                options: {
                    plugins: { legend: { display: false } }, // Ocultar leyenda para más limpieza
                    scales: {
                        y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        x: { grid: { display: false } }
                    }
                }
            });

            // 2. CHART MOTIVOS
            new Chart(document.getElementById('chartMotivos'), {
                type: 'bar',
                data: {
                    labels: Object.keys(motivosData),
                    datasets: [{
                        label: 'Total por Motivo',
                        data: Object.values(motivosData),
                        backgroundColor: modernGreen,
                        borderRadius: 6
                    }]
                },
                options: {
                    indexAxis: 'y',
                    plugins: { legend: { display: false } },
                    scales: {
                        x: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                        y: { grid: { display: false } }
                    }
                }
            });

            // 3. CHART ACCESO
            new Chart(document.getElementById('chartAcceso'), {
                type: 'doughnut', // Cambiado a dona para verse más moderno
                data: {
                    labels: Object.keys(accesoData),
                    datasets: [{
                        data: Object.values(accesoData),
                        backgroundColor: [modernGreen, modernRed, modernYellow],
                        borderWidth: 2,
                        hoverOffset: 5
                    }]
                },
                options: {
                    cutout: '65%', // Anillo más delgado
                    plugins: { legend: { position: 'bottom' } }
                }
            });

            // 4. CHART TIEMPOS
            new Chart(document.getElementById('chartTiempos'), {
                type: 'doughnut',
                data: {
                    labels: Object.keys(tiemposData),
                    datasets: [{
                        data: Object.values(tiemposData),
                        backgroundColor: [modernCyan, modernRed],
                        borderWidth: 2,
                        hoverOffset: 5
                    }]
                },
                options: {
                    cutout: '65%',
                    plugins: { legend: { position: 'bottom' } }
                }
            });
        });
    </script>
@endsection
