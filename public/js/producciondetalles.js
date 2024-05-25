
let hot;
let hot_dia;
document.addEventListener('DOMContentLoaded', async () => {
    let diasFestivos;
    let sabadodobles = [];
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
function detallesDia(fecha, cc_inspector, nombreDia, nombre_completo) {
    const contratos_dia = document.querySelector('#contratos_dia');
    const cerrar = document.querySelector('#cerrar_modal');
    const titulo = document.querySelector('#titulo');
    titulo.innerHTML = `INSPECCIONES DEL DÍA ${nombreDia} - ${nombre_completo}`;




    hot_dia = new Handsontable(contratos_dia, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: false,
        colHeaders: ['OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', '4 RECINTOS O MAS', 'ACCIONES'],
        columns: [
            { type: 'text' }, // OPERARIO
            { type: 'numeric' }, // CC OPERARIO
            { type: 'text' }, // MUNICIPIO
            { type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true }, // FECHA
            { type: 'text' }, // N° ACTA
            { type: 'text' }, // TIPO TRABAJO
            { type: 'text' }, // CONTRATO
            { type: 'text' }, // ORDEN TRABAJO
            { type: 'text' }, // ORDEN EXT
            { type: 'text' }, // CATEGORIA
            { type: 'text' }, // RESULTADO CIERRE
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // HORA INICIO
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // HORA FINAL
            { type: 'time', timeFormat: 'HH:mm', correctFormat: true }, // DURACION INSP
            { type: 'text' }, // las columnas existentes
            {
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    td.innerHTML = `
                        <div style="display: flex; gap: 5px; justify-content: center;">
                        <button id="btnEditar" class="btn btn-info" onclick="editar(${row})">Editar</button>
                        <button class="btn btn-danger" onclick="desasociar(${row})">Desasociar</button>
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
        // Añadir el listener para afterChange
        afterChange: function (changes, source) {
            if (source === 'loadData') {
                return; // No enviar cambios cuando se carga la data inicial
            }

            changes.forEach(async ([row, prop, oldValue, newValue]) => {
                if (oldValue !== newValue) {
                    // Enviar cambios al servidor
                    const payload = {
                        row: row,
                        prop: prop,
                        oldValue: oldValue,
                        newValue: newValue
                    };
                    try {
                        const response = await fetch(`detalles_diario/actualizar/${id}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        });
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        const result = await response.json();
                        alert('Cambio guardado:', result);
                    } catch (error) {
                        alert('Error al enviar los cambios:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al enviar los cambios al servidor'
                        });
                    }
                }
            })
        }
    });

    $.ajax({
        url: `detalles_diario/${fecha}/${cc_inspector}`, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            const datosBaseDatos = response; // Asigna los datos obtenidos a la variable
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
            if (r === row && c !== 15) { // Si es la fila a editar y no es la columna de acciones
                return {
                    readOnly: false // Habilita la edición para esta fila
                };
            }
            return {};
        }
    });


}
function desasociar(row) {
    // Lógica para desasociar la fila
    console.log('Desasociar fila:', row);
}

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}