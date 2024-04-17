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
    $('#table').DataTable( {
        serverSide: true,
        ordering: false,
        searching: false,
        ajax:{
            url: "{{ route('getdataCoordinacion') }}",
            type: 'GET',
            dataSrc: 'data'
        },success: function ( data, callback, settings ) {
            var out = [];
                console.log(data);
            for ( var i=data.start, ien=data.start+data.length ; i<ien ; i++ ) {
                out.push( [ data[i].orden,
                            data[i].contrato,
                            data[i].producto,
                            data[i].numero_solicitud,
                            data[i].tipo_solicitud,
                            data[i].NIT_CC,
                            data[i].nombre_lugar,
                            data[i].departamento,
                            data[i].localidad,
                            data[i].sector_operativo,
                            data[i].direccion,
                            data[i].consecutivo_ruta,
                            data[i].telefono,
                            data[i].medidor,
                            data[i].categoria,
                            data[i].unidad_operativa,
                            data[i].tipo_trabajo,
                            data[i].fecha_asignacion,
                            data[i].observacion_solicitud ] );
            }
 
            setTimeout( function () {
                callback( {
                    draw: data.draw,
                    data: out,
                    recordsTotal: 5000000,
                    recordsFiltered: 5000000
                } );
            }, 50 );
        },
        scrollY: 200,
        scroller: {
            loadingIndicator: true
        },
        stateSave: true
    } );
} );
/* $(document).ready(function() {
    var totalRecords = 0;
    var loadedRecords = 0;
    var dataChunkSize = 10; // Cantidad de datos a cargar en cada solicitud

    $('#table').DataTable( {
        serverSide: true,
        ordering: false,
        searching: false,
        paging: false,
        ajax: function ( data, callback, settings ) {
            $.ajax({
                url: "{{ route('getdataCoordinacion') }}",
                type: 'GET',
                data: {
                    start: data.start,
                    length: dataChunkSize
                },
                success: function(response) {
                    totalRecords = response.recordsTotal;
                    loadedRecords += dataChunkSize;

                    var out = [];
                    for (var i = 0; i < response.data.length; i++) {
                        out.push([
                            response.data[i].orden,
                            response.data[i].contrato,
                            response.data[i].producto,
                            response.data[i].numero_solicitud,
                            response.data[i].tipo_solicitud,
                            response.data[i].NIT_CC,
                            response.data[i].nombre_lugar,
                            response.data[i].departamento,
                            response.data[i].localidad,
                            response.data[i].sector_operativo,
                            response.data[i].direccion,
                            response.data[i].consecutivo_ruta,
                            response.data[i].telefono,
                            response.data[i].medidor,
                            response.data[i].categoria,
                            response.data[i].unidad_operativa,
                            response.data[i].tipo_trabajo,
                            response.data[i].fecha_asignacion,
                            response.data[i].observacion_solicitud
                        ]);
                    }

                    callback({
                        draw: data.draw,
                        data: out,
                        recordsTotal: totalRecords,
                        recordsFiltered: totalRecords
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error al obtener los datos del servidor');
                }
            });
        },
        scrollY: 200,
        scroller: {
            loadingIndicator: true
        },
        stateSave: true
    });
}); */
/*     $(document).ready(function() {

        $('#table').DataTable({
        
            "scrollY": "400px",
            "scrollCollapse": true,
            "paging": false,

            "searching": false,
            "ordering": false,
            "processing": true, 
            "serverSide": true,
            "responsive": true, 
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
            
        });
    }); */
</script>
@endsection
@endsection