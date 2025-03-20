@extends('adminlte::page')

@section('title', 'Información General')

@section('content_header')
<h1>Información General</h1>
@stop

@section('content')
<input type="hidden" id="token" value="{{csrf_token()}}">
<script src="{{asset('js/informacion_generalV3-1.js')}}?v={{ time() }}"></script>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cortes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearCorte">Crear Corte</a>
                <table class="table table-striped" id="cortes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Meta</th>
                            <th>Dobles</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cortes as $corte)
                        <tr data-id="{{$corte->id}}">
                            <td>{{ $corte->nombre }}</td>
                            <td>{{ $corte->fecha_inicio }}</td>
                            <td>{{ $corte->fecha_fin }}</td>
                            <td>{{ $corte->meta }}</td>
                            <td>{{ $corte->dobles }}</td>
                            <td>
                                <div class="d-flex justify-content-center gap-2">
                                    <button class="btn btn-info btn-sm abrirCorteModal" data-corte-id="{{ $corte->id }}">Editar</button>&nbsp;
                                    <button class="btn btn-primary btn-sm btndetallesCorte" data-corte-id="{{ $corte->id }}">Detalles</button>&nbsp;
                                    <button class="btn btn-secondary btn-sm" data-corte-id="{{ $corte->id }}">Graficos</button>&nbsp;
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>


    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sedes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearSede">Crear Sede</a>
                <table class="table table-striped" id="sedes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sedes as $sede)
                        <tr data-id="{{$sede->id}}">
                            <td>{{ $sede->nombre }}</td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <button class="btn btn-info btn-sm abrirSedeModal" data-sede-id="{{ $sede->id }}">Editar</button>
                                    @if ($sede->status == 1)
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusSede" data-sede-id="{{ $sede->id }}">Desactivar</button>
                                    @else
                                        <button class="btn btn-success btn-sm" id="btnChangeStatusSede" data-sede-id="{{ $sede->id }}">Activar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <input type="hidden" id="cambiarEstadoSede" value="{{route('cortes_produccion.changeStatusSede')}}">
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Zonas</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearZona">Crear Zona</a>
                <table class="table table-striped" id="zonas">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zonas as $zona)
                        <tr data-id="{{$zona->id}}">
                            <td>{{ $zona->nombre }}</td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <button class="btn btn-info btn-sm abrirZonaModal" data-zona-id="{{ $zona->id }}">Editar</button>
                                    @if ($zona->status == 1)
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusZona" data-zona-id="{{ $zona->id }}">Desactivar</button>
                                    @else
                                        <button class="btn btn-success btn-sm" id="btnChangeStatusZona" data-zona-id="{{ $zona->id }}">Activar</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <input type="hidden" id="cambiarEstadoZona" value="{{route('cortes_produccion.changeStatusZona')}}">
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Causales Devolución</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearCausal">Crear Causal</a>
                <table class="table table-striped" id="causal">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($causales as $index => $causal)
                        <tr data-id="{{$causal->id}}">
                            <td>{{ $causal->nom_causal }}</td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    @if ($index > 0) {{-- Only show the button if the index is greater than 0 --}}
                                        <button class="btn btn-info btn-sm abrirCausalModal" data-causal-id="{{ $causal->id }}">Editar</button>
                                        @if ($causal->status == 1)
                                            <button class="btn btn-danger btn-sm" id="btnChangeStatusCausal" data-causal-id="{{ $causal->id }}">Desactivar</button>
                                        @else
                                            <button class="btn btn-success btn-sm" id="btnChangeStatusCausal" data-causal-id="{{ $causal->id }}">Activar</button>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <input type="hidden" id="cambiarEstadoCausal" value="{{route('cortes_produccion.changeStatusCausal')}}">
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear Corte --}}
<div class="modal fade" id="corteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="crearCorteModalLabel">Crear Corte</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombreCorte" name="nombre">
            <input type="hidden" id="idGuardarCorte">
          </div>
          <div class="form-group">
            <label for="fecha_inicio">Fecha Inicio</label>
            <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
          </div>
          <div class="form-group">
            <label for="fecha_fin">Fecha Fin</label>
            <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
          </div>
          <div class="form-group">
            <label for="meta">Meta</label>
            <input type="text" class="form-control inputNumericoMeta" id="meta" name="meta">
          </div>
          <div class="form-group">
            <label for="Dobles">Dobles</label>
            <input type="text" class="form-control inputNumericoDobles" id="dobles" name="dobles">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" id="crearCorte" class="btn btn-primary">Crear Corte</button>
        </div>
      </div>
    </div>
</div>


{{-- Modal para crear Sede --}}
<div class="modal fade" id="sedeModal" tabindex="-1" role="dialog" aria-labelledby="crearSedeModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="crearSedeModalLabel">Ingresar Sede</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="nombreSede">Nombre</label>
            <input type="text" class="form-control" id="nombreSede">
            <input type="hidden" id="idGuardarSede">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" id="crearSede" class="btn btn-primary">Crear Sede</button>
        </div>
      </div>
    </div>
  </div>

{{-- Modal para crear Zona --}}
<div class="modal fade" id="zonaModal" tabindex="-1" role="dialog" aria-labelledby="crearZonaModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="crearZonaModalLabel">Ingresar Zona</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombreZona" name="nombre">
            <input type="hidden" id="idGuardarZona">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" id="crearZona" class="btn btn-primary">Crear Zona</button>
        </div>
      </div>
    </div>
  </div>

{{-- Modal para crear Causal --}}
<div class="modal fade" id="causalModal" tabindex="-1" role="dialog" aria-labelledby="crearCausalModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="crearCausalModalLabel">Ingresar Causal</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div class="form-group">
            <label for="nombre">Nombre</label>
            <input type="text" class="form-control" id="nombreCausal" name="nombre">
            <input type="hidden" id="idGuardarCausal">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
          <button type="submit" id="crearCausal" class="btn btn-primary">Guardar Causal</button>
        </div>
      </div>
    </div>
  </div>
<script>
    const rangosFechasExistentes = @json($cortes);
</script>
@stop
