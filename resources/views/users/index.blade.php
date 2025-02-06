@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
<h1>Gestión de Usuarios</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/admin/index.css')}}">
<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="table_users" class="table table-sm">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Tipo de identificación</th>
                        <th>Identificación</th>
                        <th>Roles</th>
                        <th>Permisos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($users as $user)
                    <tr data-id="{{$user->id}}">
                        <td>{{$user->name}}</td>
                        <td>{{$user->email}}</td>
                        <td>{{$user->type_id}}</td>
                        <td>{{$user->identification}}</td>
                        <td>
                            @foreach ($user->roles as $role)
                            <span class="badge badge-primary">{{$role->name}}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach ($user->permissions as $permission)
                            <span class="badge badge-primary">{{$permission->name}}</span>
                            @endforeach
                        </td>
                        <td>
                            @if ($user->state)
                            <span class="badge badge-success">Activo</span>
                            @else
                            <span class="badge badge-danger">Inactivo</span>
                            @endif
                        <td>
                            <div class="btn-group" role="group" aria-label="Botones">
                                <button class="btn btn-warning edit-btn" data-toggle="modal" data-target="#editUserModal" data-token="{{csrf_token()}}" data-url="{{route('profile.getDataPermissions')}}" data-user="{{ $user }}">Editar</button>
                                <form method="POST" class="d-inline changeForm">
                                    @if ($user->state)
                                    <button type="button" id="desactive_user" data-url="{{route('admin.changeStatus',['user' => $user->id])}}" class="btn btn-danger">Desactivar</button>
                                    @else
                                    <button type="button" id="active_user" data-url="{{route('admin.changeStatus',['user' => $user->id])}}" class="btn btn-success">Activar</button>
                                    @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            <input type="hidden" class="tokenUser" value="{{csrf_token()}}">
        </div>
    </div>
</div>

<!-- Modal de Edición de Usuario -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" id="editUserForm" autocomplete="off">
                <input type="hidden" id="urlEnviar" value="">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
                    <input type="hidden" id="idUsuarioEditar">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" name="name" id="nombresEditar" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="emailEditar" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="type_id">Tipo de identificación</label>
                        <input type="text" name="type_id" id="typeidEditar" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label for="identification">Identificación</label>
                        <input type="text" name="identification" id="cedulaEditar" class="form-control" disabled>
                    </div>
                    <div class="form-group">
                        <label for="roles">Rol</label>
                        <select name="roles" id="rolesEditar" class="form-control">
                            @foreach ($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group permissions">
                        <label for="permissions">Permisos Disponibles</label>
                        <span style="margin-left: 150px;"></span>
                        <label for="permissions">Permisos asignados</label>
                        <div class="d-flex align-items-start">
                            <select id="revokedPermissions" class="form-control" style="margin-right: 10px;" multiple>
                                {{-- permisos disponibles --}}
                            </select>

                            <div style="display: flex; flex-direction: column;">
                                <a type="button" class="btn btn-primary" id="assignPermission" style="margin-bottom: 10px;">Asignar</a>
                                <a type="button" class="btn btn-primary" id="removePermission">Quitar</a>
                            </div>

                            <select id="assignedPermissions" class="form-control" style="margin-left: 10px;" multiple>
                                {{-- permisos asignados --}}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer justify-content-center">
                    <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    <button type="button" id="guardarEditarUsuario" data-url="{{route('admin.update')}}" class="btn btn-primary">Guardar</button>
                    <button type="button" id="cambiarClave" class="btn btn-warning">Cambiar Contraseña</button>
                </div>
            </form>
        </div>
    </div>
</div>

@section('js')
<script>
$(document).ready(function() {
    $('#table_users').DataTable({
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
    $(document).on('click', '.edit-btn', function() {
        const user = $(this).data('user')
        $('#idUsuarioEditar').val(user.id)
        $('#nombresEditar').val(user.name)
        $('#emailEditar').val(user.email)
        $('#typeidEditar').val(user.type_id)
        $('#cedulaEditar').val(user.identification)

        // Asegúrate de que este valor coincide con el `value` de tus opciones
        $('#rolesEditar').val(user.roles[0].name)

        let divClave = $('.div-clave')
        if(divClave.length == 2){
            divClave.remove()
            $('#cancelarClave').remove()
            $('#cambiarClave').show()
        }

        let id = user.id
        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')
        let url2 = $('#urlEnviar').val()

        $.post({
            url:url,
            data:{
                _token: token,
                id: id,
            },success:function(response){
                let asignadas = response.asignadas
                let disponibles = response.disponibles

                $('#revokedPermissions').empty()
                $('#assignedPermissions').empty()

                disponibles.forEach(element => {
                    // console.log(element.name)
                    $('#revokedPermissions').append('<option value="'+element.name+'">'+element.name+'</option>');
                });

                asignadas.forEach(element => {
                    // console.log(element.name)
                    $('#assignedPermissions').append('<option value="'+element.name+'">'+element.name+'</option>');
                });

            }
        })
    });

    $(document).on("click","#guardarEditarUsuario",function(){
        let id = $('#idUsuarioEditar').val()
        let nombreGuardar=$("#nombresEditar").val()
        let emailGuardar=$("#emailEditar").val()
        let rolGuardar = $("#rolesEditar").val()
        let urlEditar=$(this).attr("data-url")
        let token = $('.tokenUser').val()
        let claveNueva = $('#claveNueva').val()
        let claveConfirmar = $('#claveConfirmar').val()

        let assignedPermissions =[]
        let revokedPermissions = []
        $('#assignedPermissions option').each(function() {
            let value = $(this).val()
            assignedPermissions.push(value)
        });

        $('#revokedPermissions option').each(function() {
            let value = $(this).val()
            revokedPermissions.push(value)
        });

        if((claveNueva != undefined && claveNueva.trim() == "") ||
            (claveConfirmar != undefined && claveConfirmar.trim() == "")){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'El campo de contraseña es obligatorio',
            })
            return
        }

        if (nombreGuardar == "" || emailGuardar == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'El campo de nombre y email son obligatorios',
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
                    email:emailGuardar,
                    roles:rolGuardar,
                    assignedPermissions:assignedPermissions,
                    revokedPermissions:revokedPermissions,
                    claveNueva:claveNueva,
                    claveConfirmar:claveConfirmar
                },
                success:function(response){
                    switch (response.status) {
                        case 'success' :
                                let row = $('#table_users tbody tr[data-id="' + response.user.id + '"]');
                                row.find('td:nth-child(1)').text(response.user.name);
                                row.find('td:nth-child(2)').text(response.user.email);
                                row.find('td:nth-child(5)').html('<span class="badge badge-primary">' + rolGuardar + '</span>');
                                row.find('td:nth-child(6)').empty()
                                assignedPermissions.forEach(element => {
                                    row.find('td:nth-child(6)').append('<span class="badge badge-primary">' + element + '</span>');
                                });
                                $('#editUserModal').modal('hide');
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualización exitosa',
                                    text: response.message,
                                });
                            break;
                        case 'error' :
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                            break;
                        case 'passwordDiff' :
                            Swal.fire({
                                icon: 'warning',
                                title: 'Advertencia',
                                text: response.message,
                            });
                        case 'passowordLength' :
                            Swal.fire({
                                icon: 'warning',
                                title: 'Advertencia',
                                text: response.message,
                            });
                            break;
                    }
                }, error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al actualizar el Usuario',
                    });
                }
            })
        }
    })

    $(document).on('click','#desactive_user, #active_user', function(){
        let token = $('.tokenUser').val()
        let url = $(this).attr('data-url')
        let changeStateUrl = "{{ route('admin.changeStatus', ['user' => '__ID__']) }}";
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Quieres cambiar el estado del usuario?',
            icon: 'warning',
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
                        _token:token
                    },
                    success:function(response){
                        let user = response.user
                        console.log(user)
                        let row = $('#table_users tbody tr[data-id="' + user.id + '"]');
                        if(user.state == 0){
                            row.find('td:nth-child(7)').html('<span class="badge badge-danger">Inactivo</span>');
                            row.find('td:nth-child(8) #desactive_user').remove()
                            let newButton = "<button type='button' id='active_user' data-url='"+ changeStateUrl.replace('__ID__', user.id) + "' class='btn btn-success'>Activar</button>"
                            row.find('td:nth-child(8) .changeForm').append(newButton);
                        }else{
                            row.find('td:nth-child(7)').html('<span class="badge badge-success">Activo</span>');
                            row.find('td:nth-child(8) #active_user').remove()
                            let newButton = "<button type='button' id='desactive_user' data-url='"+ changeStateUrl.replace('__ID__', user.id) + "' class='btn btn-danger'>Desactivar</button>"
                            row.find('td:nth-child(8) .changeForm').append(newButton);
                        }
                    }
                })
            }
        });
    });

    $(document).on('click','#cambiarClave',function(){
        let newBlock =  "<div class='form-group mt-3 div-clave'>"+
                            "<label for='claveNueva'>Nueva contraseña</label>"+
                            "<input type='password' id='claveNueva' class='form-control'>"+
                            "<i class='fa fa-eye' id='togglePassword' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>"+
                        "</div>"+
                        "<div class='form-group div-clave'>"+
                            "<label for='claveConfirmar'>Confirmar contraseña</label>"+
                            "<input type='password' id='claveConfirmar' class='form-control'>"+
                            "<i class='fa fa-eye' id='togglePasswordConfirm' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>"+
                        "</div>";

        $('.permissions').append(newBlock)

        $('#cambiarClave').hide()

        let newButton = "<button type='button' id='cancelarClave' class='btn btn-danger'>Cancelar cambio</button>"

        $('.modal-footer').append(newButton)
    })

    $(document).on('click', '#togglePassword',function(){
        const passwordField = $('#claveNueva');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        // Cambia el icono entre ojo abierto y cerrado
        $(this).toggleClass('fa-eye fa-eye-slash');
    })

    $(document).on('click', '#togglePasswordConfirm',function(){
        const passwordField = $('#claveConfirmar');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        // Cambia el icono entre ojo abierto y cerrado
        $(this).toggleClass('fa-eye fa-eye-slash');
    })

    $(document).on('click', '#cancelarClave',function(){
        $('.div-clave').remove()
        $('#cambiarClave').show()
        $('#cancelarClave').remove()
    })

    // Asignar y remover permisos con botones
    $('#assignPermission').click(function() {
        $('#revokedPermissions option:selected').each(function() {
            $(this).remove().appendTo('#assignedPermissions');
        });
    });

    $('#removePermission').click(function() {
        $('#assignedPermissions option:selected').each(function() {
            $(this).remove().appendTo('#revokedPermissions');
        });
    });
});


</script>
@endsection
@endsection
