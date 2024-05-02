@extends('adminlte::page')

@section('title', 'Inspectores')

@section('content_header')
    <h1>Inspectores</h1>
@endsection

@section('content')

<div class="card">
    <div class="card-body">
        <div class="table-responsive">
            <table id="table_users" class="table table-sm">
                <thead>
                    <tr>
                        <th>Nombres</th>
                        <th>Apellidos</th>
                        <th>Cedula</th>
                        <th>Supervisor</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($inspectores as $inspector)
                    <tr>
                        <td>{{$inspector->nombres}}</td>
                        <td>{{$inspector->apellidos}}</td>
                        <td>{{$inspector->cedula}}</td>
                        <td></td>
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
</script>
@endsection
@endsection