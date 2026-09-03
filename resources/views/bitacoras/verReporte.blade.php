@extends('layouts.tw.app')

@section('title', 'Ver Reporte')
@section('content_header')
    <h1>Reporte</h1>
@endsection
@section('subtitle', $bitacora->nombre_archivo)

@include('layouts.tw.partials.handsontable')

@section('actions')
    <a href="javascript:history.go(-1)" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Ir atrás
    </a>
    {{-- verReportesV4.1.js engancha por id: no renombrar. --}}
    <button type="button" id="devolucion" class="tw-btn-primary">
        <i class="fas fa-rotate-left"></i> Pasar a Devolución
    </button>
@endsection

@section('content')
    {{-- Hooks que lee verReportesV4.1.js --}}
    <input type="hidden" id="url_devolucion" value="{{ route('bitacoras.devolver', ['ids' => ':id', 'bitacora' => $bitacora->id]) }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="id_bitacora" value="{{ route('bitacoras.consulta_reporte', ['id_bitacora' => $bitacora->id]) }}">
    <input type="hidden" id="url_indicadores" value="{{ route('bitacoras.Consulta_indicadores', ['id_bitacora' => $bitacora->id]) }}">

    <div class="space-y-4 2xl:space-y-6">
        {{-- Indicadores: el JS inyecta aquí las tarjetas --}}
        <div id="indicadores" class="grid gap-4 2xl:gap-5 sm:grid-cols-2 lg:grid-cols-5">
            {{-- Esqueleto mientras carga --}}
                @for ($i = 0; $i < 5; $i++)
                <div class="tw-card animate-pulse p-4 2xl:p-5">
                    <div class="h-3 w-24 rounded bg-slate-200 dark:bg-slate-700"></div>
                    <div class="mt-4 h-8 w-16 rounded bg-slate-200 dark:bg-slate-700"></div>
                </div>
            @endfor
        </div>

        <section class="tw-card overflow-hidden">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-table"></i></span>
                    <div>
                        <h2 class="tw-card-title">Detalle del reporte</h2>
                        <p class="tw-card-subtitle">Selecciona filas para pasarlas a devolución</p>
                    </div>
                </div>
            </div>
            <div class="p-4 2xl:p-5 pt-0">
                {{-- Siempre ht-theme-main: el modo oscuro lo resuelve
                     `color-scheme` desde app.css, sin reinicializar la grilla. --}}
                <div id="tabla" class="ht-theme-main"></div>
            </div>
        </section>cl
    </div>
@endsection

@section('js')
    <script>
        const causales = @json($causales_dv);
    </script>
    {{-- filemtime y no time(): el navegador puede cachear hasta que el archivo
         cambie de verdad. Sin él, un cambio en el JS no llegaba al cliente. --}}
    <script src="{{ asset('js/bitacora/verReportesV4.1.js') }}?v={{ filemtime(public_path('js/bitacora/verReportesV4.1.js')) }}"></script>
@endsection
