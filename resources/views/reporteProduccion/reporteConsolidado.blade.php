@extends('layouts.tw.app')

@section('title', 'Reporte consolidado')

@section('content_header')
    <h1>Reporte consolidado</h1>
@endsection

@section('subtitle', 'Producción, metas y cumplimiento del año, con desglose por tipo de trabajo y por zona.')

@section('actions')
    <a href="{{ route('fechasProduccion.registrar') }}" class="tw-btn-secondary"
       title="Los valores de este reporte dependen de los precios parametrizados">
        <i class="fas fa-sliders"></i> Parametrizar precios
    </a>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="reporteConsolidado({
            urls: {
                consolidado:  '{{ route('nomina.generarReporteConsolidado') }}',
                porMes:       '{{ route('produccion.generarReportePorMes') }}',
                guardarMetas: '{{ route('produccion.insertarMetas') }}',
            },
            {{-- strval: PHP convierte '-10', '-11' y '-12' en claves enteras, y al
                 voltear el arreglo esos sufijos llegarían al JS como números. --}}
            sufijos: {{ Js::from(array_map('strval', array_flip($meses))) }},
         })"
         class="space-y-6">

        {{-- ============================== FILTROS ============================= --}}
        <section class="tw-card p-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[200px_1fr] lg:items-end">
                <div>
                    <label class="tw-label" for="selectorAnio">Año</label>
                    <select class="tw-select" id="selectorAnio" x-model="anio" @change="cargar()">
                        <option value="">Seleccione un año</option>
                        @for ($ano = $currentYear; $ano >= 2023; $ano--)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endfor
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:justify-end">
                    <button type="button" class="tw-btn-primary" @click="exportar()"
                            :disabled="!hayDatos || cargando">
                        <i class="fas" :class="cargando ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                        Exportar a Excel
                    </button>
                </div>
            </div>

            <p class="tw-hint mt-3" x-show="!hayDatos" x-cloak>
                <i class="fas fa-circle-info"></i>
                Selecciona un año para ver el consolidado.
            </p>
        </section>

        {{-- =========================== CONSOLIDADO =========================== --}}
        <section class="tw-card" x-show="hayDatos" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-table-list"></i></span>
                    <div>
                        <h2 class="tw-card-title">Reporte consolidado</h2>
                        <p class="tw-card-subtitle">
                            <strong>Meta E&amp;C</strong> y <strong>Meta GDO</strong> son editables;
                            el cumplimiento se recalcula al momento.
                        </p>
                    </div>
                </div>
            </div>

            <x-color-legend :items="[
                ['mes', 'Mes'],
                ['metaEyc', 'Meta E&C'],
                ['metaGdo', 'Meta GDO'],
                ['totales', 'Totales'],
            ]" />

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="reporteConsolidado" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        {{-- ================= TIPO DE TRABAJO + ZONA (lado a lado) ============ --}}
        <div class="grid gap-6 xl:grid-cols-[minmax(0,5fr)_minmax(0,7fr)]" x-show="hayDatos" x-cloak>

            <section class="tw-card">
                <div class="tw-card-header">
                    <div class="flex items-center gap-3">
                        <span class="tw-chip chip-sky"><i class="fas fa-list-check"></i></span>
                        <div>
                            <h2 class="tw-card-title">Reporte por tipo de trabajo</h2>
                            <p class="tw-card-subtitle">Reparto entre RP y previas, mes a mes.</p>
                        </div>
                    </div>
                </div>

                <x-color-legend :items="[['mes', 'Mes'], ['rp', 'RP'], ['previas', 'Previas']]" />

                <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                    <div id="tablaPrevias" class="ht-theme-main ht-compacta"></div>
                </div>
            </section>

            <section class="tw-card">
                <div class="tw-card-header">
                    <div class="flex items-center gap-3">
                        <span class="tw-chip chip-emerald"><i class="fas fa-map-location-dot"></i></span>
                        <div>
                            <h2 class="tw-card-title">Reporte por zona</h2>
                            <p class="tw-card-subtitle">Inspecciones y facturación del mes elegido.</p>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                    <label class="tw-label" for="selectorMes">Mes</label>
                    <select class="tw-select sm:max-w-xs" id="selectorMes" x-model="mes" @change="cargarMes()">
                        <option value="">Seleccione un mes</option>
                        @foreach ($meses as $sufijo => $nombre)
                            <option value="{{ $sufijo }}">{{ $nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div x-show="hayMes" x-cloak>
                    <x-color-legend :items="[
                        ['residencial', 'Residencial'],
                        ['comercial', 'Comercial'],
                        ['totales', 'Totales'],
                    ]" />

                    <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                        <div id="reportePorMes" class="ht-theme-main ht-compacta"></div>
                    </div>
                </div>

                <p class="tw-hint px-5 pb-5" x-show="!hayMes" x-cloak>
                    <i class="fas fa-circle-info"></i>
                    Elige un mes para ver el desglose por zona.
                </p>
            </section>
        </div>

        {{-- Velo de carga --}}
        <div x-show="cargando" x-cloak
             class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-2xl dark:bg-slate-800">
                <i class="fas fa-spinner fa-spin mb-3 block text-3xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Cargando información…</p>
            </div>
        </div>
    </div>
@endsection

@push('libs')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
@endpush

@section('js')
    @include('reporteProduccion.partials.consolidado-script')
@endsection
