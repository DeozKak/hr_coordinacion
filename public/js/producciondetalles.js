
let hot;
let hot_dia;
let diasFestivos;
let sabadodobles = [];
document.addEventListener('DOMContentLoaded', async () => {
    document.getElementById('exportar').addEventListener('click', exportarExcel);


    Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
        Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
        let columNameColor = hotInstance.getColHeader(col);
        if (col !== 0 && col !== 1 && columNameColor !== 'META POR INSPECTOR' && columNameColor !== 'DIAS LABORADOS') {
            TD.style.backgroundColor = 'rgb(215, 232, 255)';
        }
        if (columNameColor === 'MATRICES' || columNameColor === 'DOMINGOS Y FESTIVOS' || columNameColor === 'DISEÑOS ESPECIALES'
            || columNameColor === '4 O MAS RECINTOS' || columNameColor === 'COMERCIALES') {
            TD.style.backgroundColor = 'rgb(253, 234, 185)';
        }
        if (columNameColor === 'TOTAL' || columNameColor === 'SUB TOTAL') {
            TD.style.backgroundColor = 'rgb(185, 196, 255)';
        }

        if (columNameColor === 'SUB TOTAL' && value < 180) {
            TD.style.backgroundColor = 'rgb(255, 185, 185)';
        }

        if (columNameColor === 'PROMEDIO INDIVIDUAL' && value >= 8) {
            TD.style.backgroundColor = 'rgb(147, 255, 134)';
        } else if (columNameColor === 'PROMEDIO INDIVIDUAL' && value < 8) {
            TD.style.backgroundColor = 'rgb(255, 185, 185)';
        }

        const columnName = hotInstance.getColHeader(col);
        const ccOperario = hotInstance.getDataAtCell(row, 0);
        if (diasFestivos.includes(columnName)) {
            TD.style.backgroundColor = 'rgb(147, 255, 134)';
        }
        // Verificar si el nombre de la columna y el ccOperario coinciden con los datos en 'resultados'
        sabadodobles.forEach(resultado => {
            if (resultado.nombreDia === columnName && resultado.ccInspector === ccOperario) {
                TD.style.backgroundColor = 'rgb(255, 240, 142)'; // Cambia el color según tus necesidades
            }
        });


    });

    const detalles = document.querySelector('#detalles');
    const url = document.querySelector('#id_produccion').value;
    let headers = [];
    let rows = [];
    let fechas = [];
    const fetchData = () => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'GET',
                success: function (response) {
                    resolve(response);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        type: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al cargar los datos de la base de datos'
                    });
                    reject(error);
                }
            });
        });
    };

    try {
        const response = await fetchData();

        // asociar las fechas con los nombres de los dias
        fechas = response.diasIntermedios.map((item, index) => {
            return {
                dia: `${item.nombreDia} ${item.dias}`,
                fecha: response.fechasIntermedias[index]
            };
        });

        //conversion de las fechas a nombres de dias para el resaltado de los festivos
        diasFestivos = response.diasFestivos.map(fecha => {
            let fechaObj = new Date(fecha + 'T00:00:00'); // Agregar la hora para evitar desplazamientos
            let options = { weekday: 'long', day: '2-digit' };
            let nombreDia = fechaObj.toLocaleDateString('es-ES', options);
            return nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1);
        });

        // datos para resaltar los sabados dobles
        response.sabadodobles.forEach(entry => {
            // Iterar a través de cada registro en el array de datos
            entry.datos.forEach(record => {
                let fechaObj = new Date(record.fecha + 'T00:00:00');
                let options = { weekday: 'long', day: '2-digit' };
                let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);
                // Guardar el resultado en el array
                sabadodobles.push({
                    nombreDia: nombreDiaS,
                    ccInspector: record.cc_inspector
                });
            });
        });

        rows = response.produccionInspector;
        // Extraer la propiedad nombreMes de cada objeto
        const nombresMes = response.diasIntermedios.map(item => item.nombreMes);
        // Obtener los nombres de mes únicos
        const nombresMesUnicos = [...new Set(nombresMes)];
        // Contar las repeticiones de cada mes
        const conteoRepeticiones = response.diasIntermedios.reduce((conteo, item) => {
            conteo[item.nombreMes] = (conteo[item.nombreMes] || 0) + 1;
            return conteo;
        }, {});

        // Organizar los resultados en el formato deseado
        resultados = nombresMesUnicos.map(nombreMes => ({
            label: nombreMes,
            colspan: conteoRepeticiones[nombreMes]
        }));

        const headerAdicional = { label: '', colspan: 2 };
        const headerFinal = { label: '', colspan: 7 };
        const headerDatosAdicionales = { label: '', colspan: 4 };
        headers = [headerAdicional, ...resultados.map(item => ({ label: item.label, colspan: item.colspan })), headerFinal, headerDatosAdicionales];
        const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
        const columnasFinales = ['SUB TOTAL', 'MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES', '4 O MAS RECINTOS',
            'COMERCIALES', 'TOTAL', 'DIAS LABORADOS', 'PROMEDIO INDIVIDUAL', 'META POR INSPECTOR', '% CUMPLIMIENTO META'];
        const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
        headers.push(datosDias);
        datosDias.unshift(...datosAdicionales);
        headers[5].push(...columnasFinales);

        hot = new Handsontable(detalles, {
            readOnly: false,
            manualColumnMove: false,
            rowHeaders: true,
            nestedHeaders: [headers, headers[5]],
            height: '650px',
            data: rows,
            autoWrapRow: true,
            autoWrapCol: true,
            fixedColumnsStart: 2,
            licenseKey: 'non-commercial-and-evaluation', // for non-commercial use only
            cells: function (row, col) {
                const cellProperties = {};
                cellProperties.renderer = 'customStylesRenderer';
                return cellProperties;
            },
            afterChange: function (changes, source) {
                if (source === 'edit') {
                    changes.forEach(([row, prop, oldValue, newValue]) => {
                        if (['DISEÑOS ESPECIALES'].includes(hot.getColHeader(hot.propToCol(prop)))) {
                            calculateAndSetTotal(row, hot.propToCol(prop));

                        }
                    });
                }
            },
            afterOnCellCornerDblClick: function (event) {

                const selectedColumn = hot.getSelectedLast()[1]; // Obtiene la última columna seleccionada
                const columnName = hot.getColHeader(selectedColumn);
                const isFechaColumn = fechas.some(fecha => fecha.dia === columnName);
                if (isFechaColumn) {
                    const selectedRow = hot.getSelectedLast()[0]; // Obtiene la última fila seleccionada
                    const rowData = hot.getDataAtCell(selectedRow, 0);
                    const nombre_completo = hot.getDataAtCell(selectedRow, 1); // Obtiene el valor de la celda en la columna 0 de la fila seleccionada
                    // recuperar fecha para la consulta contratos por dia
                    const fecha = fechas.find(fecha => fecha.dia === columnName);

                    $('#exampleModal').modal('show');
                    detallesDia(fecha.fecha, rowData, columnName, nombre_completo);
                }
            }
        });
    } catch (error) {
        console.error('Error fetching data:', error);
    }


});



function exportarExcel() {
    hot.getPlugin('exportFile').downloadFile('csv', {
        filename: 'produccion'
    });

}


function calculateAndSetTotal(row, indexColumn) {

    const colIndices = [indexColumn - 3, indexColumn - 2, indexColumn - 1, indexColumn, indexColumn + 1, indexColumn + 2];
    let sum = 0;
    console.log(colIndices);
    colIndices.forEach(col => {
        const cellValue = hot.getDataAtCell(row, col);
        sum += parseFloat(cellValue) || 0;
    });

    hot.setDataAtCell(row, indexColumn + 3, sum);
}

//---------------------------------------------------------------------------------------------------//
/* MODAL INSPECCIONES POR DIA */
async function detallesDia(fecha, cc_inspector, nombreDia, nombre_completo) {
    const token = document.querySelector('#token').value;
    let datosBaseDatos;
    let nomColumna;
    const contratos_dia = document.querySelector('#contratos_dia');
    const cerrar = document.querySelector('#cerrar_modal');
    const titulo = document.querySelector('#titulo');
    titulo.innerHTML = `INSPECCIONES DEL DÍA ${nombreDia} - ${nombre_completo}`;

    (Handsontable => {
        function customTextValidator(value, callback) {
            const valid = value !== '' && (value === 'NO' || !isNaN(Number(value)));
            callback(valid);
        }

        function customNumericValidator(value, callback) {
            const valid = !isNaN(Number(value)) && value !== null && value !== '';
            callback(valid);
        }
        // Registrar el validador personalizado
        Handsontable.validators.registerValidator('custom.numeric', customNumericValidator);
        // Register the custom validator
        Handsontable.validators.registerValidator('custom.text', customTextValidator);
    })(Handsontable);


    hot_dia = new Handsontable(contratos_dia, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: false,
        colHeaders: ['ID', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', '4 RECINTOS O MAS','ESTADO', 'ACCIONES'],
        columns: [
            { type: 'numeric', readOnly: true }, // ID (oculto)
            { type: 'text' }, // OPERARIO
            { type: 'numeric', validator: 'custom.numeric' }, // CC OPERARIO
            { type: 'text' }, // MUNICIPIO
            { type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true }, // FECHA
            { type: 'numeric', validator: 'custom.numeric' }, // N° ACTA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RP 10444', 'RP 12161', 'RN 12162', 'SA 12164', 'SA 12163'],
            }, // TIPO TRABAJO
            { type: 'numeric', validator: 'custom.numeric' }, // CONTRATO
            { type: 'numeric', validator: 'custom.numeric' }, // ORDEN TRABAJO
            { type: 'numeric', correctFormat: true }, // ORDEN EXT
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RESIDENCIAL', 'COMERCIAL'],
            }, // CATEGORIA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['.CERTIFICADA', 'CERTIFICADA CON NOVEDADES', '.INSPECCIONADA CON DEFECTO CRITICO VALLE', '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE'],
            }, // RESULTADO CIERRE
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // HORA INICIO
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // HORA FINAL
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // DURACION INSP
            { type: 'text', validator: 'custom.text', allowInvalid: false },
            {}, // las columnas existentes
            {
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    const estado = hot_dia.getDataAtRow(row)[16]; // Suponiendo que el estado está en la columna 15
                    let buttonHtml = '';
                    if (estado === 1) {
                        buttonHtml = '<button class="btn btn-danger" onclick="desasociar(' + row + ',\'' + fecha + '\',' + cc_inspector + ')">Desasociar</button>';
                    } else {
                        buttonHtml = '<button class="btn btn-success" onclick="asociar(' + row + ',\'' + fecha + '\',' + cc_inspector + ')">Asociar</button>';
                    }
                    td.innerHTML = `
                        <div style="display: flex; gap: 5px; justify-content: center;">
                        <button id="btnEditar" class="btn btn-info" onclick="editar(${row})">Editar</button>
                        ${buttonHtml}
                        </div>
                        `;
                    td.style.textAlign = 'center'; // Centrar los botones
                    return td;
                }
            }
        ],
        className: 'htCenter',
        className: 'htMiddle',
        manualRowResize: true,
        autoWrapRow: true,
        autoWrapCol: true,
        licenseKey: 'non-commercial-and-evaluation',
        height: '500px',
        hiddenColumns: {
            columns: [0], // Ocultar la columna del ID
            indicators: false // No mostrar indicadores de columnas ocultas
        },
        // Añadir el listener para afterChange
        afterChange: function (changes, source) {
            if (source === 'loadData') {
                return; // No enviar cambios cuando se carga la data inicial
            }

            changes.forEach(async ([row, prop, oldValue, newValue]) => {

                if (oldValue !== newValue) {
                    // Obtener el ID de la fila
                    const id = hot_dia.getDataAtRow(row);
                    //codigo para encontrar la columna

                    datosBaseDatos.forEach(objeto => {
                        Object.entries(objeto).forEach(([clave, valor]) => {
                            if (valor === oldValue) {
                                nomColumna = clave;
                            }
                        });
                    });
                    if (nomColumna === null) {
                        console.log(nomColumna + "nulo");
                    }
                    const payload = {
                        row: row,
                        prop: nomColumna,
                        oldValue: oldValue,
                        newValue: newValue
                    };
                    $.ajax({
                        url: `detalles_diario/actualizar/${id[0]}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
                        type: 'POST',
                        data: {
                            _token: token,
                            payload: payload
                        },
                        success: function (response) {
                            alert(response.message);
                            cargarDatos();
                        },
                        error: function (xhr, status, error) {
                            console.log(xhr.responseText);
                            Swal.fire({
                                type: 'error',
                                title: 'Error',
                                text: error
                            });
                        }
                    });
                }
            })
        }
    });

    $.ajax({
        url: `detalles_diario/${fecha}/${cc_inspector}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            datosBaseDatos = response;
            // Asigna los datos obtenidos a la variable
            const array2D = convertirJSONaArray2D(datosBaseDatos);
            hot_dia.loadData(array2D);
            if (array2D && array2D.length > 0) {
                document.getElementById('mensajeNoDatos').style.display = 'none';
            } else {
                document.getElementById('mensajeNoDatos').style.display = 'block';
            }

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

    cerrar.addEventListener('click', () => {
        try {
            hot_dia.destroy();
        } catch (e) { }
        $('#exampleModal').modal('hide');
    });



}

//---------------------------------------------------------------------------------------------------//

function editar(row) {
    hot_dia.updateSettings({
        cells: function (r, c) {
            const nonEditableColumns = [1, 2, 3, 12, 13, 14, 16];
            if (r === row && !nonEditableColumns.includes(c)) {// Si es la fila a editar y no es la columna de acciones
                return {
                    readOnly: false // Habilita la edición para esta fila
                };
            }
            return {};
        }
    });


}
function desasociar(row,fecha,cc_inspector) {
  
    const id = hot_dia.getDataAtRow(row);
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡Se descontará de producción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, desasociar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: `detalles_diario/desasociar/${id[0]}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'POST',
                data: {
                    _token: document.querySelector('#token').value
                },
                success: function (response) {
                    console.log(response);
                    Swal.fire(
                        'Desasociado!',
                        'El registro ha sido descontado.',
                        'success'
                    );
                    cargarDatos();
                    actualizarDatosDia(fecha,cc_inspector);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        type: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al descontar el registro'
                    });
                }
            });
        }
    });
}

function asociar(row,fecha,cc_inspector) {
    const id = hot_dia.getDataAtRow(row);
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡Se sumará a producción!",
        type: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, asociar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: `detalles_diario/desasociar/${id[0]}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'POST',
                data: {
                    _token: document.querySelector('#token').value
                },
                success: function (response) {
                    console.log(response);
                    Swal.fire(
                        'Asociado!',
                        'El registro ha sido sumado.',
                        'success'
                    );
                    cargarDatos();
                    actualizarDatosDia(fecha,cc_inspector);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        type: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al sumar el registro'
                    });
                }
            });
        }
    });

}

function actualizarDatosDia(fecha,cc_inspector) {
    $.ajax({
        url: `detalles_diario/${fecha}/${cc_inspector}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            datosBaseDatos = response;
            console.log(fecha,cc_inspector);
            // Asigna los datos obtenidos a la variable
            const array2D = convertirJSONaArray2D(datosBaseDatos);
            hot_dia.loadData(array2D);
            if (array2D && array2D.length > 0) {
                document.getElementById('mensajeNoDatos').style.display = 'none';
            } else {
                document.getElementById('mensajeNoDatos').style.display = 'block';
            }

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
}
async function cargarDatos() {
    const fetchData = () => {
        return new Promise((resolve, reject) => {
            const url = document.querySelector('#id_produccion').value;
            $.ajax({
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'GET',
                success: function (response) {
                    resolve(response);
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    Swal.fire({
                        type: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al cargar los datos de la base de datos'
                    });
                    reject(error);
                }
            });
        });
    };
    try {
        const response = await fetchData();

        // asociar las fechas con los nombres de los dias
        fechas = response.diasIntermedios.map((item, index) => {
            return {
                dia: `${item.nombreDia} ${item.dias}`,
                fecha: response.fechasIntermedias[index]
            };
        });

        //conversion de las fechas a nombres de dias para el resaltado de los festivos
        diasFestivos = response.diasFestivos.map(fecha => {
            let fechaObj = new Date(fecha + 'T00:00:00'); // Agregar la hora para evitar desplazamientos
            let options = { weekday: 'long', day: '2-digit' };
            let nombreDia = fechaObj.toLocaleDateString('es-ES', options);
            return nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1);
        });

        // datos para resaltar los sabados dobles
        response.sabadodobles.forEach(entry => {
            // Iterar a través de cada registro en el array de datos
            entry.datos.forEach(record => {
                let fechaObj = new Date(record.fecha + 'T00:00:00');
                let options = { weekday: 'long', day: '2-digit' };
                let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);
                // Guardar el resultado en el array
                sabadodobles.push({
                    nombreDia: nombreDiaS,
                    ccInspector: record.cc_inspector
                });
            });
        });

        rows = response.produccionInspector;
        // Extraer la propiedad nombreMes de cada objeto
        const nombresMes = response.diasIntermedios.map(item => item.nombreMes);
        // Obtener los nombres de mes únicos
        const nombresMesUnicos = [...new Set(nombresMes)];
        // Contar las repeticiones de cada mes
        const conteoRepeticiones = response.diasIntermedios.reduce((conteo, item) => {
            conteo[item.nombreMes] = (conteo[item.nombreMes] || 0) + 1;
            return conteo;
        }, {});

        // Organizar los resultados en el formato deseado
        resultados = nombresMesUnicos.map(nombreMes => ({
            label: nombreMes,
            colspan: conteoRepeticiones[nombreMes]
        }));

        const headerAdicional = { label: '', colspan: 2 };
        const headerFinal = { label: '', colspan: 7 };
        const headerDatosAdicionales = { label: '', colspan: 4 };
        headers = [headerAdicional, ...resultados.map(item => ({ label: item.label, colspan: item.colspan })), headerFinal, headerDatosAdicionales];
        const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
        const columnasFinales = ['SUB TOTAL', 'MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES', '4 O MAS RECINTOS',
            'COMERCIALES', 'TOTAL', 'DIAS LABORADOS', 'PROMEDIO INDIVIDUAL', 'META POR INSPECTOR', '% CUMPLIMIENTO META'];
        const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
        headers.push(datosDias);
        datosDias.unshift(...datosAdicionales);
        headers[5].push(...columnasFinales);
    } catch (error) {
        console.error('Error fetching data:', error);
    }

    hot.loadData(rows);

};

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['id', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS', 'state'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}