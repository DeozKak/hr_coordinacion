@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1>Coordinación</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/coordinacion.css')}}">

<div class="card">
    <div class="card-body">
        <div class="row">
            <table id="table" class="display nowrap table">
                <thead>
                    <tr>
                        <th colspan="18" style="text-align: center; background-color: #b7ffae;">1. ASIGNACIÓN BASE INICIO OSF</th>
                    </tr>
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
               
            </table>

        </div>
    </div>
</div>
@section('js')
<script>
    $(document).ready(function() {
        var table = $('#table').DataTable({
            scroller: {
                displayBuffer: 50,
            }, 
            scrollY: 420,
            scrollX: true,
            deferRender: true,
            scrollResize: true,
            scrollCollapse: true,
            processing: true,
            serverSide: true,
            responsive: true,
            fixedColumns: {leftColumns: 3},
            ajax: {
                url: "{{ route('getdataCoordinacion') }}",
                type: "GET"
            },
            "columns": [{
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
            "language": {
                "lengthMenu": "Mostrar _MENU_ registros por página",
                "zeroRecords": "Nada encontrado - lo siento",
                "infoEmpty": "No hay registros disponibles",
                "infoFiltered": "(Filtrado de _MAX_ registros totales)",
                "search": "Buscar:",
                "paginate": {
                    "next": "Siguiente",
                    "previous": "Anterior"
                }
            }
        });

    });
</script>
@endsection
@endsection