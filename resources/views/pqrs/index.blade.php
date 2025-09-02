@extends('adminlte::page')

@section('title', 'Quejas')

@section('content_header')
    <h1>Quejas</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/pqrs/index.css')}}">

    {{-- Contenedor #1: Formulario de Carga (centrado) --}}
    @can('cargar_PQRS')
        <div class="shadow-container" style="max-width: 600px; margin: 2rem auto;">
            <div class="upload-card">
                {{-- ... tu formulario de carga aquí ... --}}
                <h4 class="card-title text-center mb-3">Cargar Macro PQR</h4>
                <form id="formulario-archivo" enctype="multipart/form-data">
                    @csrf
                    <div class="form-group mb-3">
                        <input type="file" class="form-control" id="archivo" name="macroPQR">
                    </div>
                    <div class="text-center">
                        <button type="submit" id="btnSubir" class="btn-gradient-success">Subir Archivo</button>
                    </div>
                </form>
                <div id="mensaje-programaciones" class="mt-3"></div>
            </div>
        </div>
    @endcan

    {{-- ▼▼ ESTE ES EL NUEVO CONTENEDOR PARA CENTRAR LA TABLA ▼▼ --}}
    <div class="d-flex justify-content-center">
        {{-- Contenedor #2: Tabla de Tiempos de Gestión --}}
        <div class="shadow-container" style="width: 100%; max-width: 1400px;"> {{-- Le damos un ancho máximo --}}
            <div class="table-section">
                <h2 class="table-section-header">Tiempos de Gestión</h2>
                <div class="table-container">
                    <div id="table"></div>
                </div>
            </div>
        </div>
    </div>


    <script src="{{ asset('js/PQRS/indexV1.3.js') }}"></script>
@endsection
