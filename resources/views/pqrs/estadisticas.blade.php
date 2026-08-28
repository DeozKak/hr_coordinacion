@extends('layouts.tw.app')

@section('title', 'Estadísticas de Quejas')

@section('content_header')
    <h1>Estadísticas de Quejas</h1>
@endsection

@section('subtitle', 'Distribución por técnico, motivo, resolución y cumplimiento de tiempos.')

@section('content')
    <div x-data="estadisticasPqrs({ estadoInicial: '{{ $estadoFiltro }}' })" class="space-y-6">

        {{-- ================================ FILTROS ============================ --}}
        <form method="GET" action="{{ route('pqrs.coordinacion.estadisticas') }}"
              class="tw-card border-l-4 border-l-brand-600 p-5">
            <div class="grid gap-4 lg:grid-cols-4">
                <div>
                    <label class="tw-label" for="filtroEstado">
                        <i class="fas fa-filter text-slate-400"></i> Estado
                    </label>
                    <select name="estado" id="filtroEstado" class="tw-select" x-model="estado">
                        <option value="1" {{ $estadoFiltro == '1' ? 'selected' : '' }}>Activas (pendientes)</option>
                        <option value="0" {{ $estadoFiltro == '0' ? 'selected' : '' }}>Cerradas (histórico)</option>
                        <option value="todos" {{ $estadoFiltro == 'todos' ? 'selected' : '' }}>Todas (global)</option>
                    </select>
                </div>

                <div>
                    <label class="tw-label" for="filtroInspector">
                        <i class="fas fa-helmet-safety text-slate-400"></i> Inspector
                    </label>
                    <select name="inspector" id="filtroInspector" class="tw-select">
                        <option value="">Todos los inspectores…</option>
                        @foreach($listaInspectores as $insp)
                            <option value="{{ $insp }}" {{ $inspectorFiltro == $insp ? 'selected' : '' }}>
                                {{ $insp }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- El rango de fechas solo aplica cuando no se miran las activas. --}}
                <div class="lg:col-span-2" x-show="estado !== '1'" x-cloak>
                    <label class="tw-label">
                        <i class="far fa-calendar text-slate-400"></i> Rango de fechas
                    </label>
                    <div class="flex flex-wrap items-center gap-2">
                        <select name="tipo_fecha" class="tw-select w-auto">
                            <option value="asignacion" {{ ($tipoFecha ?? '') == 'asignacion' ? 'selected' : '' }}>
                                F. asignación
                            </option>
                            <option value="legalizacion" {{ ($tipoFecha ?? '') == 'legalizacion' ? 'selected' : '' }}>
                                F. legalización
                            </option>
                        </select>
                        <input type="date" name="fecha_inicio" class="tw-input w-auto"
                               value="{{ $fechaInicio ?? '' }}" x-ref="inicio">
                        <span class="text-sm text-slate-400">al</span>
                        <input type="date" name="fecha_fin" class="tw-input w-auto"
                               value="{{ $fechaFin ?? '' }}" x-ref="fin">
                    </div>
                </div>
            </div>

            <div class="mt-4 flex flex-wrap justify-end gap-2">
                <a href="{{ route('pqrs.coordinacion.estadisticas') }}" class="tw-btn-secondary">
                    <i class="fas fa-rotate"></i> Limpiar
                </a>
                <button type="submit" class="tw-btn-primary">
                    <i class="fas fa-magnifying-glass"></i> Aplicar
                </button>
            </div>
        </form>

        {{-- ================================ GRÁFICAS =========================== --}}
        <div class="grid gap-6 xl:grid-cols-2">
            @php
                $tarjetas = [
                    ['chartTecnicos', 'Top 10: técnicos con más quejas',  'Cantidad de quejas por técnico',      'fa-users',          'emerald'],
                    ['chartMotivos',  'Motivos de queja (global)',        'Distribución por motivo registrado',  'fa-clipboard-list', 'emerald'],
                    ['chartAcceso',   'Resolución: accede vs no accede',  'Resultado de la visita',              'fa-scale-balanced', 'amber'],
                    ['chartTiempos',  'Eficiencia: a tiempo vs vencidas', 'Cumplimiento del plazo de respuesta', 'fa-stopwatch',      'rose'],
                ];
            @endphp

            @foreach($tarjetas as [$id, $titulo, $sub, $icono, $tinte])
                <section class="tw-card">
                    <div class="tw-card-header">
                        <div class="flex items-center gap-3">
                            <span class="tw-chip chip-{{ $tinte }}"><i class="fas {{ $icono }}"></i></span>
                            <div>
                                <h2 class="tw-card-title">{{ $titulo }}</h2>
                                <p class="tw-card-subtitle">{{ $sub }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="tw-card-body">
                        <div class="h-[300px]" x-show="hayDatos('{{ $id }}')">
                            <canvas id="{{ $id }}" role="img" aria-label="{{ $titulo }}"></canvas>
                        </div>
                        <p x-show="!hayDatos('{{ $id }}')" x-cloak
                           class="py-20 text-center text-sm text-slate-400">
                            Sin datos para los filtros seleccionados.
                        </p>
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
        const datosGraficas = {
            chartTecnicos: @json($tecnicosTop),
            chartMotivos:  @json($motivosAgrupados),
            chartAcceso:   @json($accesoStats),
            chartTiempos:  @json($tiemposStats),
        };

        /* Las instancias de Chart.js viven FUERA del estado de Alpine.
           Alpine (vía @vue/reactivity) envuelve en Proxy todo lo que guarda,
           y entonces `animator.remove(chart)` —un Map indexado por la instancia
           real— no encuentra la clave: el gráfico destruido se queda en el bucle
           de animación, se le llama draw() con el ctx ya en null, revienta dentro
           del requestAnimationFrame y mata el bucle para todos. Ese era el motivo
           de que las gráficas se fueran en blanco al alternar el tema. */
        let graficasPQRS = [];

        document.addEventListener('alpine:init', () => {
            Alpine.data('estadisticasPqrs', ({ estadoInicial }) => ({
                estado: estadoInicial,

                init() {
                    // Al pasar a "Activas" el backend ignora el rango: se limpia
                    // para no enviar fechas que no se están mostrando.
                    this.$watch('estado', (v) => {
                        if (v === '1' && this.$refs.inicio) {
                            this.$refs.inicio.value = '';
                            this.$refs.fin.value = '';
                        }
                    });

                    this.dibujar(Alpine.store('ui').dark);

                    // Se redibuja al cambiar de tema para que ejes, leyendas y
                    // bordes no se queden con los colores del modo anterior.
                    this.$watch('$store.ui.dark', (oscuro) => this.dibujar(oscuro));
                },

                hayDatos(id) {
                    const d = datosGraficas[id];
                    return !!d && Object.keys(d).length > 0;
                },

                paleta(oscuro) {
                    return {
                        rejilla: oscuro ? 'rgba(148,163,184,0.15)' : 'rgba(100,116,139,0.12)',
                        texto:   oscuro ? '#94a3b8' : '#64748b',
                        fondo:   oscuro ? '#0f172a' : '#ffffff',
                        verde:   '#10b981', verdeHover:  '#059669',
                        rojo:    '#f43f5e',
                        ambar:   '#f59e0b',
                        cian:    '#0ea5e9',
                    };
                },

                /* Mismos remates que la gráfica de Meses Ejecutados en el home. */
                tooltip() {
                    return {
                        backgroundColor: '#1a1622',
                        padding: 12,
                        cornerRadius: 10,
                        displayColors: false,
                    };
                },

                barras(id, c, horizontal = false) {
                    const datos = datosGraficas[id];
                    const valor = horizontal ? 'x' : 'y';
                    const categoria = horizontal ? 'y' : 'x';

                    return {
                        type: 'bar',
                        data: {
                            labels: Object.keys(datos).map(n => n.length > 22 ? n.slice(0, 22) + '…' : n),
                            datasets: [{
                                label: 'Cantidad',
                                data: Object.values(datos),
                                backgroundColor: c.verde,
                                hoverBackgroundColor: c.verdeHover,
                                borderRadius: 6,
                                maxBarThickness: 44,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            indexAxis: horizontal ? 'y' : 'x',
                            plugins: { legend: { display: false }, tooltip: this.tooltip() },
                            scales: {
                                [valor]: {
                                    beginAtZero: true,
                                    border: { display: false },
                                    grid: { color: c.rejilla },
                                    ticks: { precision: 0, color: c.texto },
                                },
                                [categoria]: {
                                    border: { display: false },
                                    grid: { display: false },
                                    ticks: { color: c.texto },
                                },
                            },
                        },
                    };
                },

                dona(id, c, colores) {
                    const datos = datosGraficas[id];
                    return {
                        type: 'doughnut',
                        data: {
                            labels: Object.keys(datos),
                            datasets: [{
                                data: Object.values(datos),
                                backgroundColor: colores,
                                borderColor: c.fondo,
                                borderWidth: 3,
                                hoverOffset: 6,
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            cutout: '65%',
                            plugins: {
                                tooltip: this.tooltip(),
                                legend: {
                                    position: 'bottom',
                                    labels: { color: c.texto, usePointStyle: true,
                                              pointStyle: 'circle', padding: 16,
                                              boxWidth: 8, boxHeight: 8 },
                                },
                            },
                        },
                    };
                },

                dibujar(oscuro) {
                    const c = this.paleta(oscuro);

                    Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
                    Chart.defaults.color = c.texto;

                    for (const g of graficasPQRS) g.destroy();
                    graficasPQRS = [];

                    const montar = (id, cfg) => {
                        const el = document.getElementById(id);
                        if (!el || !this.hayDatos(id)) return;
                        // Red de seguridad: si quedó un gráfico atado a este lienzo,
                        // Chart.js rechazaría el nuevo con "Canvas is already in use".
                        Chart.getChart(el)?.destroy();
                        graficasPQRS.push(new Chart(el.getContext('2d'), cfg));
                    };

                    montar('chartTecnicos', this.barras('chartTecnicos', c));
                    montar('chartMotivos',  this.barras('chartMotivos', c, true));
                    montar('chartAcceso',   this.dona('chartAcceso', c, [c.verde, c.rojo, c.ambar]));
                    montar('chartTiempos',  this.dona('chartTiempos', c, [c.cian, c.rojo]));
                },
            }));
        });
    </script>
@endsection
