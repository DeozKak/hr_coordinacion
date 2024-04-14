@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
<h1>Editar Usuario</h1>
@endsection
@section('content')
<link rel="stylesheet" href="{{asset('css/admin/edit.css')}}">
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('admin.update', $user)}}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Nombre</label>
                            <input type="text" name="name" id="name" class="form-control" value="{{$user->name}}">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" name="email" id="email" class="form-control" value="{{$user->email}}">
                        </div>
                        <div class="form-group">
                            <label for="roles">Roles</label>
                            <select name="roles" id="roles" class="form-select">
                                @foreach ($roles as $role)
                                @if ($currentRole && $role->id === $currentRole->id)
                                <option value="{{$role->name}}" selected>{{$role->name}}</option>
                                @else
                                <option value="{{$role->name}}">{{$role->name}}</option>
                                @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="permissions">Permisos Disponibles</label>
                            <span style="margin: 110px;"></span>
                            <label for="permissions">Permisos asignados</label>
                            <div class="d-flex align-items-start">
                                <select id="revokedPermissions" class="form-select" style="margin-right: 10px;" multiple>
                                    @foreach($availablePermissions as $permission)
                                    <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>

                                <div style="display: flex; flex-direction: column;">
                                    <a type="button" class="btn btn-primary" id="assignPermission" style="margin-bottom: 10px;">Asignar</a>

                                    <a type="button" class="btn btn-primary" id="removePermission">Quitar</a>

                                </div>
                                <select id="assignedPermissions" class="form-select" style="margin-left: 10px;" multiple>
                                    @foreach($userPermissions as $permission)
                                    <option value="{{ $permission->name }}">{{ $permission->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="button-container">
                            <button type="submit" id="enviar" class="btn btn-primary">Guardar</button>
                    </form>
                    <a href="{{route('admin.index')}}" class="btn btn-danger" style="margin-right: 10px;">Cancelar</a>
                    <a href="{{route('admin.password')}}" class="btn btn-warning">Cambiar Contraseña</a>
                </div>
            </div>
            </div>
        </div>
    </div>
</div>
</div>
@section('js')
<script>
    document.getElementById('assignPermission').addEventListener('click', function() {
        var revokedPermissions = document.getElementById('revokedPermissions');
        var assignedPermissions = document.getElementById('assignedPermissions');

        var selectedPermission = revokedPermissions.options[revokedPermissions.selectedIndex];
        assignedPermissions.appendChild(selectedPermission);
    });

    document.getElementById('removePermission').addEventListener('click', function() {
        var revokedPermissions = document.getElementById('revokedPermissions');
        var assignedPermissions = document.getElementById('assignedPermissions');

        var selectedPermission = assignedPermissions.options[assignedPermissions.selectedIndex];
        revokedPermissions.appendChild(selectedPermission);
    });

    document.getElementById('enviar').addEventListener('click', function() {
        var assignedPermissionsSelect = document.getElementById('assignedPermissions');
        var revokedPermissionsSelect = document.getElementById('revokedPermissions');

        var assignedPermissions = Array.from(assignedPermissionsSelect.options).map(option => option.value);
        var revokedPermissions = Array.from(revokedPermissionsSelect.options).map(option => option.value);

        // Agregar los permisos al formulario como campos ocultos
        var assignedPermissionsInput = document.createElement('input');
        assignedPermissionsInput.setAttribute('type', 'hidden');
        assignedPermissionsInput.setAttribute('name', 'assignedPermissions');
        assignedPermissionsInput.setAttribute('value', JSON.stringify(assignedPermissions));
        this.appendChild(assignedPermissionsInput);

        var revokedPermissionsInput = document.createElement('input');
        revokedPermissionsInput.setAttribute('type', 'hidden');
        revokedPermissionsInput.setAttribute('name', 'revokedPermissions');
        revokedPermissionsInput.setAttribute('value', JSON.stringify(revokedPermissions));
        this.appendChild(revokedPermissionsInput);
    });
</script>
@endsection
@endsection