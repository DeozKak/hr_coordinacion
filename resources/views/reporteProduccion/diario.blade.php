@extends('layouts.tw.app')

@section('title', 'Reporte de producción diario')

@section('content_header')
    <h1>Reporte de producción diario</h1>
@endsection

@section('subtitle', 'Producción y facturación por día, con proyección editable.')

@section('actions')
    <a href="{{ route('fechasProduccion.registrar') }}" class="tw-btn-secondary"
       title="Parametrizar los precios usados en este reporte">
        <i class="fas fa-sliders"></i> Parametrizar precios
    </a>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="reporteDiario({
            urls: {
                guardarNomina:     '{{ route('produccion.guardar') }}',
                guardarInspeccion: '{{ route('produccion.guardarInspeccionIndustrial') }}',
            },
            meses: {{ Js::from([
                'Enero' => route('produccion.enero'),      'Febrero' => route('produccion.febrero'),
                'Marzo' => route('produccion.marzo'),      'Abril' => route('produccion.abril'),
                'Mayo' => route('produccion.mayo'),        'Junio' => route('produccion.junio'),
                'Julio' => route('produccion.julio'),      'Agosto' => route('produccion.agosto'),
                'Septiembre' => route('produccion.septiembre'), 'Octubre' => route('produccion.octubre'),
                'Noviembre' => route('produccion.noviembre'),   'Diciembre' => route('produccion.diciembre'),
            ]) }},
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== FILTROS ============================= --}}
        <section class="tw-card p-4 2xl:p-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[200px_260px_1fr] lg:items-end">
                <div>
                    <label class="tw-label" for="nominaSelectorAnio">Año</label>
                    <select class="tw-select" id="nominaSelectorAnio" x-model="anio">
                        <option value="">Seleccione un año</option>
                        @for ($ano = $currentYear; $ano >= 2022; $ano--)
                            <option value="{{ $ano }}">{{ $ano }}</option>
                        @endfor
                    </select>
                </div>

                {{-- El mes solo aparece cuando ya hay año, igual que antes. --}}
                <div x-show="anio" x-cloak>
                    <label class="tw-label" for="nomina-selector">Mes</label>
                    <select class="tw-select" id="nomina-selector" x-model="mes" @change="cargar()">
                        <option value="">Seleccione un mes</option>
                        <template x-for="(url, nombre) in meses" :key="nombre">
                            <option :value="url" x-text="nombre"></option>
                        </template>
                    </select>
                </div>

                <div class="flex items-end gap-2 lg:justify-end">
                    <button type="button" class="tw-btn-primary" @click="exportar()" :disabled="cargando">
                        <i class="fas" :class="cargando ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                        Exportar a Excel
                    </button>
                </div>
            </div>

            <p class="tw-hint mt-3" x-show="!hayDatos" x-cloak>
                <i class="fas fa-circle-info"></i>
                Selecciona un año y un mes para ver el reporte.
            </p>
        </section>

        {{-- ============================== LEYENDA ============================= --}}
        <section class="tw-card" x-show="hayDatos" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-file-invoice-dollar"></i></span>
                    <div>
                        <h2 class="tw-card-title">Reporte de producción diario</h2>
                        <p class="tw-card-subtitle">
                            La columna <strong>Cantidad proyectada</strong> es editable; el resto se calcula.
                        </p>
                    </div>
                </div>
            </div>

            <x-color-legend :items="[
                ['residencial', 'Residencial'], ['comercial', 'Comercial'],
                ['valle', 'Facturación / inspectores'], ['festivo', 'Festivo'],
                ['sabado', 'Sábado'], ['totales', 'Totales'],
            ]" />

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="example" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        {{-- ============================== RESUMEN ============================= --}}
        <section class="tw-card" x-show="hayDatos" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-emerald"><i class="fas fa-list-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Resumen</h2>
                        <p class="tw-card-subtitle">
                            Precios por zona y totales; la cantidad de inspección industrial es editable.
                        </p>
                    </div>
                </div>
            </div>

            <x-color-legend :items="[
                ['residencial', 'Residencial'], ['comercial', 'Comercial'],
                ['industrial', 'Inspección industrial'],
            ]" />

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaResumen" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

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
    @include('reporteProduccion.partials.diario-script')
@endsection
