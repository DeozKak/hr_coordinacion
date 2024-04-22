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
    // selector del contenedor de la tabla
    const container = document.querySelector('#prueba');
    // configuración de la tabla y inicialización
    const hot = new Handsontable(container, {
        lenguaje: 'es-MX',
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
        columns: [{
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
        data: [],
        licenseKey: 'non-commercial-and-evaluation',
    });
    // variables para la paginación
    let pagina = 1;
    let registro = 100;
    let paginasCargadas = 1;
    const paginasParaEliminar = 5;
    let scrollPosition = 0;
    // función para cargar los datos por primera vez
    function cargaPrimeraVez() {

        $.ajax({
            url: "{{route('getdataCoordinacionRP')}}",
            data: {
                pagina: pagina
            },
            method: 'GET',
            success: function(response) {
                hot.loadData(response, null, 'json');
                pagina++;
            },
            error: function(err) {
                console.log(err);
                console.error('Error al cargar más registros');
            }
        });

    }

     function eliminarRegistrosAnteriores() {

         
         if (paginasCargadas % paginasParaEliminar === 0 && paginasCargadas > 0) {
             isRemovingRows = true;
             
             const filasAEliminar = registro * (paginasCargadas - paginasParaEliminar);
            
             hot.alter('remove_row', 0, 100);
             wtHolderElement.scrollTop = scrollPosition;

         }
     }
   
    // función para cargar más registros
    function cargarMasRegistros() {
        const bottom = wtHolderElement.scrollTop + wtHolderElement.clientHeight >= wtHolderElement.scrollHeight;

        if (bottom) {
            $.ajax({
                url: "{{route('getdataCoordinacionRP')}}",
                data: {
                    pagina: pagina
                },
                method: 'GET',
                success: function(response) {

                    insertarDatosEnFilas(response, registro);
                    eliminarRegistrosAnteriores();


                    pagina++;
                    registro += 100;
                    paginasCargadas++;
                },
                error: function(err) {
                    console.log(err);
                    console.error('Error al cargar más registros');
                }
            });
        }
    }
    // función para convertir la respuesta del servidor de JSON a un array 2D
    // para que pueda ser insertado en la tabla
    function convertirJSONaArray2D(jsonData) {
        const columnasDeseadas = ['orden', 'contrato', 'producto', 'numero_solicitud', 'tipo_solicitud', 'NIT_CC', 'nombre_lugar', 'departamento', 'localidad', 'sector_operativo', 'direccion', 'consecutivo_ruta', 'telefono', 'medidor', 'categoria', 'unidad_operativa', 'tipo_trabajo', 'fecha_asignacion', 'observacion_solicitud'];

        return Object.keys(jsonData).map(key => {
            const fila = jsonData[key];
            return columnasDeseadas.map(columna => fila[columna]);
        });
    }

    // función para insertar los datos en las filas de la tabla
    function insertarDatosEnFilas(datos, filaInicial) {
        const array2D = convertirJSONaArray2D(datos);

        hot.populateFromArray(filaInicial, 0, array2D);
    }
    // elemento contenedor de la tabla que obtine el scroll
    const wtHolderElement = document.querySelector('.wtHolder');

    // cargar los datos por primera vez
    cargaPrimeraVez();

    // evento para cargar más registros al hacer scroll
    wtHolderElement.addEventListener('scroll', function() {
        scrollPosition = wtHolderElement.scrollTop;
        cargarMasRegistros();
    });

    hot.addHook('afterRender', function() {
        wtHolderElement.scrollTop = scrollPosition;
    });
</script>

@endsection
@endsection