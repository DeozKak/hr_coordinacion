
let hot;
document.addEventListener('DOMContentLoaded', async () => {
    let diasFestivos;
    let sabadodobles = [];
    Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
        Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);

        if (col !== 0 && col !== 1 && col !== 39 && col !== 41) {
            TD.style.backgroundColor = 'rgb(215, 232, 255)';
        }
        if (col === 33 || col === 34 || col === 35 || col === 36 || col === 37) {
            TD.style.backgroundColor = 'rgb(253, 234, 185)';
        }
        if (col === 38 || col === 32) {
            TD.style.backgroundColor = 'rgb(185, 196, 255)';
        }
        if (col === 32 && value < 180) {
            TD.style.backgroundColor = 'rgb(255, 185, 185)';
        }

        if (col === 40 && value >= 8) {
            TD.style.backgroundColor = 'rgb(147, 255, 134)';
        } else if (col === 40 && value < 8) {
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
                        if ([32, 33, 34, 35, 36, 37].includes(hot.propToCol(prop))) {
                            calculateAndSetTotal(row);
                        }
                    });
                }
            },
            afterOnCellCornerDblClick: function (event) {

                const selectedColumn = hot.getSelectedLast()[1]; // Obtiene la última columna seleccionada
                const columnName = hot.getColHeader(selectedColumn);

                if (selectedColumn >= 2 && selectedColumn <= 31) {
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

function calculateAndSetTotal(row) {
    const colIndices = [32, 33, 34, 35, 36, 37];
    let sum = 0;

    colIndices.forEach(col => {
        const cellValue = hot.getDataAtCell(row, col);
        sum += parseFloat(cellValue) || 0;
    });

    hot.setDataAtCell(row, 38, sum);
}

//---------------------------------------------------------------------------------------------------//
/* MODAL INSPECCIONES POR DIA */
function detallesDia(fecha, cc_inspector, nombreDia, nombre_completo) {

    const contratos_dia = document.querySelector('#contratos_dia');
    const cerrar = document.querySelector('#cerrar_modal');
    const titulo = document.querySelector('#titulo');
    titulo.innerHTML = `INSPECCIONES DEL DÍA ${nombreDia} - ${nombre_completo}`;
    let datos;

    $.ajax({
        url: 'detalles_diario/' + fecha + '/' + cc_inspector, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            hot_dia.loadData(response);
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
  
   
        document.getElementById('mensajeNoDatos').style.display = 'block';
   
        const hot_dia = new Handsontable(contratos_dia, {
            readOnly: true,
            manualColumnMove: false,
            data: datos,
            rowHeaders: false,
            colHeaders: ['OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', '4 RECINTOS O MAS'],
            autoWrapRow: true,
            autoWrapCol: true,
            licenseKey: 'non-commercial-and-evaluation',
            height: '350px',

        });

        cerrar.addEventListener('click', () => {
            try {
                hot_dia.destroy();
            } catch (e) { }
            $('#exampleModal').modal('hide');
        });
    
   
}