
let hot;
let hotIndicadores;
document.addEventListener('DOMContentLoaded', () => {

    function carga_datos() {
        Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
            Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
            const columnName = hotInstance.getColHeader(col); // Obtener el nombre de la columna
            if(value === 'PERIODO DE GRACIA'){
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i); // Obtener las propiedades de la celda
                    cellProperties.className = 'fila-gracia'; // Agregar la clase CSS a la celda
                }
            }

            if (value === '60 meses') {
                // Pintar toda la fila si la duración es 60 meses
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i); // Obtener las propiedades de la celda
                    cellProperties.className = 'fila-60-meses'; // Agregar la clase CSS a la celda
                }
            } else if (columnName === 'N° ACTA' && /^P/i.test(value)) {
                // Pintar toda la fila si el N° ACTA empieza con "P"
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i);
                    cellProperties.className = 'fila-acta-p'; // Nueva clase para filas N° ACTA
                }
            } else if (value === 'SI' || value < '00:20' || value === 'COMERCIAL') {
                cellProperties.className = 'celda-amarilla';
            }

            return cellProperties;
        });

        const id_bitacora = document.querySelector('#id_bitacora').value;
        const container = document.querySelector('#tabla');
        hot = new Handsontable(container, {
            language: 'es-MX',
            readOnly: true,
            manualColumnMove: false,
            rowHeaders: true,
            colHeaders: true,
            height: '550px',
            contextMenu: true, 
            autoWrapRow: true,
            autoWrapCol: true,
            manualRowResize: true,
            columns: [
                {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, { renderer: 'customStylesRenderer' }, { renderer: 'customStylesRenderer' }, {},
                { type: 'checkbox', checkedTemplate: true, uncheckedTemplate: false, readOnly: false },// Aplicar el renderer personalizado a todas las celdas de la columna
            ],
            filters: true,
            dropdownMenu: true, // Habilita los filtros en la tabla
            colHeaders: ['id', 'OBSERVACIÓN', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', 'Causal rechazo', ' '],
            licenseKey: 'non-commercial-and-evaluation',
            hiddenColumns: {
                columns: [0],
            },
            copyPaste: {
                copyColumnGroupHeaders: true,
                copyColumnHeaders: true,
            },
        },

        );
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

                if (hotIndicadores) {
                    // Destruir la instancia existente de Handsontable
                    hotIndicadores.destroy();
                }
        
                 hotIndicadores = new Handsontable(tablaIndicadores, {
                    data: [
                        ['CERTIFICADA', response.certificadas],
                        ['CERTIFICADA CON NOVEDADES', response.certificadasConNovedades],
                        ['INSPECCIONADA CON DEFECTO CRITICO', response.inspeccionadasConDefectoCritico],
                        ['INSPECCIONADA CON DEFECTO NO CRITICO', response.inspeccionadasConDefectoNoCritico],
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

        document.getElementById('devolucion').addEventListener('click', () => {
            const selectedValues = getValuesFromSelectedRows();
            const token = document.getElementById('token').value;
            let url_devolucion = document.getElementById('url_devolucion').value;

            if (selectedValues.length > 0) {
                const ids = selectedValues.map(row => row[0]);
                url_devolucion = url_devolucion.replace(':id', ids.join(','));
            } else {
                alert('No se han seleccionado contratos para devolver');
                return;
            }

            // Opciones del combobox (ajusta según tus necesidades)
            const opcionesCausales = causales.map(causal => ({
                value: causal.nom_causal,  // Usar el ID como valor
                text: causal.nom_causal // Usar el nombre como texto visible
            }));

            // Construir el HTML del select
            const selectHtml = `
            <select id="causalSelect" class="form-control">
                ${opcionesCausales.map(op => `<option value="${op.value}">${op.text}</option>`).join('')}
            </select>
        `;

            Swal.fire({
                title: 'Selecciona una causal de devolución',
                html: selectHtml,
                showCancelButton: true,
                confirmButtonText: 'Devolver',
                preConfirm: () => {
                    if (document.getElementById('causalSelect').value === '--SELECCIONE CAUSAL--') {
                        Swal.showValidationMessage('Por favor, selecciona una causal válida.');
                        return false; // Evitar que se cierre la alerta
                    }
                    return document.getElementById('causalSelect').value;
                    // Validar si se seleccionó la primera opción
                   
                }
            }).then((result) => {
                if (result.value) {
                    const causalSeleccionada = result.value;
                    $.ajax({
                        url: url_devolucion,
                        type: 'POST',
                        data: {
                            _token: token,
                            causal: causalSeleccionada, // Enviar el valor seleccionado
                        },
                        success: function (response) {
                          
                            Swal.fire({
                                type: 'success',
                                title: 'Devolución exitosa',
                                text: 'Los contratos seleccionados han sido devueltos correctamente'
                            });
                            carga_datos();
                        },
                        error: function (xhr, status, error) {
                            console.log(xhr.responseText);
                            Swal.fire({
                                type: 'error',
                                title: 'Error',
                                text: 'Ocurrió un error al devolver los contratos seleccionados ' + error
                            });
                        }
                    });
                }
            });
        });

    }

    carga_datos();
});


// Función para obtener los valores de las filas seleccionadas

function getValuesFromSelectedRows() {
    const selectedRows = [];
    hot.getData().forEach((row, rowIndex) => {

        if (hot.getDataAtCell(rowIndex, 17) === true) {
            const newRow = [...row]; // Crear una copia de la fila

            for (let columnIndex = 0; columnIndex < row.length; columnIndex++) {
                const cellProperties = hot.getCellMeta(rowIndex, columnIndex);
                if (cellProperties.type === "dropdown") {
                    newRow[columnIndex] = hot.getDataAtCell(rowIndex, columnIndex);
                    //cellProperties.source[cellProperties.instance.getSelectedIndex()];
                }
            }
            selectedRows.push(newRow);
        }
    });
    return selectedRows;
}

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['id', 'vence', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', 'CAUSAL_RECHAZO'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}



