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
  .select2-container {
    width: 100% !important;
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
            <input type="date" class="form-control" name="fecha" id="fecha" placeholder="dd-mm-yy" readonly>
          </div>

          <br>

          <div class="col-md-6">
            <label for="celular">Celular</label>
            <input type="text" class="form-control" name="celular" id="celular">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="causal">Nombre Usuario</label>
            <input type="text" class="form-control" name="nombre_usuario" id="nombre_usuario">
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
          <div class="col-md-6">
            <label for="direccion">Barrio</label>
            <input type="text" class="form-control" id="direccion" style="text-align: center;">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="municipio">Municipio:</label>
            <select class="form-control select2 w-100" name="municipio" id="municipio-select"></select>
          </div>
          <div class="col-md-6">
            <label>Estado:</label>
            <div class="estado-options">
              <input type="radio" id="activo" name="estado" value="activo" style="margin-left: 30px;" checked>
              <label for="activo" style="margin-right: 20px;">Activo</label>
              <input type="radio" id="suspendido" name="estado" value="suspendido">
              <label for="suspendido">Suspendido</label>
            </div>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="categoria">Categoria</label>
            <select class="form-control" name="categoria" id="categoria">
              <option value="">Seleccione categoria</option>
              <option value="RESIDENCIAL">RESIDENCIAL</option>
              <option value="COMERCIAL">COMERCIAL</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="Fecha Agendamiento">Fecha Agendamiento</label>
            <input type="date" class="form-control" name="fecha" id="fecha_agendamiento"  placeholder="dd-mm-yy">
          </div>
        </div>

        <div class="row" style="margin-top: 30px;">
          <div class="col-md-6">
            <label for="Observaciones">Observaciones</label>
            <input type="text" class="form-control" id="observaciones" size="50" maxlength="200" style="text-align: center;">
          </div>
          <div class="col-md-6">
            <label for="nombre">Por que se programó</label>
            <input type="text" class="form-control" id="usuario_programado" size="50" maxlength="200" style="text-align: center;" readonly>

          </div>
        </div>
        <div class="row" style="margin-top: 25px;">
          <div class="col-md-6">
            <label for="nombre">Inspector:</label>
            <select class="form-control" name="nombre" id="tecnico">
              <option value="">Seleccione Inspector</option>
              @foreach ($tecnicos as $inspector)
              <option value="{{$inspector->id}}. {{$inspector->apellidos}} {{$inspector->nombres}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
              @endforeach
            </select>
          </div>
          <div class="col-md-6">
            <div class="row">
              <div class="col-md-6">
                <label for="Hora inicio">Hora inicio</label>
                <select class="form-control" name="hora_inicio" id="hora_inicio">
                  <option value="">Seleccione Hora inicio</option>
                  <option value="07:59:00 a.m.">07:59:00 a.m.</option>
                  <option value="01:59:00 p.m.">01:59:00 p.m.</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="Hora fin">Hora final</label>
                <select class="form-control" name="hora_fin" id="hora_fin">
                  <option value="">Seleccione Hora final</option>
                  <option value="11:59:00 a.m.">11:59:00 a.m.</option>
                  <option value="04:59:00 p.m.">04:59:00 p.m.</option>
                </select>
              </div>
            </div>
          </div>
        </div>
      </div>
    
    <div class="modal-footer">
      <button class="btn btn-success" id="agregar">Agregar</button>
      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
    </div>
  </div>
  </div>
</div>
</div>

@php
$tabla = isset($tabla) ? $tabla : []; // Si $tabla no está definida, se asigna un array vacío
@endphp
@section('js')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
<script>
  const view = "{{ $view ?? false }}";
</script>

<script>
  const urlMunicipios = "{{ route('municipios.json') }}";
  const tecnicos = @json($tecnicos -> toArray());
  const nombresTecnicos = tecnicos.map(tecnico => tecnico.id + '. ' + tecnico.apellidos + ' ' + tecnico.nombres);
  const user = @json($user -> toArray());
  const tabla_id = "{{ $programacion->id }}"
  const ver_programacion = "{{ $user->can('ver_programacion') ? 'true' : 'false' }}";

  const tabla_data = @json($tabla);
  $('#municipio-select').select2({
    language: "es",
    ajax: {
      url: urlMunicipios, // Ruta a la función del controlador
      dataType: 'json',
      delay: 250, // Retraso antes de realizar la búsqueda
      data: function(params) {
        return {
          term: params.term // Término de búsqueda
        };
      },
      processResults: function(data) {
        return {
          results: $.map(data, function(item, key) { // Mapear resultados
            return {
              id: key,
              text: item
            };
          })
        };
      },
      cache: true
    },
    minimumInputLength: 2 // Mínimo de caracteres para iniciar la búsqueda
  });
</script>
<script src="{{ asset('js/programacionV3.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/formPlantilla.js') }}" type="text/javascript"></script>
@stop
@stop