@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1>Coordinación</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/coordinacion.css')}}">

<div class="card">
    <div class="card-body">
   

            <div id="prueba" style=" width: '100px'"></div>

 
    </div>
</div>
@section('js')
<script>
    
    const container = document.querySelector('#prueba');

    const hot = new Handsontable(container, {

        rowHeaders: true,
        
        height: '450px',
        allowRemoveColumn: false,
        customBorders: true,
        dropdownMenu: true,
        multiColumnSorting: true,
        filters: true,
        manualRowMove: true,
        colHeaders: ['Orden', 'Contrato', 'Producto', 'Numero solicitud', 'Tipo solicitud', 'Cedula', 'Nombre', 'Departamento', 'Localidad', 'Barrio', 'Dirección', 'Consecutivo Ruta', 'Telefono',
            'Medidor', 'Categoria', 'Unidad', 'Tipo trabajo', 'Fecha asignación', 'Observación solicitud'
        ],
        columns:[
            {data: 'orden'},
            {data: 'contrato'},
            {data: 'producto'},
            {data: 'numero_solicitud'},
            {data: 'tipo_solicitud'},
            {data: 'NIT_CC'},
            {data: 'nombre_lugar'},
            {data: 'departamento'},
            {data: 'localidad'},
            {data: 'sector_operativo'},
            {data: 'direccion'},
            {data: 'consecutivo_ruta'},
            {data: 'telefono'},
            {data: 'medidor'},
            {data: 'categoria'},
            {data: 'unidad_operativa'},
            {data: 'tipo_trabajo'},
            {data: 'fecha_asignacion'},
            {data: 'observacion_solicitud'}
        ],
        data: [],
        licenseKey: 'non-commercial-and-evaluation', // for non-commercial use only
    });
    let pagina = 1;

function cargarMasRegistros() {
    const bottom = container.scrollTop + container.clientHeight >= container.scrollHeight;

    if (bottom) {
        $.ajax({
            url: "{{route('getdataCoordinacionRP')}}",
            data: { pagina: pagina},
            method: 'GET',
            success: function(response) {
                console.log(response);
                hot.alter('insert_row', hot.countRows(), data.length);
                hot.populateFromArray(hot.countRows() - response.length, 0, response);
                pagina++;
            },
            error: function(err) {
                console.log(err);
                console.error('Error al cargar más registros');
            }
        });
    }
}

container.addEventListener('scroll', cargarMasRegistros());


</script>

@endsection
@endsection