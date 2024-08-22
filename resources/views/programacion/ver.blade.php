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
<input type="hidden" name="url_exportar" id="url_exportar" value="{{ route('programacion.exportar') }}">
<div class="container mt-6">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-center mb-3">Fecha de agendamiento</h4>
                    <br>

                    <div class="form-group">
                        <input type="date" class="form-control" id="fechaInicio" name="fechaInicio" required>
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="rangoFechas" name="rangoFechas">
                        <label class="form-check-label" for="rangoFechas">
                            Seleccionar un rango de fechas
                        </label>
                    </div>

                    <div class="form-group" id="fechaFinContainer" style="display: none;">
                        <input type="date" class="form-control" id="fechaFin" name="fechaFin">
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
                    <button id="btnExportar" class="btn btn-success float-right">Exportar a plantilla GDW</button>
                    <br>
                    <div id="buscador" class="mt-4"></div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/verProgramacion.js') }}"></script>
<script>
    
    const rangoFechasCheckbox = document.getElementById('rangoFechas');
    const fechaFinContainer = document.getElementById('fechaFinContainer');

    rangoFechasCheckbox.addEventListener('change', () => {
        if (rangoFechasCheckbox.checked) {
            fechaFinContainer.style.display = 'block';
        } else {
            fechaFinContainer.style.display = 'none';
        }
    });
</script>
@stop
@endsection