@extends('adminlte::page')

@section('title', 'Información General')

@section('content_header')
    <h1>Información General</h1>
@stop

@section('content')
    <link rel="stylesheet" href="{{ asset('css/informacionGeneral/indexV1.css') }}">
    <input type="hidden" id="token" value="{{csrf_token()}}">

    <div class="row">
        {{-- Tarjeta Cortes --}}
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-calendar-alt"></i> Cortes</h3>
                </div>
                <div class="card-body">
                    <button class="btn-gradient btn-gradient-success mb-3" id="btnCrearCorte">Crear Corte</button>
                    <div class="table-responsive">
                        <table class="table" id="cortes">
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
                                        <div class="btn-group">
                                            <button class="btn-gradient btn-gradient-info btn-sm abrirCorteModal" id="btn_editar" data-corte-id="{{ $corte->id }}">Editar</button>
                                            <button class="btn-gradient btn-gradient-primary btn-sm btndetallesCorte"  id="btn_detallesCorte" data-corte-id="{{ $corte->id }}">Detalles</button>
                                            <button class="btn-gradient btn-gradient-primary btn-sm" id="btn_fallidas" data-corte-id="{{ $corte->id }}">Fallidas</button>
                                            <button class="btn-gradient btn-secondary-modern btn-sm" id="btn_graficos" data-corte-id="{{ $corte->id }}">Gráficos</button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta Causales Devolución --}}
        <div class="col-md-6">
            <div class="card dashboard-card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-undo-alt"></i> Causales Devolución</h3>
                </div>
                <div class="card-body">
                    <button class="btn-gradient btn-gradient-success mb-3" id="btnCrearCausal">Crear Causal</button>
                    <div class="table-responsive">
                        <table class="table" id="causal">
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
                                        <div class="btn-group">
                                            @if ($index > 0)
                                                <button class="btn-gradient btn-gradient-info btn-sm abrirCausalModal" data-causal-id="{{ $causal->id }}">Editar</button>
                                                @if ($causal->status == 1)
                                                    <button class="btn-gradient btn-gradient-danger btn-sm" id="btnChangeStatusCausal" data-causal-id="{{ $causal->id }}">Desactivar</button>
                                                @else
                                                    <button class="btn-gradient btn-gradient-success btn-sm" id="btnChangeStatusCausal" data-causal-id="{{ $causal->id }}">Activar</button>
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" id="cambiarEstadoCausal" value="{{route('cortes_produccion.changeStatusCausal')}}">
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear/editar Corte --}}
    <div class="modal fade modal-modern" id="corteModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="corteModalLabel">Crear/Editar Corte</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombreCorte">Nombre</label>
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
                        <input type="text" class="form-control" id="meta" name="meta">
                    </div>
                    <div class="form-group">
                        <label for="dobles">Umbral dobles sabado</label>
                        <input type="text" class="form-control" id="dobles" name="dobles">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary-modern" id="btn_cerrarCorte">Cerrar</button>
                    <button type="submit" id="crearCorte" class="btn-gradient btn-gradient-primary">Guardar</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear/editar Causal --}}
    <div class="modal fade modal-modern" id="causalModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="causalModalLabel">Crear/Editar Causal</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombreCausal">Nombre</label>
                        <input type="text" class="form-control" id="nombreCausal" name="nombre">
                        <input type="hidden" id="idGuardarCausal">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary-modern" id="btn_cerrarCausal">Cerrar</button>
                    <button type="submit" id="crearCausal" class="btn-gradient btn-gradient-primary">Guardar Causal</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        const rangosFechasExistentes = @json($cortes);
    </script>
@stop

@section('js')
    <script>
        const rangosFechasExistentes = @json($cortes);
    </script>

    <script src="{{asset('js/informacion_generalV3-1.js')}}?v={{ time() }}"></script>
@stop
