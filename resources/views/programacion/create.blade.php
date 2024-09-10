@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>{{$programacion->nombre}} {{$user->name}}</h1>
@stop

@section('content')
<link rel="stylesheet" href="{{ asset('css/programacion/create.css') }}">
<div id="loader" style="display: none;"></div>
<div id="overlay" style="display: none;"></div>
<input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">

<input type="hidden" name="url_paltilla" id="url_plantilla" value="{{ route('programacion.PlantillaStore') }}">
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
          @if($programacion->finished == 0)
          <button id="btnPlantilla" class="btn btn-primary btn-sm">Añadir en Plantilla</button>
          @endif
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
            <input type="text" class="form-control" name="contrato" id="CONTRATO">

          </div>
          <br>
          <div class="col-md-6">
            <label for="tipo_trabajo">Tipo de Trabajo</label>
            <select class="form-control" name="tipo_trabajo" id="TIPO_TRABAJO">
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
            <input type="date" class="form-control" name="fecha" id="FECHA" placeholder="dd-mm-yy" readonly>
          </div>

          <br>

          <div class="col-md-6">
            <label for="celular">Celular</label>
            <input type="text" class="form-control" name="celular" id="CELULAR">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="causal">Nombre Usuario</label>
            <input type="text" class="form-control" name="nombre_usuario" id="NOMBRE_USUARIO">
          </div>

          <br>
          <div class="col-md-6">
            <label for="orden_trabajo">Orden de Trabajo</label>
            <input type="text" class="form-control" id="ORDEN_TRABAJO" style="text-align: center;" disabled>
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="direccion">Dirección</label>
            <input type="text" class="form-control" id="DIRECCION" style="text-align: center;">

          </div>
          <br>
          <div class="col-md-6">
            <label for="direccion">Barrio</label>
            <input type="text" class="form-control" id="BARRIO" style="text-align: center;">
          </div>
        </div>
        <br>
        <div class="row">
          <div class="col-md-6">
            <label for="municipio">Municipio:</label>
            <select class="form-control select2 w-100" name="municipio" id="CIUDAD"></select>
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
            <select class="form-control" name="categoria" id="CATEGORIA">
              <option value="">Seleccione categoria</option>
              <option value="RESIDENCIAL">RESIDENCIAL</option>
              <option value="COMERCIAL">COMERCIAL</option>
            </select>
          </div>
          <div class="col-md-6">
            <label for="Fecha Agendamiento">Fecha Agendamiento</label>
            <input type="date" class="form-control" name="fecha" id="FECHA_AGENDAMIENTO"  placeholder="dd-mm-yy">
          </div>
        </div>

        <div class="row" style="margin-top: 30px;">
          <div class="col-md-6">
            <label for="Observaciones">Observaciones</label>
            <input type="text" class="form-control" id="OBSERVACIONES" size="50" maxlength="200" style="text-align: center;">
          </div>
          <div class="col-md-6">
            <label for="nombre">Por que se programó</label>
            <input type="text" class="form-control" id="PORQUE_PROGRAMO" size="50" maxlength="200" style="text-align: center;" readonly>

          </div>
        </div>
        <div class="row" style="margin-top: 25px;">
          <div class="col-md-6">
            <label for="nombre">Inspector:</label>
            <select class="form-control" name="nombre" id="TECNICO">
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
                <select class="form-control" name="hora_inicio" id="HORA_INICIO">
                  <option value="">Seleccione Hora inicio</option>
                  <option value="07:59:00 a.m.">07:59:00 a.m.</option>
                  <option value="01:59:00 p.m.">01:59:00 p.m.</option>
                </select>
              </div>
              <div class="col-md-6">
                <label for="Hora fin">Hora final</label>
                <select class="form-control" name="hora_fin" id="HORA_FINAL">
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
  $('#CIUDAD').select2({
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
<script src="{{ asset('js/programacionV4-1.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/formPlantilla.js') }}" type="text/javascript"></script>
@stop
@stop