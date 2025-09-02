@extends('adminlte::page')

@section('title', 'Ver Reporte')

@section('content_header')
    <h1>Reporte: <span class="report-name">{{ $bitacora->nombre_archivo }}</span></h1>
@endsection

@section('content')
    {{-- Enlazamos la nueva hoja de estilos para esta vista --}}
    <link rel="stylesheet" href="{{ asset('css/bitacoras/verReportesV3.css') }}">

    {{-- Inputs ocultos para que los use JavaScript --}}
    <input type="hidden" id="url_devolucion" value="{{ route('bitacoras.devolver', ['ids' => ':id', 'bitacora' => $bitacora->id]) }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="id_bitacora" value="{{ route('bitacoras.consulta_reporte', ['id_bitacora' => $bitacora->id]) }}">
    <input type="hidden" id="url_indicadores" value="{{ route('bitacoras.Consulta_indicadores', ['id_bitacora' => $bitacora->id]) }}">

    {{-- Contenedor principal con nuestro estilo de tarjeta moderna --}}
    <div class="shadow-container">

        {{-- 1. Barra de herramientas con los botones de acción --}}
        <div class="report-toolbar">
            <a class="btn btn-back" href="javascript:history.go(-1)">
                <i class="fas fa-arrow-left"></i> Ir Atrás
            </a>
            <button class="btn btn-gradient btn-gradient-warning" id="devolucion">
                <i class="fas fa-undo-alt"></i> Pasar a Devolución
            </button>
        </div>

        {{-- 2. Área de contenido del reporte --}}
        <div class="report-content">
            {{-- Aquí se cargarán los indicadores como tarjetas --}}
            <div id="indicadores" class="indicators-grid"></div>

            {{-- Aquí se cargará la tabla principal --}}
            <div id="tabla"></div>
        </div>

    </div>
    <br>

@endsection

@section('js')
    <script>
        const causales = {!! json_encode($causales_dv) !!};
    </script>
    {{-- Asegúrate que tu JS se cargue después de la definición de 'causales' --}}
    <script src="{{ asset('js/bitacora/verReportesV4.1.js') }}"></script>
@stop
