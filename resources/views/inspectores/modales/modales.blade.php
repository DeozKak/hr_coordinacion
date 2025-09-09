<!-- Modal Editar Inspector -->
<div class="modal fade modal-modern" id="editInspectorModal" tabindex="-1" role="dialog" aria-labelledby="editInspectorModalLabel"
aria-hidden="true">
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
                    <label for="idEditar">ID</label>
                    <input type="text" id="idEditar" class="form-control" disabled>
                </div>
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
            <button type="button" class="btn-secondary-modern" data-dismiss="modal">Cerrar</button>
            <button type="button" id="guardarEditarInspector" data-url="{{route('inspectores.update')}}" class="btn-gradient btn-gradient-primary">Guardar Cambios</button>
        </div>
    </div>
</div>
</div>


<!-- Modal Crear Inspector -->
<div class="modal fade modal-modern" id="createInspectorModal" tabindex="-1" role="dialog"
aria-labelledby="createInspectorModalLabel" aria-hidden="true">
<div class="modal-dialog" role="document">
    <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title">Nuevo Inspector</h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
            <div class="alert-modern alert-info-modern">
                <i class="fa fa-info-circle"></i>
                Por favor colocar información tal cual está en movilidad con el fin de evitar
                errores con el cruce de información entre las aplicaciones.
            </div>

            <!-- Contenido de create.blade -->
            <form action="" autocomplete="off">
                @csrf
                <!-- Campos del formulario de creación -->
                <div class="form-group">
                    <label for="idCrear">ID</label>
                    <input type="text" id="idCrear" class="form-control">
                </div>
                <div class="form-group">
                    <label for="nombres">Nombre</label>
                    <input type="text" name="nombres" id="nombresCrear" class="form-control"
                        value="{{old('nombres')}}">
                </div>
                <div class="form-group
                    ">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" name="apellidos" id="apellidosCrear" class="form-control"
                        value="{{old('apellidos')}}">
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
                    <input type="text" name="cedula" id="cedulaCrear" class="form-control"
                        value="{{old('cedula')}}">
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
            <button type="button" id="guardarCrearInspector" data-url="{{ route('inspectores.store') }}" class="btn-gradient btn-gradient-success">Guardar</button>
            </form>
            <button type="button" class="btn-secondary-modern" data-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
</div>


<!-- Modal Ver Desactivados -->
<div class="modal fade modal-modern" id="showDisabledInspectorsModal" tabindex="-1" role="dialog"
aria-labelledby="showDisabledInspectorsModalLabel" aria-hidden="true">
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
                            <th>ID</th>
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
            <button type="button" class="btn-secondary-modern" data-dismiss="modal">Cerrar</button>
        </div>
    </div>
</div>
</div>
