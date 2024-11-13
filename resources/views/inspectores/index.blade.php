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
<script>
    $(document).ready(function() {
        $('#table_users,#table_desactivar').DataTable({
            responsive: true,
            autoWidth: false,
            ordering: false,
            "language": {
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "Nada encontrado - lo siento",
                "info": "Mostrando la página _PAGE_ de _PAGES_",
                "infoEmpty": "No hay registros disponibles",
                "infoFiltered": "(Filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "first": "Primero",
                    "last": "Ultimo",
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });

        $(document).on('click', '.modalEditarInspector', function(){
            let modalEditarInspector = $('#editInspectorModal')
            modalEditarInspector.modal()

            let fila = $(this).closest('tr')
            let id = fila.find('td').eq(0).attr('data-id')
            let url = $('#urlGetData').val()
            let token = $('#tokenGetData').val()

            $.ajax({
                url:url,
                type: 'POST',
                data: {
                    _token: token,
                    id: id,
                },
                success:function(response){
                    if(response != ""){
                        $('#idInspectorEditar').val(response.inspector.id)
                        $('#nombresEditar').val(response.inspector.nombres)
                        $('#apellidosEditar').val(response.inspector.apellidos)
                        $('#typeIdEditar').val(response.inspector.type_id)
                        $('#cedulaEditar').val(response.inspector.cedula)
                        $('#supervisorEditar').val(response.inspector.supervisor)
                    }
                }
            })
        })

        $(document).on("click","#guardarEditarInspector",function(){
            let id = $('#idInspectorEditar').val()
            let nombreGuardar=$("#nombresEditar").val()
            let apellidoGuardar=$("#apellidosEditar").val()
            let supervisorGuardar=$("#supervisorEditar").val()
            let supervisorNombre = $("#supervisorEditar option:selected").text();
            let urlEditar=$(this).attr("data-url")
            let token = $('#tokenGetData').val()

            if (nombreGuardar == "" || apellidoGuardar == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Los nombres y apellidos son obligatorios',
                })
            }else{
                $.ajax({
                    url:urlEditar,
                    type:"POST",
                    dataType: 'json',
                    data:{
                        _token: token,
                        id:id,
                        nombres:nombreGuardar,
                        apellidos:apellidoGuardar,
                        supervisor:supervisorGuardar,
                    },
                    success:function(response){
                        if (response.status === 'success') {
                            let row = $('#table_users tbody tr[data-id="' + response.inspector.id + '"]');
                            row.find('td:nth-child(1)').text(nombreGuardar);
                            row.find('td:nth-child(2)').text(apellidoGuardar);
                            row.find('td:nth-child(5)').text(supervisorNombre);

                            $('#editInspectorModal').modal('hide');

                            Swal.fire({
                                icon: 'success',
                                title: 'Actualización exitosa',
                                text: response.message,
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                        }
                    }, error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Hubo un problema al actualizar el inspector.',
                        });
                    }
                })
            }
        })

        $(document).on('click', '.modalCrearInspector', function(){
            let modalCrearInspector = $('#createInspectorModal')
            modalCrearInspector.modal()
        })

        $(document).on("click","#guardarCrearInspector",function(){
            let nombreGuardar=$("#nombresCrear").val()
            let apellidoGuardar=$("#apellidosCrear").val()
            let typeIdGuardar=$("#typeIdCrear").val()
            let cedulaGuardar=$("#cedulaCrear").val()
            let supervisorGuardar=$("#supervisorCrear").val()
            let urlCrear=$(this).attr("data-url")
            let token = $('#tokenGetData').val()

            if (nombreGuardar == "" || apellidoGuardar == "" || typeIdGuardar == "" || cedulaGuardar == "" || supervisorGuardar == "") {
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Todos los campos son obligatorios',
                })
            }else{
                $.ajax({
                    url:urlCrear,
                    type:"POST",
                    dataType: 'json',
                    data:{
                        _token: token,
                        nombres:nombreGuardar,
                        apellidos:apellidoGuardar,
                        supervisor:supervisorGuardar,
                        cedula:cedulaGuardar,
                        type_id:typeIdGuardar
                    },
                    success:function(response){
                        if(response.status == "success"){
                            Swal.fire({
                                icon: response.status,
                                title: 'Exito',
                                text: response.message
                            })

                            $("#nombresCrear").val("")
                            $("#apellidosCrear").val("")
                            $("#cedulaCrear").val("")

                            // $('#createInspectorModal').modal('hide');

                            let inspector = response.inspector

                            let changeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";

                            let inspectorRow =
                                        "<tr data-id='" + inspector.id + "'>" +
                                            "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
                                            "<td>" + inspector.apellidos + "</td>" +
                                            "<td>" + inspector.type_id + "</td>" +
                                            "<td>" + inspector.cedula + "</td>" +
                                            "<td>" + inspector.supervisor.name + "</td>" +
                                            "<td>" +
                                                (inspector.state == 1 ?
                                                    "<span class='badge badge-success'>Activo</span>" :
                                                    "<span class='badge badge-danger'>Inactivo</span>"
                                                ) +
                                            "</td>" +
                                            "<td>" +
                                                "<div class='btn-group' role='group' aria-label='Botones'>" +
                                                    "<button class='btn btn-warning modalEditarInspector'>Editar</button>" +
                                                    "<form id='change_state' action='"+ changeStateUrl.replace('__ID__', inspector.id) + "' method='POST' class='d-inline changeStateForm'>" +
                                                        "<input type='hidden' name='_token' value='" + token + "'>" +
                                                        (inspector.state == 1 ?
                                                            "<button type='submit' class='btn btn-danger'>Desactivar</button>" :
                                                            "<button type='submit' class='btn btn-success'>Activar</button>"
                                                        ) +
                                                    "</form>" +
                                                "</div>" +
                                            "</td>" +
                                        "</tr>";

                                        $('#table_users').DataTable().row.add($(inspectorRow)).draw(false);
                        }else if(response.status == "errorRegistro"){
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message

                            })
                        }else if(response.status == "warning"){
                            Swal.fire({
                                icon: response.status,
                                title: 'Advertencia',
                                text: response.message

                            })
                        }else if(response.status == "error"){
                            Swal.fire({
                                icon: response.status,
                                title: 'Advertencia',
                                text: response.message
                            })
                        }
                    }
                })
            }
        })


        $(document).on('click', '.modalDesactivados',function() {
            let modalDesactivados = $('#showDisabledInspectorsModal')
            modalDesactivados.modal()
            let url = $(this).attr('data-url')

            $.ajax({
                url:url,
                type:'GET',
                success:function(response){
                    let inspectorArray = response.inspector
                    if(inspectorArray.length > 0){
                        $("#table_desactivar").DataTable().clear().draw();
                        let activeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
                        inspectorArray.forEach(inspector => {
                            let inspectorRow = "<tr data-id='" + inspector.id + "'>" +
                                                    "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
                                                    "<td>" + inspector.apellidos + "</td>" +
                                                    "<td>" + inspector.type_id + "</td>" +
                                                    "<td>" + inspector.cedula + "</td>" +
                                                    "<td>" + inspector.supervisor.name + "</td>" +
                                                    "<td><span class='badge badge-danger'>Inactivo</span></td>" +
                                                    "<td>" +
                                                        "<div class='btn-group' role='group' aria-label='Botones'>" +
                                                            "<form class='d-inline'>" +
                                                                "<button type='button' data-url='"+ activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-success btn-sm active_state'>Activar</button>" +
                                                            "</form>" +
                                                        "</div>" +
                                                    "</td>" +
                                                "</tr>";
                            $("#table_desactivar").DataTable().row.add($(inspectorRow)).draw();

                        });
                    }
                }
            })
        })
        $(document).on('click', '.change_state', function(e) {
            e.preventDefault(); // Prevenir el envío del formulario por defecto
            let url = $(this).attr('data-url')
            let tokenDesactivar = $('#tokenGetData').val()
            let idTable = $(this).closest('tr').eq(0).attr('data-id')
            Swal.fire({
                title: '¿Estás seguro?',
                text: '¿Quieres cambiar el estado del inspector? Una vez desactivado, el inspector no estará disponible en Bitácoras y no podrá recibir asignaciones de órdenes.',
                icon: 'warning', // Cambiado de type a icon
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cambiar estado',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url:url,
                        type:'POST',
                        data:{
                            _token:tokenDesactivar
                        },
                        success:function(response){
                            if(response.status == 'success'){
                                let inspector=response.inspector
                                if(idTable == inspector.id){
                                    let table = $('#table_users').DataTable();
                                    let row = $('#table_users').find(`tr[data-id="${idTable}"]`);
                                    table.row(row).remove().draw(false);
                                    Swal.fire('¡Hecho!', 'El inspector se desactivó exitosamente', 'success');
                                }
                            }else{
                                Swal.fire('¡Error!', 'Ha ocurrido un error al cambiar el estado del Inspector', 'error');
                            }
                        }
                    })

                }
            });
        });
        $(document).on('click','.active_state', function(e) {
            e.preventDefault(); // Prevenir el envío del formulario por defecto
            let tokenActivar = $('#tokenGetData').val()
            let url = $(this).attr('data-url')
            let idTable = $(this).closest('tr').eq(0).attr('data-id')
            Swal.fire({
                title: '¿Estás seguro?',
                text: '¿Quieres cambiar el estado del inspector? Una vez activado, el inspector estará disponible en Bitácoras y podrá recibir asignaciones de órdenes.',
                icon: 'warning', // Cambiado de type a icon
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cambiar estado',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) { // Cambiado de result.value a result.isConfirmed
                    $.ajax({
                        url:url,
                        type: 'POST',
                        dataType: 'json',
                        data:{
                            _token: tokenActivar
                        },
                        success:function(response){
                            if(response.status == 'success'){
                                let inspector=response.inspector
                                if(idTable == inspector.id){
                                    let activeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
                                    let table = $('#table_desactivar').DataTable();
                                    let row = $('#table_desactivar').find(`tr[data-id="${idTable}"]`);
                                    table.row(row).remove().draw();

                                    let inspectorRow = "<tr data-id='" + inspector.id + "'>" +
                                                            "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
                                                            "<td>" + inspector.apellidos + "</td>" +
                                                            "<td>" + inspector.type_id + "</td>" +
                                                            "<td>" + inspector.cedula + "</td>" +
                                                            "<td>" + inspector.supervisor.name + "</td>" +
                                                            "<td><span class='badge badge-success'>Activo</span></td>" +
                                                            "<td>" +
                                                                "<div class='btn-group' role='group' aria-label='Botones'>" +
                                                                    "<button class='btn btn-warning modalEditarInspector'>Editar</button>"+
                                                                    "<form class='d-inline'>" +
                                                                        "<button type='button' data-url='"+ activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-danger change_state'>Desactivar</button>" +
                                                                    "</form>" +
                                                                "</div>" +
                                                            "</td>" +
                                                        "</tr>";
                                            $("#table_users").DataTable().row.add($(inspectorRow)).draw(false);

                                    Swal.fire('¡Hecho!', 'El inspector se activó exitosamente', 'success');
                                }
                            }else{
                                Swal.fire('¡Error!', 'Ha ocurrido un error al cambiar el estado del Inspector', 'error');
                            }
                        }
                    })
                }
            });
        });

        $(document).on('input', '#cedulaCrear',function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    });

</script>
@endsection
@endsection
