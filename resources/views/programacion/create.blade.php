@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Crear</h1>
@stop

@section('content')
<input type="hidden" name="busqueda" id="busqueda" value="{{ route('programacion.busqueda',['contrato' => ':id']) }}">
<div class="container">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">Crear</div>
                <div class="card-body">
                    <a href="{{ route('programacion.index') }}" title="Regresar"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Regresar</button></a>
                    
                    <br>
                    <br>
                    <div id="tabla_programacion"></div>
                   

                </div>


            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/programacion.js') }}" type="text/javascript"></script>
@stop
@stop