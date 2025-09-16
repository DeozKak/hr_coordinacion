@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
    <h1>Gestión de Usuarios</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/admin/index_usuarios.css')}}">
    <input type="hidden" id="token" value="{{ csrf_token()  }}">
    <div class="shadow-container">
        <div class="table-responsive">
            <table id="table_users" class="table">
                <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Tipo ID</th>
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
                                <span class="badge-modern badge-primary-modern">{{$role->name}}</span>
                            @endforeach
                        </td>
                        <td>
                            @php
                                $permissions = $user->permissions;
                                $limit = 3; // Muestra los primeros 3 permisos
                            @endphp

                            {{-- Contenedor para los permisos --}}
                            <div class="permission-tags">
                                {{-- Muestra solo los primeros permisos hasta el límite --}}
                                @foreach ($permissions->take($limit) as $permission)
                                    <span class="badge-modern badge-primary-modern">{{ $permission->name }}</span>
                                @endforeach

                                {{-- Si hay más permisos que el límite, muestra el botón "+X más" --}}
                                @if ($permissions->count() > $limit)
                                    <span class="badge-modern badge-more">
                +{{ $permissions->count() - $limit }} más
            </span>
                                @endif

                                {{-- Contenedor oculto para el resto de los permisos --}}
                                <div class="hidden-permissions">
                                    @foreach ($permissions->slice($limit) as $permission)
                                        <span class="badge-modern badge-primary-modern">{{ $permission->name }}</span>
                                    @endforeach
                                </div>
                            </div>
                        </td>
                        <td>
                            @if ($user->state)
                                <span class="badge-modern badge-success-modern">Activo</span>
                            @else
                                <span class="badge-modern badge-danger-modern">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn-gradient btn-gradient-warning btn-sm edit-btn" data-toggle="modal"
                                        data-target="#editUserModal" data-token="{{csrf_token()}}"
                                        data-url="{{route('profile.getDataPermissions')}}" data-user="{{ $user }}"
                                >Editar</button>
                                <form method="POST" class="d-inline changeForm">
                                @if ($user->state)
                                    <button type="button" id="desactive_user"
                                            data-url="{{route('admin.changeStatus',['user' => $user->id])}}"
                                            class="btn-gradient btn-gradient-danger btn-sm">Desactivar
                                    </button>
                                @else
                                    <button type="button" id="active_user"
                                            data-url="{{route('admin.changeStatus',['user' => $user->id])}}"
                                            class="btn-gradient btn-gradient-success btn-sm">Activar
                                    </button>
                                @endif
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Edición de Usuario -->
    <div class="modal fade modal-modern" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
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
                                <select id="revokedPermissions" class="form-control" style="margin-right: 10px;"
                                        multiple>
                                    {{-- permisos disponibles --}}
                                </select>

                                <div style="display: flex; flex-direction: column;">
                                    <a type="button" class="btn-gradient btn-gradient-assigned " id="assignPermission"
                                       style="margin-bottom: 10px;">Asignar</a>
                                    <a type="button" class="btn-gradient btn-gradient-assigned " id="removePermission">Quitar</a>
                                </div>

                                <select id="assignedPermissions" class="form-control" style="margin-left: 10px;"
                                        multiple>
                                    {{-- permisos asignados --}}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn-gradient btn-secondary-modern" data-dismiss="modal">Cancelar</button>
                        <button type="button" id="guardarEditarUsuario" data-url="{{route('admin.update')}}"
                                class="btn-gradient btn-gradient-primary">Guardar
                        </button>
                        <button type="button" id="cambiarClave" class="btn-gradient btn-gradient-warning">Cambiar Contraseña</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('js')
        <script src="{{asset('js/users/indexV1.js')}}"></script>
        <script>
            // Define la URL base de forma correcta y la asigna a una variable de JS
            const changeStateUrl = "{{ route('admin.changeStatus', ['user' => '__ID__']) }}";
        </script>
    @endsection
@endsection
