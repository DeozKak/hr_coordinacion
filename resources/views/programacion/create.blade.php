@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>{{$programacion->nombre}} {{$user->name}}</h1>
@stop

@section('content')
<style>
    .htCenter {
  text-align: center;
}
</style>
<input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
<input type="hidden" name="url_index" id="url_index" value="{{ route('programacion.index') }}">
<input type="hidden" name="url_store" id="url_store" value="{{ route('programacion.store') }}">
<input type="hidden" name="busqueda" id="busqueda" value="{{ route('programacion.busqueda',['contrato' => ':id']) }}">
<input type="hidden" name="url_destroy" id="url_destroy" value="{{ route('programacion.destroy') }}">
<input type="hidden" name="url_update" id="url_update" value="{{ route('programacion.update',['id' => ':id']) }}">
<input type="hidden" name="url_finish" id="url_finish" value="{{ route('programacion.finish',['id' => ':id']) }}">
<div>
    <div class="row">
        <div class="col-xl-12">
            <div class="card">
                <div class="card-header">Tabla</div>
                <div class="card-body">
                    <a href="{{ route('programacion.index') }}" title="Regresar"><button class="btn btn-warning btn-sm"><i class="fa fa-arrow-left" aria-hidden="true"></i> Regresar</button></a>       
                    <br>
                    <br>
                    <div id="tabla_programacion"></div>
                    <br>
                    @if($programacion->finished == 0)
                    <button id="btnGuardar" class="btn btn-success btn-sm">Guardar</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@php
    $tabla = isset($tabla) ? $tabla : []; // Si $tabla no está definida, se asigna un array vacío
@endphp
@section('js')

<script>
         const view = "{{ $view ?? false }}";
</script>

<script>   
    const tecnicos = @json($tecnicos->toArray());
    const nombresTecnicos = tecnicos.map(tecnico => tecnico.id+'. ' +tecnico.apellidos+' '+tecnico.nombres);
    const user =  @json($user->toArray());
    const tabla_id = "{{ $programacion->id }}"
   
    const tabla_data = @json($tabla);

</script>
<script src="{{ asset('js/programacion.js') }}" type="text/javascript"></script>
@stop
@stop