@extends('layouts.tw.app')

@section('title', 'Detalles de producción')

@section('content_header')
    <h1>Detalles de producción</h1>
@endsection

@section('subtitle', $corte?->nombre ? 'Corte: '.$corte->nombre : 'Sin corte activo')

@section('actions')
    <button type="button" class="tw-btn-secondary" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Ir atrás
    </button>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="detallesProduccion({
            permiso: {{ auth()->user()?->can('ver_residente') ? 1 : 0 }},
            fechaInicioCorte: '{{ session('fecha_inicio') }}',
            urls: {
                datos:            '{{ route('produccion.datosDetalles') }}',
                obtenerDetalles:  '{{ route('obtener-url-detalles') }}',
                obtenerBitacoras: '{{ route('obtener-url-bitacoras') }}',
                actualizarFila:   '{{ route('produccion.ActualizarDetallesDiario', ['id' => ':id']) }}',
                disenoEspecial:   '{{ route('produccion.diseñoEspecial', ['id' => ':id']) }}',
                alternarEstado:   '{{ route('produccion.eliminarDetallesDiario', ['id' => ':id']) }}',
                detallesDia:      '{{ route('produccion.detallesDiario', ['fecha' => ':fecha', 'inspector' => ':inspector']) }}',
                insertar:         '{{ route('produccion.insertarContrato') }}',
                municipios:       '{{ route('municipios.json') }}',
                contarDobles:          '{{ route('produccion.contarDobles') }}',
                noContarDobles:        '{{ route('produccion.guardarNoDobles') }}',
                noContarDoblesFestivo: '{{ route('produccion.storeNotDoublesHolidays') }}',
                contarDoblesFestivo:   '{{ route('produccion.countDoublesHolidays') }}',
                noContarDoblesSabado:  '{{ route('produccion.noContarDoblesSaturday') }}',
            },
         })"
         class="space-y-6">

        {{-- ============================== LEYENDA ============================= --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-industry"></i></span>
                    <div>
                        <h2 class="tw-card-title">Producción por inspector</h2>
                        <p class="tw-card-subtitle">
                            Doble clic en la esquina de una celda de día para ver sus inspecciones
                        </p>
                    </div>
                </div>

                <button type="button" class="tw-btn-secondary" @click="exportar()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>

            {{-- Los mismos colores del diseño anterior, ahora documentados. --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 px-5 py-3
                        dark:border-slate-700/60">
                <span class="tw-eyebrow">Código de color</span>
                @foreach ([
                    ['dia',     'Día del corte'],
                    ['resumen', 'Columnas de resumen'],
                    ['total',   'Totales'],
                    ['bueno',   'Festivo / promedio ≥ 8'],
                    ['malo',    'Bajo lo esperado'],
                    ['sabado',  'Sábado doble'],
                ] as [$clave, $texto])
                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10 leyenda-{{ $clave }}"></span>
                        {{ $texto }}
                    </span>
                @endforeach
            </div>

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="detalles" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        @include('produccion.partials.detalles-modales')

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

@push('styles')
    <style>
        /* Muestras de la leyenda: mismos tonos que pinta el renderizador. */
        .leyenda-dia     { background: rgb(215, 232, 255); }
        .leyenda-resumen { background: rgb(253, 234, 185); }
        .leyenda-total   { background: rgb(185, 196, 255); }
        .leyenda-bueno   { background: rgb(147, 255, 134); }
        .leyenda-malo    { background: rgb(255, 185, 185); }
        .leyenda-sabado  { background: rgb(255, 240, 142); }
        .dark .leyenda-dia     { background: #1e3a5f; }
        .dark .leyenda-resumen { background: #4a3a1a; }
        .dark .leyenda-total   { background: #2a3170; }
        .dark .leyenda-bueno   { background: #1e4620; }
        .dark .leyenda-malo    { background: #5c2020; }
        .dark .leyenda-sabado  { background: #57431a; }
    </style>
@endpush

@section('js')
    @include('produccion.partials.detalles-script')
@endsection
