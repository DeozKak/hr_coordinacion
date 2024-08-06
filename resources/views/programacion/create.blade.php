@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Crear</h1>
@stop

@section('content')
<style>
    .htCenter {
  text-align: center;
}
</style>
<input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
<input type="hidden" name="url_base" id="url_store" value="{{ route('programacion.store') }}">
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
                   
                    <a href="{{ route('programacion.index') }}" title="Regresar"><button class="btn btn-success btn-sm"> Guardar</button></a>

                </div>


            </div>
        </div>
    </div>
</div>

@section('js')
<script>
    const tecnicos = @json($tecnicos->toArray());
    const nombresTecnicos = tecnicos.map(tecnico => tecnico.apellidos+' '+tecnico.nombres);
    const user =  @json($user->toArray());
   
</script>
<script src="{{ asset('js/programacion.js') }}" type="text/javascript"></script>
@stop
@stop