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

  @keyframes spin {
    0% {
      transform: rotate(0deg);
    }

    100% {
      transform: rotate(360deg);
    }
  }

  #loader {
    border: 16px solid rgba(0, 0, 0, 0.1);
    /* Borde transparente */
    border-top: 16px solid #3498db;
    /* Azul */
    border-radius: 50%;
    width: 120px;
    height: 120px;
    animation: spin 2s linear infinite;
    /* Animación de rotación */
    position: fixed;
    /* Fijar posición */
    top: 50%;
    /* Posición vertical en el centro */
    left: 50%;
    /* Posición horizontal en el centro */
    margin-top: -60px;
    /* Centrar verticalmente */
    margin-left: -60px;
    /* Centrar horizontalmente */
    z-index: 9999;
    /* Asegurar que esté sobre otros elementos */
  }

  /* Estilo adicional para oscurecer el fondo */
  #overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
    /* Fondo semi-transparente */
    z-index: 9998;
    /* Debajo del loader */
  }
</style>
<div id="loader" style="display: none;"></div>
<div id="overlay" style="display: none;"></div>
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
          <button id="btnPlantilla" class="btn btn-primary btn-sm">Añadir en Plantilla</button>
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

<!-- Modal base -->
<div class="modal fade" id="addPlantilla" tabindex="-1" aria-labelledby="addPlantillaModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addPlantillaModalLabel">Programacion en plantilla</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div id="tabla_plantilla"></div>
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
  const tecnicos = @json($tecnicos -> toArray());
  const nombresTecnicos = tecnicos.map(tecnico => tecnico.id + '. ' + tecnico.apellidos + ' ' + tecnico.nombres);
  const user = @json($user -> toArray());
  const tabla_id = "{{ $programacion->id }}"
  const ver_programacion = "{{ $user->can('ver_programacion') ? 'true' : 'false' }}";

  const tabla_data = @json($tabla);
</script>
<script src="{{ asset('js/programacionV3.js') }}" type="text/javascript"></script>
@stop
@stop