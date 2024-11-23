@extends('adminlte::page')

@section('title', 'Inspectores')

@section('content_header')
    <h1>Inspectores</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/admin/index.css')}}">

<div class="card">
    <div class="card-body">
        <!-- Botones para abrir los modales -->
        <button class="btn btn-success mb-2 modalCrearInspector">Nuevo Inspector</button>
        <button data-url="{{route('inspectores.show_disabled')}}" class="btn btn-primary mb-2 modalDesactivados">Ver desactivados</button>

        <div class="table-responsive">
            <!-- Tabla de Inspectores -->
            <table id="table_users" class="table table-sm">
                <thead>
                    <tr>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Tipo de identificación</th>
                        <th>Cedula</th>
                        <th>Supervisor</th>
                        <th>Aprendiz</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody id="tableBody">
                    @foreach ($inspectores as $inspector)
                    <tr data-id="{{$inspector->id}}">
                        <td data-id="{{$inspector->id}}">{{$inspector->nombres}}</td>
                        <td>{{$inspector->apellidos}}</td>
                        <td>{{$inspector->type_id}}</td>
                        <td>{{$inspector->cedula}}</td>
                        <td>{{$inspector->supervisor->name}}</td>
                        <td>
                            @if($inspector->aprendiz)
                            <span class="badge bg-warning text-dark">SI</span>
                            @else
                            <span class="badge bg-secondary">NO</span>
                            @endif
                        </td>
                        <td>
                            @if ($inspector->state)
                            <span class="badge badge-success">Activo</span>
                            @else
                            <span class="badge badge-danger">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group" aria-label="Botones">
                                <!-- Botón para abrir el modal de edición -->
                                <button class="btn btn-warning modalEditarInspector">Editar</button>

                                <form class="d-inline">
                                    <button type="button" data-url="{{route('inspectores.change_state',['inspector' => $inspector->id])}}" class="btn btn-danger change_state">Desactivar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <input type="hidden" id="urlGetData" value="{{ route('inspector.getData')}}">
            <input type="hidden" id="tokenGetData" value="{{csrf_token()}}">
        </div>
    </div>
</div>
<!-- Modal Editar Inspector (fuera del bucle) -->
<div class="modal fade" id="editInspectorModal" tabindex="-1" role="dialog" aria-labelledby="editInspectorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Inspector</h5>
                <input type="hidden" id="idInspectorEditar">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form autocomplete="off">
                    <div class="form-group">
                        <label for="nombres">Nombre</label>
                        <input type="text" name="nombres" id="nombresEditar" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="apellidos">Apellidos</label>
                        <input type="text" name="apellidos" id="apellidosEditar" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="Tipo de identificacion">Tipo de identificación</label>
                        <select name="type_id" id="typeIdEditar" class="form-control" disabled>
                            <option value="">Seleccione un tipo de identificación</option>
                            <option value="CC">CC</option>
                            <option value="CE">CE</option>
                        </select>
                    </div>
                        <div class="form-group ">
                            <label for="cedula">Identificación</label>
                            <input type="text" name="cedula" id="cedulaEditar" class="form-control" disabled>
                        </div>
                    <div class="form-group">
                        <label for="supervisor">Supervisor</label>
                        <select name="supervisor" id="supervisorEditar" class="form-control">
                            @foreach ($supervisores as $supervisor)
                            <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="Aprendiz">Aprendiz</label>
                        <select name="Aprendiz" id="aprendizEditar" class="form-control">
                            <option value="0" selected>NO</option>
                            <option value="1">SI</option>
                        </select>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" id="guardarEditarInspector" data-url="{{route('inspectores.update')}}" class="btn btn-primary">Guardar</button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Crear Inspector -->
<div class="modal fade" id="createInspectorModal" tabindex="-1" role="dialog" aria-labelledby="createInspectorModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Nuevo Inspector</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <small class="text-muted">Por favor colocar información tal cual está en movilidad con el fin de evitar errores con el cruce de información entre las aplicaciones.</small>
                <br>
                <br>
                <!-- Contenido de create.blade -->
                <form action="" autocomplete="off">
                    @csrf
                    <!-- Campos del formulario de creación -->
                    <div class="form-group">
                        <label for="nombres">Nombre</label>
                            <input type="text" name="nombres" id="nombresCrear" class="form-control" value="{{old('nombres')}}" >
                        </div>
                        <div class="form-group
                        ">
                            <label for="apellidos">Apellidos</label>
                            <input type="text" name="apellidos" id="apellidosCrear" class="form-control" value="{{old('apellidos')}}" >
                        </div>
                        <div class="form-group
                        ">
                            <label for="Tipo de identificacion">Tipo de identificación</label>
                            <select name="type_id" id="typeIdCrear" class="form-control">
                                <option value="">Seleccione un tipo de identificación</option>
                                <option value="CC">CC</option>
                                <option value="CE">CE</option>
                            </select>
                        </div>
                        <div class="form-group ">
                            <label for="cedula">Identificación</label>
                            <input type="text" name="cedula" id="cedulaCrear" class="form-control" value="{{old('cedula')}}" >
                        </div>
                        <div class="form-group ">
                            <label for="supervisor">Supervisor</label>
                            <select name="supervisor" id="supervisorCrear" class="form-control">
                                <option value="">Seleccione un supervisor</option>
                                @foreach ($supervisores as $supervisor)
                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                                @endforeach
                            </select>
                        </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="guardarCrearInspector" data-url="{{ route('inspectores.store') }}" class="btn btn-primary">Guardar</button>
                </form>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal Ver Desactivados -->
<div class="modal fade" id="showDisabledInspectorsModal" tabindex="-1" role="dialog" aria-labelledby="showDisabledInspectorsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inspectores Desactivados</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="table_desactivar" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Nombres</th>
                                <th>Apellidos</th>
                                <th>Tipo de identificación</th>
                                <th>Cedula</th>
                                <th>Supervisor</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tableDesactivar">

                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{asset('js/inspectores/inspectores.js')}}"></script>
<script>
    let changeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
    let activeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
</script>
@endsection
@endsection
