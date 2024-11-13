@extends('adminlte::page')

@section('title', 'Inspectores Desactivados')

@section('content_header')
<h1>Inspectores Desactivados</h1>
@endsection

@section('content')

<link rel="stylesheet" href="{{asset('css/admin/index.css')}}">
<div class="card">
    <div class="card-body">
        <a  href="{{route('inspectores.index')}}" class="btn btn-danger mb-2">Volver</a>
        <div class="table-responsive">
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
                <tbody>
                    @foreach ($inspectores as $inspector)
                    <tr>
                        <td>{{$inspector->nombres}}</td>
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
                                <form id="change_state" action="{{route('inspectores.change_state',['inspector' => $inspector->id])}}" method="POST" class="d-inline">
                                    @csrf
                                    @if ($inspector->state)
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
    });

    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault(); // Prevenir el envío del formulario por defecto

                let currentForm = this; // Guardar una referencia al formulario actual

                Swal.fire({
                    title: '¿Estás seguro?',
                    text: '¿Quieres cambiar el estado del inspector? Una vez activado, el inspector estará disponible en Bitácoras y podrá recibir asignaciones de órdenes.',
                    type: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cambiar estado',
                    cancelButtonText: 'Cancelar'
                }).then((result) => {
                    if (result.value == true) {
                        currentForm.submit(); // Enviar el formulario si el usuario confirma
                    }
                });
            });
        });
    });


    function goBack() {
        window.history.back();
    }

</script>

@endsection
@endsection
