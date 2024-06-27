
function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['orden', 'contrato', 'producto', 'numero_solicitud', 'tipo_solicitud', 'NIT_CC', 'nombre_lugar', 'departamento', 'localidad', 'sector_operativo', 'direccion', 'consecutivo_ruta', 'telefono', 'medidor', 'categoria', 'unidad_operativa', 'tipo_trabajo', 'fecha_asignacion', 'observacion_solicitud'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}

document.addEventListener('DOMContentLoaded', function () {
    let nestedHeaders = [
        [{
            label: 'ASIGNACION BASE OSF',
            colspan: 19
        }],
        ['Orden', 'Contrato', 'Producto', 'Numero solicitud', 'Tipo solicitud', 'Cedula', 'Nombre', 'Departamento', 'Localidad', 'Barrio', 'Dirección', 'Consecutivo Ruta', 'Telefono',
            'Medidor', 'Categoria', 'Unidad', 'Tipo trabajo', 'Fecha asignación', 'Observación solicitud'
        ]
    ];
    // selector del contenedor de la tabla
    const container = document.querySelector('#prueba');
    // configuración de la tabla y inicialización
    const hot = new Handsontable(container, {
        language: 'es-MX',
        readOnly: true,
        height: '650px',
        manualColumnMove: false,
        nestedHeaders: nestedHeaders,
        rowHeaders: true,
        colHeaders: true,
        filters: true,
        licenseKey: 'non-commercial-and-evaluation',
        fixedColumnsStart: 2,
        dropdownMenu: true,
    });

    $.ajax({
        url: url,
        method: 'GET',
        success: function (response) {
            const datos = convertirJSONaArray2D(response);
            hot.loadData(datos);
        }, error(xhr, status, error) {
            console.log(xhr.respobnseText);
        }
    });

});