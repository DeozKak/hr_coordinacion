@extends('adminlte::page')

@section('title', 'Gestion de Usuarios')

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
                    <tr>
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
                                <a href="{{route('admin.edit',['user' => $user->id])}}" class="btn btn-warning">Editar</a>
                                <form id="change_state" action="{{route('admin.changeStatus',['user' => $user->id])}}" method="POST" class="d-inline">
                                    @csrf
                                    @if ($user->state)
                                    <button type="submit" class="btn btn-danger">Desactivar</button>
                                    @else
                                    <button type="submit" class="btn btn-success">Activar</button>
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

@if (session('success'))
<script>
     document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: "top-end",
                type: "success",
                title: "{{ session('success') }}",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
        });
</script>
@endif

@section('js')
<script>
    $(document).ready(function() {

        $('#table_users').DataTable({
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
    });

    
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir el envío del formulario por defecto

                let currentForm = this; // Guardar una referencia al formulario actual
                
                Swal.fire({
                    title: '¿Estás seguro?',
                    text: '¿Quieres cambiar el estado del usuario?, el usuario no podrá acceder al sistema si lo desactivas.',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cambiar estado'
                }).then((result) => {
                    if (result.value == true) {  
                        currentForm.submit(); // Enviar el formulario si el usuario confirma
                    }
                });
            });
        });
    });
</script>
@endsection
@endsection