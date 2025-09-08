@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>{{$programacion->nombre}} {{$user->name}}</h1>
@stop

@section('content')
<link rel="stylesheet" href="{{ asset('css/programacion/createV1.1.css') }}">
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
<div class="shadow-container">
    <div class="programacion-toolbar">
        <a class="btn-back" href="{{ route('programacion.index') }}" title="Regresar">
            <i class="fa fa-arrow-left"></i> Regresar
        </a>
        <div class="actions-group">
            @if($programacion->finished == 0)
                <button id="btnPlantilla" class="btn-gradient btn-gradient-primary">Añadir en Plantilla</button>
                <button id="btnGuardar" class="btn-gradient btn-gradient-success">Guardar</button>
            @endif
        </div>
    </div>

    <div class="table-container">
        <div id="tabla_programacion"></div>
    </div>
</div>

<!-- Modal base -->
<div class="modal fade modal-modern" id="addPlantilla" tabindex="-1" aria-labelledby="addPlantillaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addPlantillaModalLabel">
                    <i class="fas fa-file-alt text-primary"></i> Programación en Plantilla
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">

                <div class="form-field-group">
                    <label for="CONTRATO"><i class="fas fa-file-signature"></i> Contrato</label>
                    <input type="text" class="form-control" name="contrato" id="CONTRATO">
                </div>
                <div class="form-field-group">
                    <label for="TIPO_TRABAJO"><i class="fas fa-tools"></i> Tipo de Trabajo</label>
                    <select class="form-control" name="tipo_trabajo" id="TIPO_TRABAJO">
                        <option value="">Seleccione Tipo de Trabajo</option>
                        <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
                        <option value="10444">RP 10444</option>
                        <option value="12161">RP 12161</option>
                        <option value="12162">RN 12162</option>
                        <option value="12163">SA 12163</option>
                        <option value="12164">SA 12164</option>
                    </select>
                </div>

                <div class="form-field-group">
                    <label for="FECHA"><i class="fas fa-calendar-alt"></i> Fecha:</label>
                    <input type="date" class="form-control" name="fecha" id="FECHA" readonly>
                </div>
                <div class="form-field-group">
                    <label for="CELULAR"><i class="fas fa-mobile-alt"></i> Celular</label>
                    <input type="text" class="form-control" name="celular" id="CELULAR">
                </div>

                <div class="form-field-group">
                    <label for="NOMBRE_USUARIO"><i class="fas fa-user"></i> Nombre Usuario</label>
                    <input type="text" class="form-control" name="nombre_usuario" id="NOMBRE_USUARIO">
                </div>
                <div class="form-field-group">
                    <label for="ORDEN_TRABAJO"><i class="fas fa-hashtag"></i> Orden de Trabajo</label>
                    <input type="text" class="form-control" id="ORDEN_TRABAJO" style="text-align: center;" disabled>
                </div>

                <div class="form-field-group">
                    <label for="DIRECCION"><i class="fas fa-map-marked-alt"></i> Dirección</label>
                    <input type="text" class="form-control" id="DIRECCION">
                </div>
                <div class="form-field-group">
                    <label for="BARRIO"><i class="fas fa-map-pin"></i> Barrio</label>
                    <input type="text" class="form-control" id="BARRIO">
                </div>

                <div class="form-field-group">
                    <label for="CIUDAD"><i class="fas fa-city"></i> Municipio:</label>
                    <select class="form-control select2 w-100" name="municipio" id="CIUDAD"></select>
                </div>
                <div class="form-field-group">
                    <label><i class="fas fa-toggle-on"></i> Estado:</label>
                    <div class="estado-options">
                        <input type="radio" id="activo" name="estado" value="activo" checked>
                        <label for="activo">Activo</label>
                        <input type="radio" id="suspendido" name="estado" value="suspendido">
                        <label for="suspendido">Suspendido</label>
                    </div>
                </div>

                <div class="form-field-group">
                    <label for="CATEGORIA"><i class="fas fa-tag"></i> Categoría</label>
                    <select class="form-control" name="categoria" id="CATEGORIA">
                        <option value="">Seleccione categoría</option>
                        <option value="RESIDENCIAL">RESIDENCIAL</option>
                        <option value="COMERCIAL">COMERCIAL</option>
                    </select>
                </div>
                <div class="form-field-group">
                    <label for="FECHA_AGENDAMIENTO"><i class="fas fa-calendar-check"></i> Fecha Agendamiento</label>
                    <input type="date" class="form-control" name="fecha_agendamiento" id="FECHA_AGENDAMIENTO">
                </div>

                <div class="form-field-group full-width">
                    <label for="OBSERVACIONES"><i class="fas fa-eye"></i> Observaciones</label>
                    <input type="text" class="form-control" id="OBSERVACIONES" maxlength="200">
                </div>

                <div class="form-field-group full-width">
                    <label for="PORQUE_PROGRAMO"><i class="fas fa-question-circle"></i> Por qué se programó</label>
                    <input type="text" class="form-control" id="PORQUE_PROGRAMO" maxlength="200" readonly>
                </div>

                <div class="form-field-group">
                    <label for="TECNICO"><i class="fas fa-user-cog"></i> Inspector:</label>
                    <select class="form-control" name="nombre" id="TECNICO">
                        <option value="">Seleccione Inspector</option>
                        @foreach ($tecnicos as $inspector)
                            <option value="{{$inspector->id}}. {{$inspector->apellidos}} {{$inspector->nombres}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-field-group">
                    <label><i class="fas fa-clock"></i> Horario:</label>
                    <div style="display: flex; gap: 1rem;">
                        <select class="form-control" name="hora_inicio" id="HORA_INICIO">
                            <option value="">Seleccione Hora inicio</option>
                            <option value="06:59:00 a.m.">06:59:00 a.m.</option>
                            <option value="11:59:00 a.m.">11:59:00 a.m.</option>
                        </select>
                        <select class="form-control" name="hora_fin" id="HORA_FINAL">
                            <option value="">Seleccione Hora final</option>
                            <option value="11:59:00 a.m.">11:59:00 a.m.</option>
                            <option value="04:59:00 p.m.">04:59:00 p.m.</option>
                        </select>
                    </div>
                </div>

            </div>
            <div class="modal-footer">
                <button type="button" id="btn_close" class="btn-secondary-modern">Cerrar</button>
                <button class="btn-gradient btn-gradient-success" id="agregar">Agregar</button>
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
  const ver_programacion = "{{ Auth::user()->can('ver_programacion') ? 'true' : 'false' }}";

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
<script src="{{ asset('js/programacion/programacionV4-7.js') }}" type="text/javascript"></script>
<script src="{{ asset('js/formPlantilla.js') }}" type="text/javascript"></script>
@stop
@stop
