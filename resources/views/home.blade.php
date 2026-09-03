@extends('layouts.tw.app')

@section('title', 'Panel de Control')
@section('content_header')
    <h1>Panel de Control</h1>
@endsection
@section('subtitle', 'Resumen operativo de revisiones periódicas.')

@php
    use Illuminate\Support\Carbon;

    $fecha = Carbon::parse($fechaReporte);
    $rotuloAcumulado = $acumuladoDesde
        ? 'desde ' . Carbon::parse($acumuladoDesde)->format('d/m/Y')
        : 'sin histórico previo';

    /* Fila superior: los cuatro indicadores del día. */
    $kpis = [
        ['tipo' => 'inspectores', 'label' => 'Inspectores operaron',          'icon' => 'fa-user-tie', 'tint' => 'blue',    'titulo' => 'Inspectores que operaron'],
        ['tipo' => 'ejecutadas',  'label' => 'Ejecutado (cierres efectivos)',          'icon' => 'fa-circle-check', 'tint' => 'emerald', 'titulo' => 'Tareas Efectivas'],
        ['tipo' => 'pendientes_legalizar', 'label' => 'Pendiente x legalizar',          'icon' => 'fa-file-signature', 'tint' => 'amber', 'titulo' => 'Pendientes por Legalizar'],
        ['tipo' => 'prioridades', 'label' => 'Prioridades pendientes',          'icon' => 'fa-triangle-exclamation', 'tint' => 'rose', 'titulo' => 'Prioridades Pendientes por Legalizar (>= 60 Meses)'],
    ];

    /* Fila secundaria: contexto y acumulados. */
    $kpisSec = [
        ['tipo' => 'programadas', 'label' => 'Programadas por el inspector',          'icon' => 'fa-calendar-day', 'tint' => 'sky', 'nota' => null,   /* la pinta Alpine: cambia con el filtro */
         'titulo' => 'Programadas para hoy'],
        ['tipo' => 'fallidas', 'label' => 'Tareas fallidas',          'icon' => 'fa-circle-xmark', 'tint' => 'slate', 'nota' => 'en la fecha seleccionada',
         'titulo' => 'Tareas Fallidas'],
        /* Estos dos van acotados al corte de GDO, y el corte se cambia sin
           recargar: la nota la pinta Alpine para que siga al periodo. El
           rango ya no va en el título del modal, que se quedaría viejo. */
        ['tipo' => 'pendientes_legalizar_acumulado', 'label' => 'Pendiente x legalizar acumulado',
         'icon' => 'fa-clock-rotate-left', 'tint' => 'amber',
         'nota' => null, 'nota_dinamica' => 'rotuloAcumulado',
         'titulo' => 'Pendientes por Legalizar acumulados'],
        ['tipo' => 'prioridades_acumulado', 'label' => 'Prioridades acumuladas',
         'icon' => 'fa-clock-rotate-left', 'tint' => 'rose',
         'nota' => null, 'nota_dinamica' => 'rotuloAcumulado',
         'titulo' => 'Prioridades Pendientes acumuladas'],
    ];
@endphp

@section('actions')
    {{-- Filtro de fecha y localidad.

         Sigue siendo un <form> con method GET y sus name= intactos: si Alpine
         no llegara a cargar, envía como toda la vida y el servidor responde con
         la página entera. Con Alpine, @submit lo intercepta y sólo se repintan
         los indicadores, la gráfica y las ventanas de detalle. --}}
    <form action="{{ route('home') }}" method="GET"
          x-data @submit.prevent="$dispatch('filtrar-reporte')"
          class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-sm
                 dark:border-slate-700/60 dark:bg-slate-800">

        {{-- Los dos campos viven en un store porque este filtro se pinta en la
             cabecera, fuera del x-data del tablero: es lo que les permite
             hablarse. Conservan su name= para el envío sin JavaScript. --}}
        <label class="relative">
            <span class="sr-only">Fecha de operación</span>
            <i class="fas fa-calendar-day pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <input type="date" name="fecha_reporte"  x-model="$store.reporte.fecha"
                   class="tw-input w-[10.5rem] border-transparent bg-slate-50 py-2 pl-9 pr-3 font-medium shadow-none
                          dark:bg-slate-900/60">
        </label>

        <label class="relative">
            <span class="sr-only">Municipio</span>
            <i class="fas fa-location-dot pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <select name="localidad_reporte" x-model="$store.reporte.localidad"
                    class="tw-select w-[14rem] border-transparent bg-slate-50 py-2 pl-9 font-medium shadow-none
                           dark:bg-slate-900/60">
                <option value="TODAS">Todas las localidades</option>
                {{-- La lista cambia con la fecha, así que se pinta desde el store. --}}
                <template x-for="loc in $store.reporte.localidades" :key="loc">
                    <option :value="loc" x-text="loc"></option>
                </template>
            </select>
        </label>

        <button type="submit" class="tw-btn-dark" :disabled="$store.reporte.cargando">
            <i class="fas" :class="$store.reporte.cargando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
            Filtrar
        </button>
    </form>
    @haspermission('ver_residente')
    <button type="button" x-data @click="$dispatch('abrir-corte')" class="tw-btn-secondary">
        <i class="fas fa-scissors"></i> Corte GDO
    </button>


        <button type="button" @click="$dispatch('abrir-cargue')" class="tw-btn-primary">
            <i class="fas fa-cloud-arrow-up"></i> Cargar Datos OSF
        </button>
    @endhaspermission
@endsection

@section('content')
<div x-data="dashboard({
        detalles: @js($detalles),
        metricas: @js($metricas),
        urlReporte: '{{ route('home.reporte') }}',
        programaciones: @js($detallesProgramaciones ?? []),
        progInicial: {
            filas:   @js($estadisticasProgramadas),
            totales: @js($totalesProg),
            fecha:   @js(now()->format('Y-m-d')),
            ciudad:  'TODAS',
            ciudades: @js($ciudadesProgramaciones),
        },
        urlProgramaciones: '{{ route('home.programaciones') }}',
        meses: @js($mesesData ?? []),
        tecnicos: @js($catalogoTecnicos),
        fuerzaInicial: @js($fuerzaLocalidades),
        urlAsignacion: '{{ route('asignacion.guardar_tecnicos') }}',
        corteInicial: @js($corteGdo),
        urlCorte: '{{ route('corte.guardar') }}',
     })"
     @filtrar-reporte.window="filtrarReporte()"
     @abrir-corte.window="abrirCorte()"
     class="space-y-4 2xl:space-y-6">

    {{-- ============ INDICADORES DEL DÍA ============ --}}
    <section aria-label="Indicadores del día">
        {{-- Cuatro por fila desde 1024px y no desde 1280: por debajo de ese
             corte los indicadores caían de dos en dos y la página se hacía
             larguísima en portátiles. --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:gap-5">
            @foreach ($kpis as $kpi)
                <button type="button"
                        @click="verDetalle('{{ $kpi['tipo'] }}', @js($kpi['titulo']))"
                        class="tw-card group relative p-4 text-left transition 2xl:p-5 hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgb(16_24_40/0.06),0_12px_28px_rgb(16_24_40/0.08)]">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tw-eyebrow max-w-[9rem]">{{ $kpi['label'] }}</span>
                        <span class="tw-chip chip-{{ $kpi['tint'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <p class="tw-metric mt-3" x-text="formatoMiles(metricas['{{ $kpi['tipo'] }}'])"></p>
                    {{-- Va superpuesto: como sólo se ve al pasar por encima, si
                         ocupara sitio estaría estirando la tarjeta todo el rato. --}}
                    <p class="pointer-events-none absolute bottom-3 left-4 text-xs font-medium text-brand-600
                              opacity-0 transition group-hover:opacity-100 2xl:left-5">
                        Ver detalle <i class="fas fa-arrow-right text-[0.625rem]"></i>
                    </p>
                </button>
            @endforeach
        </div>
    </section>

    {{-- ============ CORTE DE GDO ============ ---
         El periodo sobre el que se mide la legalización. Manda sobre tres
         cifras: lo legalizado dentro del corte y los dos acumulados de la
         fila de abajo, que arrancan en su fecha de inicio. --}}
    <section aria-label="Corte de GDO">
        <div class="tw-card flex flex-wrap items-center gap-4 p-4 2xl:p-5">

            <span class="tw-chip chip-violet shrink-0"><i class="fas fa-scissors"></i></span>

            {{-- Periodo --}}
            <div class="min-w-[12rem] flex-1">
                <p class="tw-eyebrow">Corte de GDO</p>
                <template x-if="corte">
                    <p class="mt-1 text-sm font-semibold text-slate-900 dark:text-white">
                        <span x-text="corte.inicio_mostrado"></span>
                        <span class="mx-1 text-slate-400">&rarr;</span>
                        <span x-text="corte.fin_mostrado"></span>
                    </p>
                </template>
                <template x-if="!corte">
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Sin definir. Los acumulados van sobre todo el histórico.
                    </p>
                </template>
            </div>

            {{-- Días que faltan --}}
            <template x-if="corte">
                <div class="shrink-0">
                    <span class="pill-rose" x-show="corte.cerrado" x-cloak>
                        <i class="fas fa-flag-checkered text-[0.625rem]"></i> Corte cerrado
                    </span>
                    <span class="pill-amber" x-show="!corte.cerrado && corte.dias_restantes === 0" x-cloak>
                        <i class="fas fa-hourglass-end text-[0.625rem]"></i> Último día
                    </span>
                    <span class="pill-emerald" x-show="!corte.cerrado && corte.dias_restantes > 0" x-cloak>
                        <i class="fas fa-hourglass-half text-[0.625rem]"></i>
                        <span x-text="`Faltan ${corte.dias_restantes} ${corte.dias_restantes === 1 ? 'día' : 'días'}`"></span>
                    </span>
                </div>
            </template>

            {{-- Legalizado dentro del corte --}}
            <button type="button" @click="verLegalizado()"
                    class="group shrink-0 rounded-xl border border-slate-200 px-4 py-2.5 text-left transition
                           hover:border-brand-400 hover:bg-brand-50/40
                           dark:border-slate-700 dark:hover:bg-slate-700/40">
                <p class="tw-eyebrow">Legalizado en el corte</p>
                <p class="mt-1 text-2xl font-bold leading-none tracking-tight text-slate-900 dark:text-white"
                   x-text="formatoMiles(metricas['legalizado_corte'])"></p>
            </button>
            @haspermission('ver_residente')
            <button type="button" @click="abrirCorte()" class="tw-btn-secondary shrink-0">
                <i class="fas fa-pen"></i>
                <span x-text="corte ? 'Cambiar corte' : 'Definir corte'"></span>
            </button>
            @endhaspermission
        </div>
    </section>

    {{-- ============ CONTEXTO Y ACUMULADOS ============ --}}
    <section aria-label="Acumulados">
        {{-- Cuatro por fila desde 1024px y no desde 1280: por debajo de ese
             corte los indicadores caían de dos en dos y la página se hacía
             larguísima en portátiles. --}}
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4 2xl:gap-5">
            @foreach ($kpisSec as $kpi)
                <button type="button"
                        @click="verDetalle('{{ $kpi['tipo'] }}', @js($kpi['titulo']))"
                        class="rounded-2xl border p-4 text-left transition hover:-translate-y-0.5 2xl:p-5 tint-{{ $kpi['tint'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tw-eyebrow max-w-[9rem]">{{ $kpi['label'] }}</span>
                        <span class="tw-chip chip-{{ $kpi['tint'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <p class="mt-3 text-2xl font-bold leading-none tracking-tight text-slate-900 dark:text-white 2xl:text-3xl"
                       x-text="formatoMiles(metricas['{{ $kpi['tipo'] }}'])"></p>
                    @if (isset($kpi['nota_dinamica']))
                        <p class="mt-2.5 truncate text-xs text-slate-500 dark:text-slate-400"
                           x-text="{{ $kpi['nota_dinamica'] }}"></p>
                    @elseif ($kpi['nota'] === null)
                        <p class="mt-2.5 truncate text-xs text-slate-500 dark:text-slate-400"
                           x-text="fechaReporteMostrada"></p>
                    @else
                        <p class="mt-2.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $kpi['nota'] }}</p>
                    @endif
                </button>
            @endforeach
        </div>
    </section>

    {{-- ============ GRÁFICA + PROGRAMACIONES ============ --}}
    <div class="grid gap-4 lg:grid-cols-5 2xl:gap-6">

        {{-- Meses ejecutados --}}
        <section class="tw-card lg:col-span-2">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-emerald"><i class="fas fa-chart-column"></i></span>
                    <div>
                        <h2 class="tw-card-title">Meses Ejecutados</h2>
                        <p class="tw-card-subtitle">Tareas efectivas por mes de vencimiento</p>
                    </div>
                </div>
            </div>
            <div class="tw-card-body">
                <div class="h-[18.75rem]" x-show="tieneMeses">
                    <canvas x-ref="chartMeses" role="img" aria-label="Gráfica de meses ejecutados"></canvas>
                </div>
                <p x-show="!tieneMeses" x-cloak class="py-20 text-center text-sm text-slate-400">
                    Sin datos para la fecha seleccionada.
                </p>
            </div>
        </section>

        {{-- Programaciones del día.
             Tiene filtro propio, independiente del de la cabecera: se consulta
             por FECHA_AGENDAMIENTO y CIUDAD de tbl_programacion_contratos y se
             refresca sola, sin recargar la página. --}}
        <section class="tw-card lg:col-span-3">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-calendar-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Programaciones para el Día</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="progFechaMostrada"></span>
                            <span x-show="progCiudadAplicada !== 'TODAS'" x-cloak
                                  x-text="` · ${progCiudadAplicada}`"></span>
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <span class="pill-emerald" x-text="`${formatoMiles(progTotales.ejecutadas)} ejecutadas`"></span>
                    <span class="pill-rose" x-text="`${formatoMiles(progTotales.pendientes)} pendientes`"></span>
                </div>
            </div>

            {{-- Filtro propio de la tarjeta --}}
            <div class="flex flex-wrap items-end gap-3 border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                <div class="w-full sm:w-44">
                    <label class="tw-label" for="prog_fecha">Fecha de agendamiento</label>
                    <input type="date" id="prog_fecha" class="tw-input py-2" x-model="progFecha" required>
                </div>

                <div class="w-full sm:w-60">
                    <label class="tw-label" for="prog_ciudad">Municipio</label>
                    {{-- Sólo los municipios con programación ese día, así que la
                         lista se repinta con cada filtrado. --}}
                    <select id="prog_ciudad" class="tw-select py-2" x-model="progCiudad">
                        <option value="TODAS">Todos los municipios</option>
                        <template x-for="c in progCiudades" :key="c">
                            <option :value="c" x-text="c"></option>
                        </template>
                    </select>
                </div>

                <button type="button" class="tw-btn-primary" @click="filtrarProgramaciones()"
                        :disabled="progCargando">
                    <i class="fas" :class="progCargando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                    Filtrar
                </button>

                <button type="button" class="tw-btn-secondary" @click="limpiarProgramaciones()"
                        x-show="progFiltroTocado" x-cloak>
                    <i class="fas fa-rotate-left"></i> Volver al principal
                </button>

                <p x-show="progError" x-cloak
                   class="w-full rounded-xl border border-red-200 bg-red-50 px-4 py-2.5 text-sm text-red-800
                          dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                   x-text="progError"></p>
            </div>

            {{-- La tabla se pinta desde Alpine, no desde Blade: es lo que le
                 permite cambiar sin recargar. Arranca con los datos que trae el
                 servidor, así que la primera pintura es igual que antes. --}}
            <div class="relative max-h-[20rem] tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th>Tipo de trabajo</th>
                            <th class="text-right">Programadas</th>
                            <th class="text-right">Ejecutadas</th>
                            <th class="text-right">Pendientes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="est in progFilas" :key="est.tipo">
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-briefcase mr-2 text-slate-300"></i><span x-text="est.etiqueta ?? est.tipo"></span>
                                </td>
                                <td class="text-right tabular-nums" x-text="formatoMiles(est.total)"></td>
                                <td class="text-right">
                                    <button type="button" class="pill-emerald tabular-nums hover:ring-2 hover:ring-emerald-300"
                                            @click="verProgramacion(est.tipo, 'ejecutadas')">
                                        <span x-text="formatoMiles(est.ejecutadas)"></span>
                                        <i class="fas fa-magnifying-glass text-[0.625rem]"></i>
                                    </button>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="pill-rose tabular-nums hover:ring-2 hover:ring-rose-300"
                                            @click="verProgramacion(est.tipo, 'pendientes')">
                                        <span x-text="formatoMiles(est.pendientes)"></span>
                                        <i class="fas fa-magnifying-glass text-[0.625rem]"></i>
                                    </button>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!progFilas.length" x-cloak>
                            <td colspan="4" class="py-12 text-center text-slate-400">
                                No hay programaciones para la fecha seleccionada.
                            </td>
                        </tr>
                    </tbody>
                    <tfoot x-show="progFilas.length" x-cloak>
                        <tr>
                            <td class="text-right uppercase tracking-wide text-slate-500">Total</td>
                            <td class="text-right tabular-nums" x-text="formatoMiles(progTotales.programadas)"></td>
                            <td class="text-right tabular-nums text-emerald-700" x-text="formatoMiles(progTotales.ejecutadas)"></td>
                            <td class="text-right tabular-nums text-rose-700" x-text="formatoMiles(progTotales.pendientes)"></td>
                        </tr>
                    </tfoot>
                </table>

                <div x-show="progCargando" x-cloak
                     class="absolute inset-0 flex items-center justify-center bg-white/70 backdrop-blur-[1px]
                            dark:bg-slate-800/70">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
                </div>
            </div>
        </section>
    </div>

    {{-- ============ PENDIENTES EN BASE + FUERZA DE TRABAJO ============ --}}
    <div class="grid gap-4 md:grid-cols-2 2xl:gap-6">

        {{-- Pendientes en base --}}
        <section class="tw-card" x-data="{ vista: 'tipos' }">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-sky"><i class="fas fa-layer-group"></i></span>
                    <div>
                        <h2 class="tw-card-title">Pendientes en Base</h2>
                        {{-- La fuente es tbl_asignaciones, la foto de las órdenes
                             abiertas: toda fila cuenta, así que ya no hay un
                             total mayor del que descontar recepcionadas. --}}
                        <p class="tw-card-subtitle">
                            Órdenes abiertas · {{ number_format($baseTotalTipos, 0, ',', '.') }}
                        </p>
                    </div>
                </div>

                {{-- Conmutador de vista --}}
                <div class="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-900/50" role="tablist">
                    @foreach (['tipos' => 'Tipo de trabajo', 'meses' => 'Meses'] as $k => $etiqueta)
                        <button type="button" role="tab" @click="vista = '{{ $k }}'"
                                :aria-selected="vista === '{{ $k }}'"
                                class="rounded-lg px-3 py-1.5 text-xs font-semibold transition"
                                :class="vista === '{{ $k }}'
                                    ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                                    : 'text-slate-500 hover:text-slate-700'">{{ $etiqueta }}</button>
                    @endforeach
                </div>
            </div>

            <div class="max-h-[21.25rem] tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th x-text="vista === 'tipos' ? 'Tipo de trabajo' : 'Meses de vencimiento'"></th>
                            <th class="text-right">Cantidad</th>
                        </tr>
                    </thead>

                    <tbody x-show="vista === 'tipos'">
                        @forelse ($baseTipos as $fila)
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-briefcase mr-2 text-slate-300"></i>{{ $fila['etiqueta'] }}
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($fila['cantidad'], 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="2" class="py-12 text-center text-slate-400">Sin pendientes registrados.</td></tr>
                        @endforelse
                    </tbody>

                    <tbody x-show="vista === 'meses'" x-cloak>
                        @foreach ($baseMeses as $fila)
                            @php $critico = in_array($fila['rango'], ['60', '60 +']) && $fila['cantidad'] > 0; @endphp
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-hourglass-half mr-2 text-slate-300"></i>{{ $fila['rango'] }}
                                </td>
                                <td @class([
                                    'text-right tabular-nums',
                                    'font-bold text-rose-600' => $critico,
                                ])>{{ number_format($fila['cantidad'], 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>

                    <tfoot>
                        <tr>

                            <td class="text-right uppercase tracking-wide text-slate-500">Total</td>
                            <td class="text-right tabular-nums">
                                <span x-show="vista === 'tipos'">{{ number_format($baseTotalTipos, 0, ',', '.') }}</span>
                                <span x-show="vista === 'meses'" x-cloak>{{ number_format($baseTotalMeses, 0, ',', '.') }}</span>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>

        {{-- Fuerza de trabajo.
             La pinta Alpine desde `fuerza`, no Blade: al guardar una
             asignación la tabla se rehace con lo que devuelve el servidor,
             sin recargar la página. --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-violet"><i class="fas fa-users-gear"></i></span>
                    <div>
                        <h2 class="tw-card-title">Fuerza de Trabajo por Localidad</h2>
                        <p class="tw-card-subtitle" x-text="`${fuerzaTotal} técnicos asignados actualmente`"></p>
                    </div>
                </div>

                @can('ver_coordinacion_RP')
                    <button type="button" @click="abrirAsignacion()" class="tw-btn-secondary tw-btn-sm">
                        <i class="fas fa-plus"></i> Asignar
                    </button>
                @endcan
            </div>

            <div class="max-h-[21.25rem] tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th>Localidad</th>
                            <th class="text-right">Técnicos</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <template x-for="loc in fuerza" :key="loc.localidad">
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-location-dot mr-2 text-slate-300"></i><span x-text="loc.localidad"></span>
                                </td>
                                <td class="text-right"><span class="pill-violet tabular-nums" x-text="loc.total"></span></td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                @click="verTecnicos(loc.localidad, loc.tecnicos)">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        @can('ver_coordinacion_RP')
                                            <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                    @click="abrirAsignacion(loc.localidad, loc.ids)">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="!fuerza.length">
                            <td colspan="3" class="py-12 text-center text-slate-400">Aún no hay técnicos asignados.</td>
                        </tr>
                    </tbody>

                    <tfoot x-show="fuerza.length">
                        <tr>
                            <td class="text-right uppercase tracking-wide text-slate-500">Total</td>
                            <td class="text-right"><span class="pill-violet tabular-nums" x-text="fuerzaTotal"></span></td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </section>
    </div>

    @include('home.partials.modales')
</div>
@endsection

@section('js')
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    @include('home.partials.script')

@endsection
