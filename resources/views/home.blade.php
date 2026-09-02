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
        ['tipo' => 'inspectores', 'label' => 'Inspectores operaron', 'valor' => $metricas['inspectores'],
         'icon' => 'fa-user-tie', 'tint' => 'blue',    'titulo' => 'Inspectores que operaron'],
        ['tipo' => 'ejecutadas',  'label' => 'Ejecutado (cierres efectivos)', 'valor' => $metricas['ejecutadas'],
         'icon' => 'fa-circle-check', 'tint' => 'emerald', 'titulo' => 'Tareas Efectivas'],
        ['tipo' => 'pendientes_legalizar', 'label' => 'Pendiente x legalizar', 'valor' => $metricas['pendientes_legalizar'],
         'icon' => 'fa-file-signature', 'tint' => 'amber', 'titulo' => 'Pendientes por Legalizar'],
        ['tipo' => 'prioridades', 'label' => 'Prioridades pendientes', 'valor' => $metricas['prioridades'],
         'icon' => 'fa-triangle-exclamation', 'tint' => 'rose', 'titulo' => 'Prioridades Pendientes por Legalizar (>= 60 Meses)'],
    ];

    /* Fila secundaria: contexto y acumulados. */
    $kpisSec = [
        ['tipo' => 'programadas', 'label' => 'Programadas por el inspector', 'valor' => $metricas['programadas'],
         'icon' => 'fa-calendar-day', 'tint' => 'sky', 'nota' => $fecha->translatedFormat('d M Y'),
         'titulo' => 'Programadas para hoy'],
        ['tipo' => 'fallidas', 'label' => 'Tareas fallidas', 'valor' => $metricas['fallidas'],
         'icon' => 'fa-circle-xmark', 'tint' => 'slate', 'nota' => 'en la fecha seleccionada',
         'titulo' => 'Tareas Fallidas'],
        ['tipo' => 'pendientes_legalizar_acumulado', 'label' => 'Pendiente x legalizar acumulado',
         'valor' => $metricas['pendientes_legalizar_acumulado'], 'icon' => 'fa-clock-rotate-left', 'tint' => 'amber',
         'nota' => $rotuloAcumulado, 'titulo' => "Pendientes por Legalizar acumulados ($rotuloAcumulado)"],
        ['tipo' => 'prioridades_acumulado', 'label' => 'Prioridades acumuladas',
         'valor' => $metricas['prioridades_acumulado'], 'icon' => 'fa-clock-rotate-left', 'tint' => 'rose',
         'nota' => $rotuloAcumulado, 'titulo' => "Prioridades Pendientes acumuladas ($rotuloAcumulado)"],
    ];
@endphp

@section('actions')
    {{-- Filtro de fecha y localidad --}}
    <form action="{{ route('home') }}" method="GET"
          class="flex flex-wrap items-center gap-2 rounded-2xl border border-slate-200/80 bg-white p-2 shadow-sm
                 dark:border-slate-700/60 dark:bg-slate-800">

        <label class="relative">
            <span class="sr-only">Fecha de operación</span>
            <i class="fas fa-calendar-day pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <input type="date" name="fecha_reporte" value="{{ $fechaReporte }}"
                   class="tw-input w-[10.5rem] border-transparent bg-slate-50 py-2 pl-9 pr-3 font-medium shadow-none
                          dark:bg-slate-900/60">
        </label>

        <label class="relative">
            <span class="sr-only">Municipio</span>
            <i class="fas fa-location-dot pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
            <select name="localidad_reporte"
                    class="tw-select w-[14rem] border-transparent bg-slate-50 py-2 pl-9 font-medium shadow-none
                           dark:bg-slate-900/60">
                <option value="TODAS" @selected($localidadSeleccionada === 'TODAS')>Todas las localidades</option>
                @foreach ($localidadesDisponibles as $loc)
                    <option value="{{ $loc }}" @selected($localidadSeleccionada === $loc)>{{ $loc }}</option>
                @endforeach
            </select>
        </label>


        <button type="submit" class="tw-btn-dark">
            <i class="fas fa-magnifying-glass"></i> Filtrar
        </button>
    </form>

    @haspermission('ver_residente')
        <button type="button" @click="$dispatch('abrir-cargue')" class="tw-btn-primary">
            <i class="fas fa-cloud-arrow-up"></i> Cargar Datos OSF
        </button>
    @endhaspermission
@endsection

@section('content')
<div x-data="dashboard({
        detalles: @js($detalles),
        programaciones: @js($detallesProgramaciones ?? []),
        meses: @js($mesesData ?? []),
        tecnicos: @js($todos_los_tecnicos->map(fn ($t) => [
            'id' => $t->id,
            'nombre' => $t->NOMBRE_COMPLETO,
            'asignado_en' => $t->asignado_en,
        ])->values()),
     })"
     class="space-y-6">

    {{-- ============ INDICADORES DEL DÍA ============ --}}
    <section aria-label="Indicadores del día">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpis as $kpi)
                <button type="button"
                        @click="verDetalle('{{ $kpi['tipo'] }}', @js($kpi['titulo']))"
                        class="tw-card group p-5 text-left transition hover:-translate-y-0.5 hover:shadow-[0_4px_12px_rgb(16_24_40/0.06),0_12px_28px_rgb(16_24_40/0.08)]">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tw-eyebrow max-w-[9rem]">{{ $kpi['label'] }}</span>
                        <span class="tw-chip chip-{{ $kpi['tint'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <p class="tw-metric mt-4">{{ number_format($kpi['valor'], 0, ',', '.') }}</p>
                    <p class="mt-3 text-xs font-medium text-brand-600 opacity-0 transition group-hover:opacity-100">
                        Ver detalle <i class="fas fa-arrow-right text-[10px]"></i>
                    </p>
                </button>
            @endforeach
        </div>
    </section>

    {{-- ============ CONTEXTO Y ACUMULADOS ============ --}}
    <section aria-label="Acumulados">
        <div class="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
            @foreach ($kpisSec as $kpi)
                <button type="button"
                        @click="verDetalle('{{ $kpi['tipo'] }}', @js($kpi['titulo']))"
                        class="rounded-2xl border p-5 text-left transition hover:-translate-y-0.5 tint-{{ $kpi['tint'] }}">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tw-eyebrow max-w-[9rem]">{{ $kpi['label'] }}</span>
                        <span class="tw-chip chip-{{ $kpi['tint'] }}"><i class="fas {{ $kpi['icon'] }}"></i></span>
                    </div>
                    <p class="mt-4 text-3xl font-bold leading-none tracking-tight text-slate-900 dark:text-white">
                        {{ number_format($kpi['valor'], 0, ',', '.') }}
                    </p>
                    <p class="mt-2.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ $kpi['nota'] }}</p>
                </button>
            @endforeach
        </div>
    </section>

    {{-- ============ GRÁFICA + PROGRAMACIONES ============ --}}
    <div class="grid gap-6 xl:grid-cols-5">

        {{-- Meses ejecutados --}}
        <section class="tw-card xl:col-span-2">
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
                <div class="h-[300px]" x-show="tieneMeses">
                    <canvas x-ref="chartMeses" role="img" aria-label="Gráfica de meses ejecutados"></canvas>
                </div>
                <p x-show="!tieneMeses" x-cloak class="py-20 text-center text-sm text-slate-400">
                    Sin datos para la fecha seleccionada.
                </p>
            </div>
        </section>

        {{-- Programaciones del día --}}
        <section class="tw-card xl:col-span-3">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-calendar-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Programaciones para el Día</h2>
                        <p class="tw-card-subtitle">
                            {{ $fecha->format('d/m/Y') }}{{ $localidadSeleccionada !== 'TODAS' ? ' · '.$localidadSeleccionada : '' }}
                        </p>
                    </div>
                </div>
                <div class="flex gap-2">
                    <span class="pill-emerald">{{ number_format($totalesProg['ejecutadas'], 0, ',', '.') }} ejecutadas</span>
                    <span class="pill-rose">{{ number_format($totalesProg['pendientes'], 0, ',', '.') }} pendientes</span>
                </div>
            </div>

            <div class="max-h-[320px] overflow-auto">
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
                        @forelse ($estadisticasProgramadas as $est)
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-briefcase mr-2 text-slate-300"></i>{{ $est['tipo'] }}
                                </td>
                                <td class="text-right tabular-nums">{{ number_format($est['total'], 0, ',', '.') }}</td>
                                <td class="text-right">
                                    <button type="button" class="pill-emerald tabular-nums hover:ring-2 hover:ring-emerald-300"
                                            @click="verProgramacion(@js($est['tipo']), 'ejecutadas')">
                                        {{ number_format($est['ejecutadas'], 0, ',', '.') }} <i class="fas fa-magnifying-glass text-[10px]"></i>
                                    </button>
                                </td>
                                <td class="text-right">
                                    <button type="button" class="pill-rose tabular-nums hover:ring-2 hover:ring-rose-300"
                                            @click="verProgramacion(@js($est['tipo']), 'pendientes')">
                                        {{ number_format($est['pendientes'], 0, ',', '.') }} <i class="fas fa-magnifying-glass text-[10px]"></i>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="py-12 text-center text-slate-400">
                                No hay programaciones para la fecha seleccionada.
                            </td></tr>
                        @endforelse
                    </tbody>
                    @if (count($estadisticasProgramadas))
                        <tfoot>
                            <tr>
                                <td class="text-right uppercase tracking-wide text-slate-500">Total</td>
                                <td class="text-right tabular-nums">{{ number_format($totalesProg['programadas'], 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums text-emerald-700">{{ number_format($totalesProg['ejecutadas'], 0, ',', '.') }}</td>
                                <td class="text-right tabular-nums text-rose-700">{{ number_format($totalesProg['pendientes'], 0, ',', '.') }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </section>
    </div>

    {{-- ============ PENDIENTES EN BASE + FUERZA DE TRABAJO ============ --}}
    <div class="grid gap-6 lg:grid-cols-2">

        {{-- Pendientes en base --}}
        <section class="tw-card" x-data="{ vista: 'tipos' }">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-sky"><i class="fas fa-layer-group"></i></span>
                    <div>
                        <h2 class="tw-card-title">Pendientes en Base</h2>
                        <p class="tw-card-subtitle">
                            Sin recepcionar · {{ number_format($baseTotalTipos, 0, ',', '.') }}
                            de {{ number_format($baseTotalTabla, 0, ',', '.') }} registros
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

            <div class="max-h-[340px] overflow-auto">
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

        {{-- Fuerza de trabajo --}}
        @php
            $granTotalTecnicos = collect($tecnicos_por_localidad)->sum(fn ($t) => $t->count());
        @endphp
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-violet"><i class="fas fa-users-gear"></i></span>
                    <div>
                        <h2 class="tw-card-title">Fuerza de Trabajo por Localidad</h2>
                        <p class="tw-card-subtitle">{{ $granTotalTecnicos }} técnicos asignados actualmente</p>
                    </div>
                </div>

                @can('ver_coordinacion_RP')
                    <button type="button" @click="abrirAsignacion()" class="tw-btn-secondary tw-btn-sm">
                        <i class="fas fa-plus"></i> Asignar
                    </button>
                @endcan
            </div>

            <div class="max-h-[340px] overflow-auto">
                <table class="tw-table tw-table-fija">
                    <thead class="sticky top-0 z-10">
                        <tr>
                            <th>Localidad</th>
                            <th class="text-right">Técnicos</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($tecnicos_por_localidad as $nombreLocalidad => $tecnicos)
                            <tr>
                                <td class="font-medium text-slate-800 dark:text-slate-200">
                                    <i class="fas fa-location-dot mr-2 text-slate-300"></i>{{ $nombreLocalidad }}
                                </td>
                                <td class="text-right"><span class="pill-violet tabular-nums">{{ $tecnicos->count() }}</span></td>
                                <td>
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                @click="verTecnicos(@js($nombreLocalidad), @js($tecnicos->map(fn ($t) => [
                                                    'nombre' => $t->NOMBRE_COMPLETO ?? 'Nombre no registrado',
                                                    'supervisor' => $t->supervisor->name ?? '—',
                                                    'id' => $t->ID_TECNICO,
                                                ])->values()))">
                                            <i class="fas fa-eye"></i> Ver
                                        </button>
                                        @can('ver_coordinacion_RP')
                                            <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                    @click="abrirAsignacion(@js($nombreLocalidad), @js($tecnicos->pluck('ID_TECNICO')->values()))">
                                                <i class="fas fa-pen"></i>
                                            </button>
                                        @endcan
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-12 text-center text-slate-400">Aún no hay técnicos asignados.</td></tr>
                        @endforelse
                    </tbody>
                    @if (count($tecnicos_por_localidad))
                        <tfoot>
                            <tr>
                                <td class="text-right uppercase tracking-wide text-slate-500">Total</td>
                                <td class="text-right"><span class="pill-violet tabular-nums">{{ $granTotalTecnicos }}</span></td>
                                <td></td>
                            </tr>
                        </tfoot>
                    @endif
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
