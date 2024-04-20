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
 <a id="btn" class="btn btn-success" >crear filas</a>
@section('js')
<script>
    
    const container = document.querySelector('#prueba');

    const hot = new Handsontable(container, {

        rowHeaders: true,
        fillHandle: false,
        height: '450px',
        allowRemoveColumn: false,
        customBorders: false,
        dropdownMenu: true,
        multiColumnSorting: false,
        filters: true,
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
    let registro = 100;
function cargaPrimeraVez() {
    const bottom = container.scrollTop + container.clientHeight >= container.scrollHeight;
    if (bottom) {
        $.ajax({
            url: "{{route('getdataCoordinacionRP')}}",
            data: { pagina: pagina},
            method: 'GET',
            success: function(response) {
                hot.loadData(response, null, 'json');
                
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
function cargarMasRegistros() {
    const bottom = container.scrollTop + container.clientHeight >= container.scrollHeight;
    if (bottom) {
        $.ajax({
            url: "{{route('getdataCoordinacionRP')}}",
            data: { pagina: pagina},
            method: 'GET',
            success: function(response) {
               
                insertarDatosEnFilas(response,registro);
                console.log(registro);
                pagina++
                registro += 100;
            },
            error: function(err) {
                console.log(err);
                console.error('Error al cargar más registros');
            }
        }); 
    }
}

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['orden', 'contrato', 'producto', 'numero_solicitud', 'tipo_solicitud', 'NIT_CC', 'nombre_lugar', 'departamento', 'localidad', 'sector_operativo', 'direccion', 'consecutivo_ruta', 'telefono', 'medidor', 'categoria', 'unidad_operativa', 'tipo_trabajo', 'fecha_asignacion', 'observacion_solicitud'];
    
    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}
function insertarDatosEnFilas(datos, filaInicial) {
    const array2D = convertirJSONaArray2D(datos);

    hot.populateFromArray(filaInicial, 0, array2D);
}
document.getElementById('btn').addEventListener('click', function() {
    cargarMasRegistros();
});
container.addEventListener('scroll', cargaPrimeraVez());


</script>

@endsection
@endsection