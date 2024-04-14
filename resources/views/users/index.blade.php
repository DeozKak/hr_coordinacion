@extends('adminlte::page')

@section('title', 'Gestion de Usuarios')

@section('content_header')
<h1>Gestión de Usuarios</h1>
@endsection


@section('content')

<div class="card">
    <div class="card-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Email</th>
                    <th>Roles</th>
                    <th>Permisos</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                <tr>
                    <td>{{$user->name}}</td>
                    <td>{{$user->email}}</td>
                    <td>
                        @foreach ($user->roles as $role)
                        <span class="badge badge-primary">{{$role->name}}</span>
                        @endforeach
                    </td>
                    <td>
                        @foreach ($user->permissions as $permission)
                        <span class="badge badge-primary">{{$permission->name}}</span>
                        @endforeach
                    <td>
                        <a href="{{route('admin.edit',['user' => $user->id])}}" class="btn btn-warning">Editar</a>
                        <form  method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

@if (session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "Error",
            text: "{{session('error')}}",
            type: "error"
        });
    });
</script>
@endif
@endsection