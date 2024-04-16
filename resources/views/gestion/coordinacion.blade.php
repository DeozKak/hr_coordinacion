@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1>Coordinación</h1>
@endsection

@section('content')
<style>
    div.dataTables_wrapper {
        width: 100%;
        margin: 0 auto;
    }
</style>
<div class="card">
    <div class="card-body">
        <div class="row">
            <table id="table" class="table table-sm">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Contrato</th>
                        <th>Producto</th>
                        <th>Numero solicitud</th>
                        <th>Tipo solicitud</th>
                        <th>Cedula</th>
                        <th>Nombre</th>
                        <th>Departamento</th>
                        <th>Localidad</th>
                        <th>Barrio</th>
                        <th>Dirección</th>
                        <th>Consecutivo Ruta</th>
                        <th>Telefono</th>
                        <th>Medidor</th>
                        <th>Categoria</th>
                        <th>Unidad</th>
                        <th>Tipo trabajo</th>
                        <th>Fecha asignación</th>
                        <th>Observación solicitud</th>
                    </tr>
                </thead>
                <tbody>

                    <tr>

                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>

                    </tr>

                </tbody>
            </table>

        </div>
    </div>
</div>
@section('js')
<script>
    $(document).ready(function() {

        $('#table').DataTable({
            "processing": true, // Mostrar el indicador de procesamiento
            "serverSide": true, // Habilitar el procesamiento del lado del servidor
            "language": {
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "Nada encontrado - lo siento",
                "info": "Mostrando la página _PAGE_ de _PAGES_",
                "infoEmpty": "No hay registros disponibles",
                "infoFiltered": "(Filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            },
            "ajax": {
                "url": "{{ route('getdataCoordinacion') }}", // Ruta para obtener los datos del servidor
                "type": "GET" // Método de solicitud
            },
            "columns": [

                {
                    data: 'orden'
                },
                {
                    data: 'contrato'
                },
                {
                    data: 'producto'
                },
                {
                    data: 'numero_solicitud'
                },
                {
                    data: 'tipo_solicitud'
                },
                {
                    data: 'NIT_CC'
                },
                {
                    data: 'nombre_lugar'
                },
                {
                    data: 'departamento'
                },
                {
                    data: 'localidad'
                },
                {
                    data: 'sector_operativo'
                },
                {
                    data: 'direccion'
                },
                {
                    data: 'consecutivo_ruta'
                },
                {
                    data: 'telefono'
                },
                {
                    data: 'medidor'
                },
                {
                    data: 'categoria'
                },
                {
                    data: 'unidad_operativa'
                },
                {
                    data: 'tipo_trabajo'
                },
                {
                    data: 'fecha_asignacion'
                },
                {
                    data: 'observacion_solicitud'
                }
            ],
            scrollY: "400px",
            scrollCollapse: true,
            paging: false
        });
    });
</script>
@endsection
@endsection