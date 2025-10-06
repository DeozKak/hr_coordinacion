let hot;
let hot_contadores = null;
let hot_dia = null;
let diasFestivos;
let sabadodobles = [];
let sabadosDoblesManualesTransformados = [];
let InspectorSelected;
let fechaSeleccionada;
let totalColspan = 0;
let cellBackgroundColor = "";
let cantInspecciones = 0;
let rowSelected;
let columnSelected;
let idCorteDetalles = null;
/* Inicializacion tabla de producción */
document.addEventListener('DOMContentLoaded', async () => {

    document.getElementById('exportar').addEventListener('click', exportarExcel);

    /*  $('#loader').show();
     $('#overlay').show(); */
    function cellStyle() {
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

            sabadosDoblesManualesTransformados.forEach(resultado => {
                if (resultado.nombreDia === columnName && resultado.ccInspector === ccOperario) {
                    TD.style.backgroundColor = 'rgb(255, 240, 142)'; // Cambia el color según tus necesidades
                }
            });
        });
    }

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
                    if (response.error) {
                        Swal.fire({
                            icon: 'warning',
                            text: response.error
                        });
                        $('#loader').hide();
                        $('#overlay').hide();
                        return;
                    }
                    resolve(response);
                    cellStyle()
                    $('#loader').hide();
                    $('#overlay').hide();
                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    Swal.fire({
                        icon: 'error',
                        title: error,
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
            let options = {weekday: 'long', day: '2-digit'};
            let nombreDia = fechaObj.toLocaleDateString('es-ES', options);
            return nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1);
        });
        const idCorteDetallesInput = document.getElementById('id_corte_detalles');
        idCorteDetallesInput.value = response.corte;

        // datos para resaltar los sabados dobles
        response.sabadodobles.forEach(entry => {
            // Iterar a través de cada registro en el array de datos
            entry.datos.forEach(record => {
                let fechaObj = new Date(record.fecha + 'T00:00:00');
                let options = {weekday: 'long', day: '2-digit'};
                let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);
                // Guardar el resultado en el array
                sabadodobles.push({
                    nombreDia: nombreDiaS,
                    ccInspector: record.cc_inspector
                });
            });
        });

        // Validar que sabadosDoblesManuales existe y es un array
        let datos
        response.sabadosDoblesManuales?.forEach(item => {
            datos = item;
        });

        if (datos !== undefined) {
            datos.forEach(items => {
                if (items?.datos?.totalInspecciones?.length > 0) {
                    items.datos.totalInspecciones.forEach(inspeccion => {
                        if (inspeccion?.fecha) {
                            let fechaObj = new Date(inspeccion.fecha + 'T00:00:00');
                            let options = {weekday: 'long', day: '2-digit'};
                            let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                            nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);

                            sabadosDoblesManualesTransformados.push({
                                nombreDia: nombreDiaS,
                                ccInspector: items.datos.cc_inspector
                            });
                        }
                    });
                }
            })
        }
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

        for (const col of resultados) {
            totalColspan += col.colspan;
        }

        const headerAdicional = {label: '', colspan: 2};
        const headerFinal = {label: '', colspan: 7};
        const headerDatosAdicionales = {label: '', colspan: 5};
        headers = [headerAdicional, ...resultados.map(item => ({
            label: item.label,
            colspan: item.colspan
        })), headerFinal, headerDatosAdicionales];
        const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
        const columnasFinales = ['SUB TOTAL', 'MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES', '4 O MAS RECINTOS',
            'COMERCIALES', 'TOTAL', 'RN TOTAL', 'DIAS LABORADOS', 'PROMEDIO INDIVIDUAL', 'META POR INSPECTOR', '% CUMPLIMIENTO META'];
        const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
        headers.push(datosDias);
        datosDias.unshift(...datosAdicionales);

        const lastIndex = headers.length - 1;
        headers[lastIndex].push(...columnasFinales);

        // headers[5].push(...columnasFinales);

        hot = new Handsontable(detalles, {
            readOnly: true,
            manualColumnMove: false,
            rowHeaders: true,
            nestedHeaders: [headers, headers[lastIndex]],
           /* height: '650px',*/
            data: rows,
            dropdownMenu: true,
            filters: true,
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
                InspectorSelected = hot.getSelectedLast();
                const selectedColumn = hot.getSelectedLast()[1]; // Obtiene la última columna seleccionada
                const columnName = hot.getColHeader(selectedColumn);
                const isFechaColumn = fechas.some(fecha => fecha.dia === columnName);

                if (isFechaColumn) {
                    const selectedRow = hot.getSelectedLast()[0]; // Obtiene la última fila seleccionada
                    const selectedColumn = hot.getSelectedLast()[1];
                    rowSelected = selectedRow;
                    columnSelected = selectedColumn;
                    const rowData = hot.getDataAtCell(selectedRow, 0);
                    const nombre_completo = hot.getDataAtCell(selectedRow, 1); // Obtiene el valor de la celda en la columna 0 de la fila seleccionada
                    let cellElement = hot.getCell(selectedRow, selectedColumn);
                    let valueCell = hot.getDataAtCell(selectedRow, selectedColumn);
                    cellBackgroundColor = window.getComputedStyle(cellElement).backgroundColor;
                    cantInspecciones = valueCell;

                    if (isFechaColumn) {
                        // recuperar fecha para la consulta contratos por dia
                        const fecha = fechas.find(fecha => fecha.dia === columnName);
                        fechaSeleccionada = fecha.fecha;
                        $('#exampleModal').modal({
                            show: true, // Mostrar el modal
                            focus: false // Deshabilitar el autoenfoque
                        });
                        detallesDia(fecha.fecha, rowData, columnName, nombre_completo);
                    }
                }
            }
        });
    } catch (error) {
        console.error('Error fetching data:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Error en la insersion de datos en tabla local',
        });
        $('#loader').hide();
        $('#overlay').hide();
    }
    // Insertar fila para promedios
    hot.setDataAtCell(hot.countRows(), 1, 'TOTAL');
    // Insertar fila para sumas (arriba de la de promedios)
    hot.setDataAtCell(hot.countRows(), 1, 'PROMEDIO');
    // Calcular promedios (considerando celdas vacías)
    const promedios = []; // Array para guardar los promedios
    const filaPromedios = hot.countRows() - 2; // Penúltima fila (promedios)
    totalColspan = totalColspan + 10;
    colProm = totalColspan;
    // Calcular promedios (considerando celdas vacías)
    for (let i = 0; i < colProm; i++) {
        hot.updateSettings({
            columnSummary: [
                {
                    destinationRow: hot.countRows() - 1, // Penúltima fila (promedios)
                    destinationColumn: i + 2,
                    sourceColumn: i + 2,
                    type: 'custom',
                    customFunction: function (endpoint) {
                        let sum = 0;
                        let count = 0;

                        // Obtener el rango de filas
                        const fromRow = endpoint.ranges[0][0]; // Fila inicial
                        const toRow = endpoint.ranges[0][1];   // Fila final

                        for (let j = fromRow; j <= toRow; j++) {
                            let value = hot.getDataAtCell(j, i + 2);
                            if (!isNaN(value) && value !== null && value !== '') {
                                sum += parseFloat(value);
                                count++;
                            }
                        }
                        let promedio = count > 0 ? (sum / count).toFixed(2) : '';
                        promedios.push(parseFloat(promedio) || 0);
                        return promedio;
                    }
                }
            ]
        });
    }

    // Calcular sumas
    for (let i = 0; i < totalColspan; i++) {
        hot.updateSettings({
            columnSummary: [
                {
                    destinationRow: hot.countRows() - 2, // Última fila (sumas)
                    destinationColumn: i + 2,
                    sourceColumn: i + 2,
                    type: 'sum'
                }
            ]
        });
    }

    hot.updateSettings({
        colWidths: [100, 300], // ajustar ancho de las primeras dos columnas
    });

    $(document).on('click', '#noContar, #noContarDoblesFestivos', function () {
        let dataUrl;
        let ccInspector = hot_dia.getDataAtRow(0, 2)[3]
        let fecha = hot_dia.getDataAtRow(0, 4)[5]

        if (cellBackgroundColor == "rgb(147, 255, 134)") {
            dataUrl = urlGuardarNoDoblesFestivos
        } else {
            dataUrl = urlNoContarDobles
        }
        let token = $('#token').val()
        let nombreInspector = hot_dia.getDataAtRow(0, 1)[2]

        $.ajax({
            url: dataUrl,
            type: 'POST',
            data: {
                ccInspector: ccInspector,
                fecha: fecha,
                _token: token,
            },
            success: function (response) {
                // Crear un objeto Date a partir de la fecha en formato YYYY-MM-DD+


                let fechaTransformar = new Date(fecha);

                // Array para traducir los días de la semana al español
                let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                // Obtener el día de la semana y el día del mes
                let diaSemanaEsp = diasSemana[fechaTransformar.getUTCDay()];
                let diaMes = fechaTransformar.getUTCDate();

                // Asegurarse de que el día del mes tenga dos dígitos
                let diaMesFormateado = String(diaMes).padStart(2, '0');

                // Formatear la fecha en el nuevo formato deseado
                let fechaFormateada = `${diaSemanaEsp} ${diaMesFormateado}`;

                if (!response.success) {
                    sabadodobles = sabadodobles.filter(element =>
                        !(element.nombreDia === fechaFormateada && element.ccInspector === ccInspector)
                    );
                }
                cellStyle();
                cargarDatos(idCorteDetalles);

                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Día doble descontado para el inspector: " + nombreInspector + " día: " + fechaFormateada,
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });

                $('#exampleModal').modal('hide');


            }
        })
    })

    $(document).on('click', '#noContarDoblesSabado', function () {
        let ccInspector = hot_dia.getDataAtRow(0, 2)[3]
        let fecha = hot_dia.getDataAtRow(0, 4)[5]
        let dataUrl = $(this).attr('data-url')
        let token = $('#token').val()
        let nombreInspector = hot_dia.getDataAtRow(0, 1)[2]

        $.ajax({
            url: dataUrl,
            type: 'POST',
            data: {
                ccInspector: ccInspector,
                fecha: fecha,
                _token: token,
            },
            success: function (response) {
                // Crear un objeto Date a partir de la fecha en formato YYYY-MM-DD+
                let fechaTransformar = new Date(fecha);

                // Array para traducir los días de la semana al español
                let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                // Obtener el día de la semana y el día del mes
                let diaSemanaEsp = diasSemana[fechaTransformar.getUTCDay()];
                let diaMes = fechaTransformar.getUTCDate();

                // Asegurarse de que el día del mes tenga dos dígitos
                let diaMesFormateado = String(diaMes).padStart(2, '0');

                // Formatear la fecha en el nuevo formato deseado
                let fechaFormateada = `${diaSemanaEsp} ${diaMesFormateado}`;

                sabadodobles = sabadodobles.filter(element =>
                    !(element.nombreDia === fechaFormateada && element.ccInspector === ccInspector)
                );

                cellStyle();
                cargarDatos(idCorteDetalles);

                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Día doble descontado para el inspector: " + nombreInspector + " día: " + fechaFormateada,
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });

                $('#exampleModal').modal('hide');

            }
        })
    })

    $(document).on('click', '#contarDobles, #contarDoblesFestivos', function () {
        let ccInspector = hot_dia.getDataAtRow(0, 2)[3]
        let fecha = hot_dia.getDataAtRow(0, 4)[5]
        let dataUrl = $(this).attr('data-url')
        let token = $('#token').val()
        let nombreInspector = hot_dia.getDataAtRow(0, 1)[2]

        $.ajax({
            url: dataUrl,
            type: 'POST',
            data: {
                ccInspector: ccInspector,
                fecha: fecha,
                _token: token,
            },
            success: function (response) {


                let fechaTransformar = new Date(fecha);

                // Array para traducir los días de la semana al español
                let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                // Obtener el día de la semana y el día del mes
                let diaSemanaEsp = diasSemana[fechaTransformar.getUTCDay()];
                let diaMes = fechaTransformar.getUTCDate();

                // Asegurarse de que el día del mes tenga dos dígitos
                let diaMesFormateado = String(diaMes).padStart(2, '0');

                // Formatear la fecha en el nuevo formato deseado
                let fechaFormateada = `${diaSemanaEsp} ${diaMesFormateado}`;

                cellStyle();
                cargarDatos(idCorteDetalles);

                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Día doble contado para el inspector : " + nombreInspector + " día: " + fechaFormateada,
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });

                $('#exampleModal').modal('hide');


            }
        })
    })

    $(document).on('click', '#abrirModalContarDoblesSabado', function () {
        $('.inspeccionesTotales').text(' max (' + cantInspecciones + ')')
        $('#contarSabado').val('')
        $('#modalContarDoblesSabado').modal({
            show: true,
            focus: false
        })
        $('#btn_modal_cerrar').click(function () {
            $('#modalContarDoblesSabado').modal('hide')
        })


    })

    $(document).on('click', '.btnGuardarContarSabado', function () {
        let ccInspector = hot_dia.getDataAtRow(0, 2)[3]
        let fecha = hot_dia.getDataAtRow(0, 4)[5]
        let url = $(this).attr('data-url')
        let token = $('#token').val()
        let nombreInspector = hot_dia.getDataAtRow(0, 1)[2]
        let diasContados = $('#contarSabado').val()

        if (!diasContados || diasContados === '0' || diasContados === '' || diasContados === undefined) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Coloque al menos una inspeccion doble para contar'
            });
            return;
        }
        if (diasContados > cantInspecciones) {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La cantidad de inspecciones a contar no puede ser mayor a la cantidad de inspecciones totales'
            });
        } else {
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    ccInspector: ccInspector,
                    fecha: fecha,
                    diasContados: diasContados,
                    _token: token,
                },
                success: function (response) {

                    $('#modalContarDoblesSabado').modal('hide')
                    $('#exampleModal').modal('hide');

                    let fechaTransformar = new Date(fecha);

                    // Array para traducir los días de la semana al español
                    let diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];

                    // Obtener el día de la semana y el día del mes
                    let diaSemanaEsp = diasSemana[fechaTransformar.getUTCDay()];
                    let diaMes = fechaTransformar.getUTCDate();

                    // Asegurarse de que el día del mes tenga dos dígitos
                    let diaMesFormateado = String(diaMes).padStart(2, '0');

                    // Formatear la fecha en el nuevo formato deseado
                    let fechaFormateada = `${diaSemanaEsp} ${diaMesFormateado}`;

                    sabadodobles.push({
                        nombreDia: fechaFormateada,
                        ccInspector: ccInspector
                    });

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Día doble contado para el inspector: " + nombreInspector + " día: " + fechaFormateada,
                        showConfirmButton: false,
                        toast: true,
                        timer: 4000
                    });

                    cellStyle();
                    cargarDatos(idCorteDetalles);
                }
            })
        }
    })

    $(document).on('input', '.inputNumeric', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    })

    $('#modalContarDoblesSabado, #ventanaEmergente').on('hidden.bs.modal', function () {
        $('body').addClass('modal-open');
    });

});

//---------------------------------------------------------------------------------------------------//

function exportarExcel() {
    hot.getPlugin('exportFile').downloadFile('csv', {
        filename: 'produccion'
    });

}

function calculateAndSetTotal(row, indexColumn) {

    const colIndices = [indexColumn - 3, indexColumn - 2, indexColumn - 1, indexColumn, indexColumn + 1, indexColumn + 2];
    let sum = 0;

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
    const fecha_inicioCorte = document.querySelector('#fecha_inicio').value;
    /* variable de rutas sale de la vista */
    const response = await fetch(urlObtenerDetalles + `?fecha=${fecha}&cc_inspector=${cc_inspector}`);
    const urlDetalles = await response.text();
    // const contadores_dia = document.querySelector('#contadores_dia');
    const contratos_dia = document.querySelector('#contratos_dia');
    const cerrar = document.querySelector('#cerrar_modal');
    const titulo = document.querySelector('#titulo');
    titulo.innerHTML = `INSPECCIONES DEL DÍA ${nombreDia} - ${nombre_completo}`;

    Handsontable.renderers.registerRenderer('customStylesRendererdays', (hotInstance, TD, row, col, prop, value, cellProperties) => {
        Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
        const columnName = hotInstance.getColHeader(col); // Obtener el nombre de la columna


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
    /*  if (hot_contadores && !hot_contadores.isDestroyed) {
          hot_contadores.destroy();
          hot_contadores = null;
      }*/
    if (hot_dia && !hot_dia.isDestroyed) {
        hot_dia.destroy();
        hot_dia = null;
    }

    /* hot_contadores = new Handsontable(contadores_dia, {
         readOnly: true,
         manualColumnMove: false,
         rowHeaders: false,
         colHeaders: false,
         height: '150px',
         licenseKey: 'non-commercial-and-evaluation',
     });*/

    hot_dia = new Handsontable(contratos_dia, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: false,
        colHeaders: ['ID', 'vence', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', '4 RECINTOS O MAS', 'ESTADO', 'ACCIONES'],
        columns: [
            {type: 'numeric', readOnly: true}, // ID (oculto)
            {renderer: 'customStylesRendererdays'}, // VENCE
            {type: 'text'}, // OPERARIO
            {type: 'numeric', validator: 'custom.numeric'}, // CC OPERARIO
            {type: 'text'}, // MUNICIPIO
            {
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
                datePickerConfig: {
                    minDate: new Date(fecha_inicioCorte), // Esto establece la fecha mínima como el día de inicio del corte
                    maxDate: new Date(new Date().getTime() - (24 * 60 * 60 * 1000)) // Esto establece la fecha máxima como el día actual
                }
            },// Usa el editor personalizado, // FECHA
            {type: 'numeric', validator: 'custom.numeric', renderer: 'customStylesRendererdays'}, // N° ACTA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RP 10444', 'RP 12161', 'RN 12162', 'SA 12164', 'SA 12163'],
            }, // TIPO TRABAJO
            {type: 'numeric', validator: 'custom.numeric'}, // CONTRATO
            {type: 'numeric', validator: 'custom.numeric'}, // ORDEN TRABAJO
            {type: 'numeric', correctFormat: true}, // ORDEN EXT
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RESIDENCIAL', 'COMERCIAL'],
            }, // CATEGORIA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['CERTIFICADA', 'CERTIFICADA CON NOVEDADES', 'INSPECCIONADA CON DEFECTO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'],
            }, // RESULTADO CIERRE
            {type: 'time', timeFormat: 'HH:mm', correctFormat: true}, // HORA INICIO
            {type: 'time', timeFormat: 'HH:mm', correctFormat: true}, // HORA FINAL
            {type: 'time', timeFormat: 'HH:mm', correctFormat: true, renderer: 'customStylesRendererdays'}, // DURACION INSP
            {type: 'text', validator: 'custom.text', allowInvalid: false},
            {}, // las columnas existentes
            {
                renderer: function (instance, td, row, col, prop, value, cellProperties) {

                    const estado = instance.getDataAtRow(row)[17];
                    const diseno_especial = instance.getDataAtRow(row)[18];


                    let buttonHtml = '';
                    let buttondiseno = '';
                    if (permission === 1) {
                        if (estado === 1) {
                            // Botón "Descontar" ahora usa el gradiente rojo
                            buttonHtml = '<button class="btn-gradient btn-gradient-danger btn-sm" onclick="desasociar(' + row + ',\'' + fecha + '\',' + cc_inspector + ')">Descontar</button>';
                        } else {
                            // Botón "Contar" ahora usa el gradiente verde
                            buttonHtml = '<button class="btn-gradient btn-gradient-success btn-sm" onclick="asociar(' + row + ',\'' + fecha + '\',' + cc_inspector + ')">Contar</button>';
                        }

                        if (diseno_especial === 1) {
                            // Botón "Quitar Diseño especial" ahora usa el gradiente naranja/amarillo
                            buttondiseno = '<button class="btn-gradient btn-gradient-warning btn-sm" onclick="diseñoEspecial(' + row + ',\'' + fecha + '\',' + cc_inspector + ',' + diseno_especial + ')">Quitar Diseño especial</button>';
                        } else {
                            // Botón "Diseño especial" también usa el gradiente naranja/amarillo
                            buttondiseno = '<button class="btn-gradient btn-gradient-warning btn-sm" onclick="diseñoEspecial(' + row + ',\'' + fecha + '\',' + cc_inspector + ',' + diseno_especial + ')">Diseño especial</button>';
                        }
                        // 'td' es la celda de la tabla
                        td.className = 'actions-cell';
                        // Centramos los botones y les damos espacio
                        td.innerHTML = `
                            <div class="action-buttons-container">
                                <button id="btnEditar" class="btn-gradient btn-gradient-info btn-sm" onclick="editar(${row})">Editar</button>
                                ${buttondiseno}
                                ${buttonHtml}
                            </div>
                            `;

                        return td;
                    }
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

                    }
                    const payload = {
                        row: row,
                        prop: nomColumna,
                        oldValue: oldValue,
                        newValue: newValue
                    };
                    const urlCompleta = urlActualizarDetallesDiario.replace(':id', id[0]);
                    $.ajax({
                        url: urlCompleta, // Ruta al archivo PHP que realiza la consulta a la base de datos
                        type: 'POST',
                        data: {
                            _token: token,
                            payload: payload
                        },
                        success: function (response) {
                            const idCorteDetallesInput = document.querySelector('#id_corte_detalles');
                            if (idCorteDetallesInput) { // Verificar si el elemento existe
                                cargarDatos(idCorteDetallesInput.value);
                            } else {
                                cargarDatos();
                            }

                        },
                        error: function (xhr, status, error) {

                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: error
                            });
                        }
                    });
                }
            })
        }
    });

    /* consulta llenar tabla */

    $.ajax({
        url: urlDetalles, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            //hot_contadores.loadData(response[5]);
            datosBaseDatos = response[0];
            console.log(response);
            const url = window.location.href
            const array = url.split("/");
            const last = array[array.length - 1]
            //se llena esta variable para las consultas posteriores a los cambios de los dobles
            idCorteDetalles = last;
            $('#cantidadDobles').text('');
            //if(last == 'detalles'){

            $('#noContar, #noContarDoblesFestivos, #contarDobles, #contarDoblesFestivos, #abrirModalContarDoblesSabado, #noContarDoblesSabado').remove();

            // para saber el color de la celda
            const selectedRow = rowSelected;
            const selectedColumn = columnSelected;
            const cellElement = hot.getCell(selectedRow, selectedColumn);
            const dataText = hot.getDataAtCell(selectedRow, selectedColumn);
            const cellBackgroundColor = window.getComputedStyle(cellElement).backgroundColor;
            const columnName = hot.getColHeader(selectedColumn).split(' ')[0];
            // ----------------------
            // validamos si la celda es de color verde
            if (dataText != "") {
                if (cellBackgroundColor == "rgb(147, 255, 134)" && !response[2]) {
                    if (!$('#noContarDoblesFestivos').length) {

                        let botonNoContarDobles = "<button type='button' data-url='" + urlGuardarNoDoblesFestivos + "' class='btn-base btn-gradient btn-gradient-secondary' id='noContarDoblesFestivos'>No contar dobles</button>";
                        $('#agregar').before(botonNoContarDobles);
                    }
                } else if (cellBackgroundColor == "rgb(147, 255, 134)" && response[2]) {
                    if (!$('#contarDoblesFestivos').length) {

                        let botonContarDobles = "<button type='button' data-url='" + urlCountDoublesHolidays + "' class='btn-base btn-gradient btn-gradient-secondary' id='contarDoblesFestivos'>Contar dobles</button>";
                        $('#agregar').before(botonContarDobles);
                    }
                }

                if (columnName == "Sábado") {
                    if (response[3].length == 0 && !response[2] && !response[1] && cellBackgroundColor == "rgb(255, 240, 142)") {
                        if (!$('#noContar').length) { // Solo agregar si el botón no existe ya

                            let botonNoContarDobles = "<button type='button' data-url='" + urlNoContarDobles + "' class='btn-base btn-gradient btn-gradient-secondary' id='noContar'>No contar dobles</button>"
                            $('#agregar').before(botonNoContarDobles)
                        }
                    } else if (response[3].length == 0 && !response[2] && !response[1] && cellBackgroundColor != "rgb(255, 240, 142)") {
                        if (!$('#abrirModalContarDoblesSabado').length) { // Solo agregar si el botón no existe yaconsole.log('sabado');

                            let botonContarDoblesSabados = "<button type='button' class='btn-base btn-gradient btn-gradient-secondary' id='abrirModalContarDoblesSabado'>Contar dobles</button>"
                            $('#agregar').before(botonContarDoblesSabados)
                        }
                    } else if (response[1] && !response[2] && response[3].length == 0 && cellBackgroundColor == "rgb(215, 232, 255)") {
                        if (!$('#contarDobles').length) { // Solo agregar si el botón no existe yaconsole.log('sabado');

                            let botonContarDobles = "<button type='button' data-url='" + urlContarDobles + "' class='btn-base btn-gradient btn-gradient-secondary' id='abrirModalContarDoblesSabado'>Contar dobles</button>"
                            $('#agregar').before(botonContarDobles);
                        }
                    } else if (response[3].length != 0 && !response[1] && !response[2] && cellBackgroundColor == "rgb(255, 240, 142)") {
                        console.log('sabado');
                        if (!$('#noContarDoblesSabado').length) { // Solo agregar si el botón no existe ya
                            $('#cantidadDobles').text('(cantidad de inspecciones dobles: ' + response[3][0][0] + ')')


                            let botonNoContarDoblesSabados = "<button type='button' data-url='" + urlNoContarDoblesSabados + "' class='btn-base btn-gradient btn-gradient-secondary' id='noContarDoblesSabado'>No contar dobles</button>"
                            $('#agregar').before(botonNoContarDoblesSabados);
                        }
                    }
                }

                if (response[4] !== 0) {
                    $('#cantidadDobles').text('(cantidad de inspecciones dobles: ' + response[4] + ')')
                }

                if (cellBackgroundColor == "rgb(147, 255, 134)") {
                    if ($('#contarDoblesFestivos').text() !== 'Contar dobles') {
                        if (cantInspecciones != 0) {
                            $('#cantidadDobles').text('(cantidad de inspecciones dobles: ' + cantInspecciones + ')')
                        }
                    }

                }
            } else {
                // Elimina ambos botones si no se cumplen las condiciones
                $('#noContar, #noContarDoblesFestivos, #contarDobles, #contarDoblesFestivos, #abrirModalContarDoblesSabado, #noContarDoblesSabado').remove();
            }
            //}

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
            console.log(xhr.responseText);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la base de datos'
            });
        }
    });

    const fechaconvert = formatearFecha(fecha);
    /* Variable sale de la vista Blade */
    const responseBitacoras = await fetch(urlObtenerBitacoras + `?fecha=${fechaconvert}&cc_inspector=${cc_inspector}`);
    const urlBitacoras = await responseBitacoras.text();

    /* consultar si ya hay bitacora */
    $.ajax({
        url: urlBitacoras, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            if (response.error) {
                const agregar = document.querySelector('#agregar');
                const agregarClon = agregar.cloneNode(true); // Clonar el elemento y sus hijos

                agregar.parentNode.replaceChild(agregarClon, agregar);
                agregarClon.disabled = true;

            } else {
                document.getElementById('agregar').disabled = false;
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un al consultar bitacoras'
            });
        }
    });
    cerrar.addEventListener('click', () => {
        try {
            hot_dia.destroy();
        } catch (e) {
        }
        $('#exampleModal').modal('hide');
    });

    const agregar = document.querySelector('#agregar');
    /* const ventanaEmergente = new bootstrap.Modal(document.getElementById('ventanaEmergente'), {
        focus: false // Deshabilitar el enfoque automático
    }); */

    agregar.addEventListener('click', () => {
        const fechaInput = document.getElementById('fecha');
        const fechaPredefinida = new Date(fechaSeleccionada);
        // Formatear la fecha predefinida según el formato de fecha requerido por el elemento de entrada de fecha (yyyy-mm-dd)
        const formattedDate = fechaPredefinida.toISOString().slice(0, 10);

        // Establecer la fecha predefinida en el campo de entrada de fecha
        fechaInput.value = formattedDate;
        const nombreInspector = hot.getDataAtCell(InspectorSelected[0], 1);
        const ccInspector = hot.getDataAtCell(InspectorSelected[0], 0);
        const selectNombre = document.querySelector('#nombre');
        const option1 = document.createElement('option');
        option1.value = ccInspector;
        option1.text = nombreInspector;
        selectNombre.appendChild(option1);
        $('#ventanaEmergente').modal({
            show: true, // Mostrar el modal
            focus: false // Deshabilitar el autoenfoque
        });
        /* ventanaEmergente.show(); */
        document.getElementById('exampleModal').classList.add('modal-backdrop-custom');
    });

    $('#ventanaEmergente').on('hidden.bs.modal', () => {
        document.getElementById('exampleModal').classList.remove('modal-backdrop-custom');
        const selectNombre = document.getElementById('nombre');
        selectNombre.innerHTML = ''; // Limpiar las opciones
        // Deshabilitar el select
    });
    //---------------------------------------------------------------------------------------------------//
    /////////////////////////////////////////////////
    ////////////////////////////////////////////////
    //* validacion de campos agregar inspección *///
    ////////////////////////////////////////////////
    ////////////////////////////////////////////////

    // Función para validar los datos ingresados en la tabla
    const inputrecintos = document.querySelectorAll('#NroRecintos');

    // Permitir solo números
    inputrecintos.forEach(input => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
        });
    });
    //--------------------------------------------------------------------------------

    const inputrecintosP = document.getElementById('NroRecintosP');

    inputrecintosP.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
    });

    const selectrecintos = document.getElementById('recintos');

    selectrecintos.addEventListener('change', function () {

        if (this.value === 'SI') {
            inputrecintosP.disabled = false; // Habilitar el campo "NroRecintos"
        } else {
            inputrecintosP.disabled = true;
            inputrecintosP.value = ""; // Deshabilitar el campo "NroRecintos"
        }
    });

    // limitar fechas en el campo fecha
    const inputFecha = document.getElementById('fecha');

    // Obtener la fecha actual
    const fechaActual = new Date();

    // Restar 7 días a la fecha actual
    let fechaMinima = new Date(fechaActual);
    fechaMinima.setDate(fechaActual.getDate() - 7);

    // Formatear la fecha mínima para establecerla en el campo de fecha
    const dia = ("0" + fechaMinima.getDate()).slice(-2);
    const mes = ("0" + (fechaMinima.getMonth() + 1)).slice(-2);
    const fechaFormateada = fechaMinima.getFullYear() + "-" + mes + "-" + dia;

    // Establecer la fecha mínima en el campo de fecha
    inputFecha.min = fechaFormateada;
    inputFecha.setAttribute('placeholder', 'dd-mm-yy');
    //--------------------------------------------------------------------------------
    // campo numero de acta
    const inputNumero = document.getElementById('N°acta');

    // Permitir solo números
    inputNumero.addEventListener('input', function () {
        // Asegurar que siempre comience con "P"
        if (!this.value.startsWith('P')) {
            this.value = 'P' + this.value;
        }
        // Permitir solo números después de la "P" y limitar la longitud total
        this.value = this.value.replace(/[^P0-9]/g, '').slice(0, 19); // 18 números + la "P"
    });


    // Quitar los botones de aumento/decremento
    inputNumero.addEventListener('mousewheel', function (event) {
        event.preventDefault();
    });
    //--------------------------------------------------------------------------------
    // campo contrato
    const inputContrato = document.getElementById('contrato');

    // Preenlazar el campo con ":" al inicio al enfocarse en él
    inputContrato.addEventListener('focus', function () {
        if (!this.value.startsWith(':')) {
            this.value = ':' + this.value;
        }
    });

    // Evitar la edición del ":" al inicio y permitir solo números después del ":"
    inputContrato.addEventListener('input', function () {
        if (this.value.startsWith(':')) {
            // Permitir solo números después del ":"
            this.value = ':' + this.value.replace(/[^0-9]/g, '').slice(0, 18);

        } else {
            // Si se elimina el ":", volver a agregarlo
            this.value = ':' + this.value.replace(/[^0-9]/g, '').slice(0, 18);

        }
    });

    // Evitar el evento de rueda del mouse
    inputContrato.addEventListener('mousewheel', function (event) {
        event.preventDefault();
    });
    //--------------------------------------------------------------------------------
    // campo orden de trabajo
    const inputOrden = document.getElementById('orden_trabajo');

    // Permitir solo números
    inputOrden.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);
    });

    // Quitar los botones de aumento/decremento
    inputOrden.addEventListener('mousewheel', function (event) {
        event.preventDefault();
    });

    const selectTipoTrabajo = document.getElementById('tipo_trabajo');
    const grupo1 = document.querySelector('.matriz-des1');
    const grupo2 = document.querySelector('.matriz-des2');

    selectTipoTrabajo.addEventListener('change', function () {
        if (selectTipoTrabajo.value === "FI-29 revisión periódica línea matriz") {
            grupo1.style.display = 'none';
            grupo2.style.display = 'none';
        } else {
            grupo1.style.display = '';
            grupo2.style.display = '';
        }
    });

    const btnAgregar = document.getElementById('agregarInspeccion');

    btnAgregar.addEventListener('click', function () {
        const campos = document.querySelectorAll('#ventanaEmergente input, #ventanaEmergente select');
        let formularioValido = true;

        campos.forEach(campo => {
            if (campo.value === 'DV') {
                const selectCausal = document.getElementById('causal');

                const valorSeleccionado = selectCausal.value;
                if (valorSeleccionado === '--SELECCIONE CAUSAL--') {
                    formularioValido = false;
                    selectCausal.classList.add('campo-invalido'); // Establecer borde rojo para campos no completados
                }
            }
            if (campo.value === 'SI') {
                const inputRecintos = document.getElementById('NroRecintosP');
                if (inputRecintos.value.trim() === '' && campo.value === 'NO') {
                    // Validar solo si el campo 'NO' está seleccionado
                    inputRecintos.classList.add('campo-invalido'); // Establecer borde rojo para campos no completados
                    formularioValido = false;
                } else {
                    inputRecintos.style.border = ''; // Restablecer estilo de borde por defecto
                }
            }
            if (campo.value.trim() === '' || campo.value === ':') {
                const selectTipoTrabajo = document.getElementById('tipo_trabajo');
                if (selectTipoTrabajo.value === "FI-29 revisión periódica línea matriz") {
                    if (campo.id === 'orden_trabajo' || campo.id === 'categoria' || campo.id === 'NroRecintosP' || campo.id === 'recintos') {
                        return;
                    }
                }
                const selectrecintos = document.getElementById('recintos');
                if (campo.id === 'NroRecintosP' && selectrecintos.value === 'NO') {
                    return;
                }
                formularioValido = false;
                campo.style.border = '1px solid red'; // Establecer borde rojo para campos no completados

            } else {
                campo.style.border = ''; // Restablecer estilo de borde por defecto
            }
        });

        if (formularioValido) {

            agregar_datos();

            campos.forEach(campo => {
                campo.value = campo.getAttribute('value') || '';
                switch (campo.id) {
                    case 'nombre':
                        const selectNombre = document.querySelector('#nombre');
                        // Selecciona la primera opción
                        if (selectNombre.options.length > 0) {
                            selectNombre.selectedIndex = 0;
                        }
                        break;
                    case 'fecha':
                        const fechaInput = document.getElementById('fecha');
                        const fechaPredefinida = new Date(fechaSeleccionada);
                        // Formatear la fecha predefinida según el formato de fecha requerido por el elemento de entrada de fecha (yyyy-mm-dd)
                        const formattedDate = fechaPredefinida.toISOString().slice(0, 10);
                        // Establecer la fecha predefinida en el campo de entrada de fecha
                        fechaInput.value = formattedDate;
                        break;
                    case 'recintos':
                        campo.value = 'NO';
                        break;
                }

            });
            $('#ventanaEmergente').modal('hide');
        } else {
            window.scrollTo({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });
            Swal.fire({
                position: "top-end",
                icon: "warning",
                title: "Por favor complete todos los campos",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
        }
    });
}

//---------------------------------------------------------------------------------------------------//
/* funciones para manipular campos de los contratos */
function editar(row) {
    hot_dia.updateSettings({
        cells: function (r, c) {
            const nonEditableColumns = [1, 2, 3, 4, 13, 14, 15, 17, 18];
            if (r === row && !nonEditableColumns.includes(c)) {// Si es la fila a editar y no es la columna de acciones
                return {
                    readOnly: false // Habilita la edición para esta fila
                };
            }
            return {};
        }
    });


}

function desasociar(row, fecha, cc_inspector) {

    const id = hot_dia.getDataAtRow(row);
    const url = urlDesasociar.replace(':id', id[0]);
    Swal.fire({
        title: '¿Estás seguro?',
        text: "¡Se descontará de producción!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí, desasociar!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'POST',
                data: {
                    _token: document.querySelector('#token').value
                },
                success: function (response) {

                    Swal.fire(
                        'Desasociado!',
                        'El registro ha sido descontado.',
                        'success'
                    );
                    const idCorteDetallesInput = document.querySelector('#id_corte_detalles');

                    if (idCorteDetallesInput) { // Verificar si el elemento existe
                        cargarDatos(idCorteDetallesInput.value);
                    } else {
                        cargarDatos();
                    }
                    actualizarDatosDia(fecha, cc_inspector);
                },
                error: function (xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al descontar el registro'
                    });
                }
            });
        }
    });
}

function asociar(row, fecha, cc_inspector) {
    const id = hot_dia.getDataAtRow(row);
    const url = urlDesasociar.replace(':id', id[0]);
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
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'POST',
                data: {
                    _token: document.querySelector('#token').value
                },
                success: function (response) {

                    Swal.fire(
                        'Asociado!',
                        'El registro ha sido sumado.',
                        'success'
                    );
                    const idCorteDetallesInput = document.querySelector('#id_corte_detalles');

                    if (idCorteDetallesInput) { // Verificar si el elemento existe
                        cargarDatos(idCorteDetallesInput.value);
                    } else {
                        cargarDatos();
                    }
                    actualizarDatosDia(fecha, cc_inspector);
                },
                error: function (xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al sumar el registro'
                    });
                }
            });
        }
    });

}

function diseñoEspecial(row, fecha, cc_inspector, currentValue) {
    const id = hot_dia.getDataAtRow(row);
    let actionText = currentValue ? 'desactivar' : 'agregar';
    let actionTitle = currentValue ? 'Desactivar diseño especial' : 'Diseño especial';
    let actionMessage = currentValue ? '¿Desea desactivar el diseño especial?' : '¿Desea agregar el contrato como un diseño especial?';
    const url = urlDiseñoEspecial.replace(':id', id);
    Swal.fire({
        title: actionTitle,
        text: actionMessage,
        icon: 'info',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: '¡Sí!',
        cancelButtonText: 'Cancelar'
    }).then((result) => {
        if (result.value) {
            $.ajax({
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'POST',
                data: {
                    _token: document.querySelector('#token').value
                },
                success: function (response) {

                    if (response.success) {
                        let successMessage = response.diseño_especial ? 'Se ha agregado un diseño especial.' : 'Se ha desactivado el diseño especial.';
                        Swal.fire(
                            actionTitle,
                            successMessage,
                            'success'
                        );
                        const idCorteDetallesInput = document.querySelector('#id_corte_detalles');

                        if (idCorteDetallesInput) { // Verificar si el elemento existe
                            cargarDatos(idCorteDetallesInput.value);
                        } else {
                            cargarDatos();
                        }
                        actualizarDatosDia(fecha, cc_inspector);
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Ocurrió un error al actualizar el diseño especial'
                        });
                    }
                },
                error: function (xhr, status, error) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al agregar un diseño especial'
                    });
                }
            });

        }
    });

}

//---------------------------------------------------------------------------------------------------//
/* funciones para refrescar los datos de las tablas */
function actualizarDatosDia(fecha, cc_inspector) {
    const url = urlActualizarDetallesDia.replace(':fecha', fecha).replace(':inspector', cc_inspector);
    $.ajax({
        url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            datosBaseDatos = response;
            console.log(datosBaseDatos[0]);
            // Asigna los datos obtenidos a la variable
            const array2D = convertirJSONaArray2D(datosBaseDatos[0]);
            hot_dia.loadData(array2D);
            if (array2D && array2D.length > 0) {
                document.getElementById('mensajeNoDatos').style.display = 'none';
            } else {
                document.getElementById('mensajeNoDatos').style.display = 'block';
            }

        },
        error: function (xhr, status, error) {

            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la base de datos'
            });
        }
    });
}

async function cargarDatos(idCorteDetalles = null) {
    //este valor se reasigna si la peticion viene de los botones de contar y descontar dobles
    if (idCorteDetalles === 'detalles') {
        idCorteDetalles = null
    }
    const fetchData = () => {
        return new Promise((resolve, reject) => {
            const url = document.querySelector('#id_produccion').value;
            $.ajax({
                url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
                data: {idCorteDetalles},
                type: 'GET',
                success: function (response) {
                    resolve(response);
                },
                error: function (xhr, status, error) {

                    Swal.fire({
                        icon: 'error',
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
            let options = {weekday: 'long', day: '2-digit'};
            let nombreDia = fechaObj.toLocaleDateString('es-ES', options);
            return nombreDia.charAt(0).toUpperCase() + nombreDia.slice(1);
        });

        // datos para resaltar los sabados dobles
        response.sabadodobles.forEach(entry => {
            // Iterar a través de cada registro en el array de datos
            entry.datos.forEach(record => {
                let fechaObj = new Date(record.fecha + 'T00:00:00');
                let options = {weekday: 'long', day: '2-digit'};
                let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);
                // Guardar el resultado en el array
                sabadodobles.push({
                    nombreDia: nombreDiaS,
                    ccInspector: record.cc_inspector
                });
            });
        });
        sabadosDoblesManualesTransformados = [];
        // Validar que sabadosDoblesManuales existe y es un array
        let datos
        response.sabadosDoblesManuales?.forEach(item => {
            datos = item;
        });

        datos.forEach(items => {
            if (items?.datos?.totalInspecciones?.length > 0) {
                items.datos.totalInspecciones.forEach(inspeccion => {
                    if (inspeccion?.fecha) {
                        let fechaObj = new Date(inspeccion.fecha + 'T00:00:00');
                        let options = {weekday: 'long', day: '2-digit'};
                        let nombreDiaS = fechaObj.toLocaleDateString('es-ES', options);
                        nombreDiaS = nombreDiaS.charAt(0).toUpperCase() + nombreDiaS.slice(1);

                        sabadosDoblesManualesTransformados.push({
                            nombreDia: nombreDiaS,
                            ccInspector: items.datos.cc_inspector
                        });
                    }
                });
            }
        })

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

        const headerAdicional = {label: '', colspan: 2};
        const headerFinal = {label: '', colspan: 7};
        const headerDatosAdicionales = {label: '', colspan: 4};
        headers = [headerAdicional, ...resultados.map(item => ({
            label: item.label,
            colspan: item.colspan
        })), headerFinal, headerDatosAdicionales];
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

    // Insertar fila para promedios
    hot.setDataAtCell(hot.countRows(), 1, 'TOTAL');
    // Insertar fila para sumas (arriba de la de promedios)
    hot.setDataAtCell(hot.countRows(), 1, 'PROMEDIO');

    // Calcular promedios (considerando celdas vacías)
    const promedios = []; // Array para guardar los promedios
    const filaPromedios = hot.countRows() - 1; // Penúltima fila (promedios)
    // console.log(filaPromedios);
    /* totalColspan = totalColspan + 7; */
    colProm = totalColspan - 7;
    // Calcular promedios (considerando celdas vacías)
    colProm = totalColspan - 7;

    for (let i = 0; i < colProm; i++) {
        let sum = 0;
        let count = 0;
        for (let j = 0; j < filaPromedios; j++) { // Recorre todas las filas ANTES de la de promedios
            let value = hot.getDataAtCell(j, i + 2);
            if (!isNaN(value) && value !== null && value !== '') {
                sum += parseFloat(value);
                count++;
            }
        }
        let promedio = count > 0 ? (sum / count).toFixed(2) : '';
        promedios.push(parseFloat(promedio) || 0);
    }

    // Crear un array de cambios
    const cambios = [];
    for (let i = 0; i < promedios.length; i++) {
        cambios.push([filaPromedios, i + 2, promedios[i]]);
    }

    // Aplicar todos los cambios a la vez
    hot.setDataAtCell(cambios);

    // Calcular sumas manualmente (excluyendo la fila de promedios)
    const sumas = [];
    for (let i = 0; i < totalColspan; i++) {
        let suma = 0;
        for (let j = 0; j < filaPromedios - 1; j++) { // Recorrer hasta la fila ANTES de promedios
            let value = hot.getDataAtCell(j, i + 2);
            if (!isNaN(value) && value !== null && value !== '') {
                suma += parseFloat(value);
            }
        }
        sumas.push(suma);
    }
    // Insertar sumas en la fila correspondiente
    const cambiosSumas = [];
    const filaSumas = hot.countRows() - 2; // Fila de sumas
    for (let i = 0; i < sumas.length; i++) {
        cambiosSumas.push([filaSumas, i + 2, sumas[i]]);
    }
    hot.setDataAtCell(cambiosSumas);

}

//---------------------------------------------------------------------------------------------------//
/* funcion para convertir respuesta JSON del servidor a un Array */
function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['id', 'vence', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS', 'state', 'diseno_especial'];

    return Object.keys(jsonData).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}

//---------------------------------------------------------------------------------------------------//
/* Función para agregar inspección */
function agregar_datos() {
    const select_insp = document.getElementById('nombre');
    const selectedoption = select_insp.options[select_insp.selectedIndex];
    let orden_ext = null;

    //obtener el valor de la cedula
    const cedulaInsp = select_insp.value;

    //obtener el nombre del inspector
    const nombre_insp = selectedoption.getAttribute('data-nombres');

    const municipio = document.getElementById('municipio-select').value;
    const fecha = document.getElementById('fecha').value;
    const acta = document.getElementById('N°acta').value;
    const tipo_trabajo = document.getElementById('tipo_trabajo').value;
    const contrato = document.getElementById('contrato').value;
    const orden = document.getElementById('orden_trabajo').value;
    if (tipo_trabajo === "RP 12161") {
        orden_ext = orden;
    }
    const categoria = document.getElementById('categoria').value;
    const hora_inicio = document.getElementById('hora_inicio').value;
    const hora_final = document.getElementById('hora_final').value;

    let cantidadRecintos = document.getElementById('NroRecintosP').value;
    if (cantidadRecintos === "") {
        cantidadRecintos = "NO";
    }
    const resultado_cierre = document.getElementById('resultado_cierre').value;


    const duracion = calcularDuracion(hora_inicio, hora_final);

    const [anio, mes, dia] = fecha.split('-').map(Number);

    const fechaObj = new Date(anio, mes - 1, dia); // Restar 1 al mes para que sea 0-indexado

    let diaFormateado = fechaObj.getDate().toString().padStart(2, '0');
    let mesFormateado = (fechaObj.getMonth() + 1).toString().padStart(2, '0');
    let anioFormateado = fechaObj.getFullYear().toString().slice(-2);

    const fechaFormateada = `${diaFormateado}-${mesFormateado}-${anioFormateado}`;

    const data = [
        null, // ID (se asigna automáticamente)
        nombre_insp, // OPERARIO
        cedulaInsp, // CC OPERARIO
        municipio, // MUNICIPIO
        fechaFormateada, // FECHA
        acta, // N° ACTA
        tipo_trabajo, // TIPO TRABAJO
        contrato, // CONTRATO
        orden, // ORDEN TRABAJO
        orden_ext, // ORDEN EXT
        categoria, // CATEGORIA
        resultado_cierre, // RESULTADO CIERRE
        hora_inicio, // HORA INICIO
        hora_final, // HORA FINAL
        duracion, // DURACION INSP
        cantidadRecintos, // 4 RECINTOS O MAS
        1, // ESTADO
    ];


    $.ajax({
        url: urlInsertar, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'POST',
        data: {
            _token: document.querySelector('#token').value,
            data: data
        },
        success: function (response) {
            if (response.error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: response.error
                });
                return;
            }
            if (response.ok) {
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: response.ok,
                    showConfirmButton: false,
                    toast: true,
                    timer: 3000
                });

                $('#ventanaEmergente').on('hidden.bs.modal', () => {
                    document.getElementById('exampleModal').classList.remove('modal-backdrop-custom');
                    const selectNombre = document.getElementById('nombre');
                    selectNombre.innerHTML = ''; // Limpiar las opciones
                    // Deshabilitar el select
                });
                actualizarDatosDia(fechaSeleccionada, cedulaInsp);
                const idCorteDetallesInput = document.querySelector('#id_corte_detalles');

                if (idCorteDetallesInput) { // Verificar si el elemento existe
                    cargarDatos(idCorteDetallesInput.value);
                } else {
                    cargarDatos();
                }
            }
        },
        error: function (xhr, status, error) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: error
            });
        }
    });
    return;
}

//---------------------------------------------------------------------------------------------------//
function calcularDuracion(hora_inicio, hora_final) {

    const [horaInicio, minutoInicio] = hora_inicio.split(':').map(Number);
    const [horaFinal, minutoFinal] = hora_final.split(':').map(Number);

    const fechaInicio = new Date(0, 0, 0, horaInicio, minutoInicio);
    const fechaFinal = new Date(0, 0, 0, horaFinal, minutoFinal);

    let diferencia = fechaFinal - fechaInicio;

    if (diferencia < 0) {
        // Si la hora final es anterior a la hora de inicio, sumar un día
        diferencia += 24 * 60 * 60 * 1000;
    }

    const horas = String(Math.floor(diferencia / (60 * 60 * 1000))).padStart(2, '0');
    const minutos = String(Math.floor((diferencia % (60 * 60 * 1000)) / (60 * 1000))).padStart(2, '0');

    const duracionString = `${horas}:${minutos}`;

    return duracionString; // Duración en formato "hh:mm"

}


function formatearFecha(fecha) {
    // Dividir la fecha en componentes año, mes, día
    var partes = fecha.split('-');
    var año = partes[0];
    var mes = partes[1];
    var dia = partes[2];

    // Construir la fecha en el formato deseado dd-mm-yyyy
    var fechaFormateada = dia + '-' + mes + '-' + año;

    return fechaFormateada;
}

/*setInterval(() => {
    try {
        const idCorteDetallesInput = document.querySelector('#id_corte_detalles');
        if (idCorteDetallesInput) {
            cargarDatos(idCorteDetallesInput.value);
        } else {
            cargarDatos();
        }
    } catch (error) {
        console.error("Error al cargar datos:", error);
        // Puedes agregar aquí lógica adicional para manejar el error, como mostrar un mensaje al usuario.
    }
}, 300000);*/


