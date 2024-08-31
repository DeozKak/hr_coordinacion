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
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="addPlantillaModalLabel">Programacion en plantilla</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6">
        
            <label for="contrato">Contrato</label>
            <input type="text" class="form-control" name="contrato" id="contrato">
            
          </div>
          <br>
          <div class="col-md-6">
          <label for="tipo_trabajo">Tipo de Trabajo</label>
            <select class="form-control" name="tipo_trabajo" id="tipo_trabajo">
              <option value="">Seleccione Tipo de Trabajo</option>
              <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
              <option value="RP 10444">RP 10444</option>
              <option value="RP 12161">RP 12161</option>
              <option value="RN 12162">RN 12162</option>
              <option value="SA 12163">SA 12163</option>
              <option value="SA 12164">SA 12164</option>
            </select>
           
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="fecha">Fecha:</label>
            <input type="date" class="form-control" name="fecha" id="fecha" placeholder="dd-mm-yy">
          </div>

          <br>

          <div class="col-md-6">
            <label for="celular">Celular</label>
            <input type="text" class="form-control" name="celular" id="N°acta">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="causal">Nombre Usuario</label>
            <input type="text" class="form-control" name="nombre_usuario" id="causal">
          </div>

          <br>
          <div class="col-md-6">
          <label for="orden_trabajo">Orden de Trabajo</label>
            <input type="text" class="form-control" id="orden_trabajo" style="text-align: center;">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
          <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="direccion" style="text-align: center;">

          </div>
          <br>
          <div class="col-md-6 matriz-des1">
            <label for="categoria">Categoria</label>
            <select class="form-control" name="categoria" id="categoria">
              <option value="">Seleccione categoria</option>
              <option value="RESIDENCIAL">RESIDENCIAL</option>
              <option value="COMERCIAL">COMERCIAL</option>
            </select>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="recintos">4 Recintos o mas</label>
            <select class="form-control" name="recintos" id="recintos">
              <option value="NO" selected>NO</option>
              <option value="SI">SI</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="cantidad_recintos">Cantidad de recintos</label>
            <input type="text" class="form-control" id="NroRecintosP" style="text-align: center;" disabled>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
          <label for="municipio">Municipio:</label>
          <select class="form-control select2" name="municipio" id="municipio-select"></select>
          <label for="nombre">Inspector:</label>
            <select class="form-control" name="nombre" id="nombre">
              <option value="">Seleccione Inspector</option>
              @foreach ($tecnicos as $inspector)
              <option value="{{$inspector->cedula}}" data-nombres="{{$inspector->apellidos}} {{$inspector->nombres}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
              @endforeach
            </select>
            
          </div>
        </div>

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