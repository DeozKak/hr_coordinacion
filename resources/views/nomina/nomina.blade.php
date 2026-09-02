@extends('layouts.tw.app')

@section('title', 'Nómina')

@section('content_header')
    <h1>Nómina</h1>
@endsection

@section('subtitle', 'Bonificaciones, multas y costos de proyecto del corte.')

@section('actions')
    <a href="{{ route('nomina.parametrizarSalarioAux') }}" class="tw-btn-secondary"
       title="El reporte usa el salario mínimo, el auxilio de transporte y los porcentajes de aportes">
        <i class="fas fa-sliders"></i> Parametrizar salario y aportes
    </a>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="reporteNomina({
            urls: {
                generar: '{{ route('nomina.generarReporteNomina') }}',
                multa:   '{{ route('nomina.guardarMultaRodamiento') }}',
            },
         })"
         class="space-y-6">

        {{-- =============================== FILTRO ============================ --}}
        <section class="tw-card p-5">
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-56">
                    <label class="tw-label" for="mesAnio">Mes del corte</label>
                    <input type="month" id="mesAnio" class="tw-input" x-model="mesAnio">
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:ml-auto sm:w-auto">
                    <button type="button" class="tw-btn-secondary" @click="exportar()"
                            :disabled="!hayDatos || cargando">
                        <i class="fas fa-file-excel"></i> Descargar Excel
                    </button>
                    <button type="button" class="tw-btn-primary" @click="generar()" :disabled="cargando">
                        <i class="fas" :class="cargando ? 'fa-spinner fa-spin' : 'fa-gears'"></i>
                        Generar reporte
                    </button>
                </div>
            </div>

            <p class="tw-hint mt-3" x-show="!hayDatos" x-cloak>
                <i class="fas fa-circle-info"></i>
                Selecciona el mes del corte y pulsa Generar reporte.
            </p>
        </section>

        {{-- ============================== NÓMINA ============================= --}}
        <section class="tw-card" x-show="hayDatos" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-sack-dollar"></i></span>
                    <div>
                        <h2 class="tw-card-title" x-text="titulo"></h2>
                        <p class="tw-card-subtitle">
                            La columna <strong>MULTAS</strong> es editable; el resto se calcula.
                        </p>
                    </div>
                </div>
            </div>

            <x-color-legend :items="[
                ['copas', 'Copas'],
                ['bonificacion', 'Total bonificación'],
                ['multas', 'Multas'],
                ['bonoComercial', 'Bono comercial'],
                ['totales', 'Totales'],
            ]" />

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaNomina" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        {{-- ========================== COSTOS PROYECTO ======================== --}}
        <section class="tw-card" x-show="hayDatos" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-emerald"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div>
                        <h2 class="tw-card-title">Costos de proyecto</h2>
                        <p class="tw-card-subtitle">
                            Salario, auxilio y aportes por inspector, según los parámetros del periodo.
                        </p>
                    </div>
                </div>
            </div>

            <x-color-legend :items="[
                ['aprendiz', 'Aprendiz'],
                ['costoTotal', 'Total'],
                ['totales', 'Totales'],
            ]" />

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaCostosProyecto" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        {{-- Velo de carga --}}
        <div x-show="cargando || guardandoMulta" x-cloak
             class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-2xl dark:bg-slate-800">
                <i class="fas fa-spinner fa-spin mb-3 block text-3xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300"
                   x-text="guardandoMulta ? 'Guardando la multa…' : 'Generando el reporte…'"></p>
            </div>
        </div>
    </div>
@endsection

@push('libs')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.17.0/xlsx.full.min.js"></script>
@endpush

@section('js')
    @include('nomina.partials.nomina-script')
@endsection
