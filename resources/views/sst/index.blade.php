@extends('adminlte::page')

@section('title', 'Preoperacional')

@section('content_header')
    <h1>Preoperacional</h1>
@endsection

@section('content')
    <script src="{{ asset('js/sst/sst.js') }}"></script>
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_exportar" value="{{ route('sst.exportar') }}">

    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Seleccione rango de fechas</h3>
            </div>
            <div class="card-body text-left">
                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" class="form-control">

                <label for="fecha_fin" class="form-label mt-2">Fecha Fin</label>
                <input type="date" id="fecha_fin" class="form-control">

                <br>

                <button class="btn btn-primary" id="exportar">exportar a GDW</button>

                <br>
                <div id="mensaje_servidor" class="alert alert-danger mt-3" style="display: none;">
                    <!-- Los mensajes de error se inyectarán aquí -->
                </div>


            </div>
        </div>
    </div>
@endsection
