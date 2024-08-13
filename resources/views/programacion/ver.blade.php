@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Ver Programacion</h1>
@stop


@section('content')
<style>
    .card {
        border-radius: 10px;
        /* Bordes redondeados para la tarjeta */
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        /* Sombra sutil para destacar la tarjeta */
    }

    .card-body {
        padding: 20px;
    }

    .form-group {
        margin-bottom: 15px;
    }

    .btn-success {
        background-color: #28a745;
        /* Verde Bootstrap */
        border-color: #28a745;
    }

    .btn-success:hover {
        background-color: #218838;
        border-color: #1e7e34;
    }
</style>
<input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
<input type="hidden" name="url_busqueda" id="url_busqueda" value="{{ route('programacion.agendamiento') }}">
<div class="container mt-6">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-center mb-3">Seleccionar Fecha de agendamiento</h4>
        
                        <br>
                        <div class="form-group">
                            <input type="date" class="form-control" id="fecha" name="fecha" required>
                        </div>

                        <div class="text-center">
                            <button type="submit" id="btnBuscar" class="btn btn-success">Buscar</button>
                        </div>

              
                </div>
            </div>
        </div>
         <div class="col-md-12 mt-6"> 
            <div class="card"> 
                <div class="card-body">
                    <h4 class="card-title text-center mb-3">Resultados de la Búsqueda</h4>
                    <br>
                    <div id="buscador" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/verProgramacion.js') }}"></script>
@stop
@endsection