document.addEventListener('DOMContentLoaded', () => {
    Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
        Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
        
        if (value === 'SI') {
            TD.style.backgroundColor = 'rgb(253, 221, 140)';
        }

        if(value < '00:20'){
            TD.style.backgroundColor = 'rgb(253, 221, 140)';
        }

        if(value === 'COMERCIAL'){
            TD.style.backgroundColor = 'rgb(253, 221, 140)';
        }
    });
   
    const id_bitacora = document.querySelector('#id_bitacora').value;
    const container = document.querySelector('#tabla');
    const hot = new Handsontable(container, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: true,
        colHeaders: true,
        height: '550px',
        autoWrapRow: true,
        autoWrapCol: true,
        columns: [
            {},{},{},{},{},{},{},{},{},{renderer: 'customStylesRenderer'},{},{},{},{renderer: 'customStylesRenderer'},{ renderer: 'customStylesRenderer' } // Aplicar el renderer personalizado a todas las celdas de la columna
          ],
        filters: true,
        dropdownMenu: true, // Habilita los filtros en la tabla
        colHeaders: ['OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', '4 RECINTOS O MAS'],
        licenseKey: 'non-commercial-and-evaluation'
    });
    // Realizar una petición AJAX para obtener los datos de la base de datos
    $.ajax({
        url: id_bitacora, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            const datosBaseDatos = response.contratos; // Asigna los datos obtenidos a la variable
            const array2D = convertirJSONaArray2D(datosBaseDatos);
            hot.loadData(array2D);
            const columnData = hot.getDataAtCol(14);

        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            Swal.fire({
                type: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la base de datos'
            });
        }
    });



    // tabla indicadores

    const urlIndicadores = document.querySelector('#url_indicadores').value;
    const tablaIndicadores = document.querySelector('#indicadores');
    $.ajax({
        url: urlIndicadores, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            const hotIndicadores = new Handsontable(tablaIndicadores, {
                data: [
                    ['.CERTIFICADA', response.certificadas],
                    ['CERTIFICADA CON NOVEDADES', response.certificadasConNovedades],
                    ['.INSPECCIONADA CON DEFECTO CRITICO', response.inspeccionadasConDefectoCritico],
                    ['.INSPECCIONADA CON DEFECTO NO CRITICO', response.inspeccionadasConDefectoNoCritico],
                    ['TOTAL', response.totalContratosOK]
                ],
                readOnly: true,
                rowHeaders: false,
                colHeaders: false,
                autoWrapRow: true,
                autoWrapCol: true,
                licenseKey: 'non-commercial-and-evaluation' // for non-commercial use only
            });

        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            Swal.fire({
                type: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la base de datos'
            });
        }
    })

});

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}
