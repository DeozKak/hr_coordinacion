let totalColspan = 0;
document.addEventListener('DOMContentLoaded', async () => {
    let headers = [];
    let rows = [];
    let fechas = [];

    function cellStyle(){
        Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
            Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
            let columNameColor = hotInstance.getColHeader(col);
       
            if (col !== 0 && col !== 1 && columNameColor !== 'META POR INSPECTOR' && columNameColor !== 'DIAS LABORADOS') {
                TD.style.backgroundColor = 'rgb(215, 232, 255)';
            }
          
            const columnName = hotInstance.getColHeader(col);
            const ccOperario = hotInstance.getDataAtCell(row, 0);
           
        });
    }
    
    const fetchData = () => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: document.querySelector('#data').value, // Ruta al archivo PHP que realiza la consulta a la base de datos
                type: 'GET',
                success: function (response) {
                    if (response.error) {
                        Swal.fire({
                            type: 'warning',
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
                        type: 'error',
                        title: 'Error',
                        text: 'Ocurrió un error al cargar los datos de la base de datos'
                    });
                    reject(error);
                }
            });
        });
    };
    //recibimos la respuesta del servidor, para su manipulacion
    const response = await fetchData();

      // asociar las fechas con los nombres de los dias
      fechas = response.diasIntermedios.map((item, index) => {
        return {
            dia: `${item.nombreDia} ${item.dias}`,
            fecha: response.fechasIntermedias[index]
        };
    });
    // datos para la tabla
    rows = response.produccionInspector;
    // preparacion headers tabla de fallidas
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

    // Primera fila de encabezados (Meses)
    const headerAdicional = { label: '', colspan: 2 };
    const primeraFila = [headerAdicional, ...resultados.map(item => ({ label: item.label, colspan: item.colspan }))];
    headers.push(primeraFila); // Agregar la primera fila a headers
    // Segunda fila de encabezados (Días)
    const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
    const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
    const segundaFila = [...datosAdicionales, ...datosDias]; // Usar spread operator para crear un nuevo array
    headers.push(segundaFila); // Agregar la segunda fila a headers

    hot = new Handsontable(detalles, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: true,
        nestedHeaders: headers,
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
            InspectorSelected = hot.getSelectedLast();
            const selectedColumn = hot.getSelectedLast()[1]; // Obtiene la última columna seleccionada
            const columnName = hot.getColHeader(selectedColumn);
            const isFechaColumn = fechas.some(fecha => fecha.dia === columnName);
            console.log(selectedColumn, columnName, isFechaColumn);
            if (isFechaColumn) {
                const selectedRow = hot.getSelectedLast()[0]; // Obtiene la última fila seleccionada
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

 

});


async function detallesDia(fecha, cc_inspector, nombreDia, nombre_completo) {
    /* const token = document.querySelector('#token').value; */
  /*   const fecha_inicioCorte = document.querySelector('#fecha_inicio').value; */
    /* variable de rutas sale de la vista */
    const response = await fetch(urlObtenerDetalles + `?fecha=${fecha}&cc_inspector=${cc_inspector}`);
    const urlDetalles = await response.text();
    
    const contratos_dia = document.querySelector('#contratos_dia');
    const cerrar = document.querySelector('#cerrar_modal');
    const titulo = document.querySelector('#titulo');
    titulo.innerHTML = `FALLIDAS DEL DÍA ${nombreDia} - ${nombre_completo}`;

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


    hot_dia = new Handsontable(contratos_dia, {
        readOnly: true,
        manualColumnMove: false,
        rowHeaders: false,
        colHeaders: [ 'vence', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE' ],
        columns: [
        
            { renderer: 'customStylesRendererdays' }, // VENCE
            { type: 'text' }, // OPERARIO
            { type: 'numeric', validator: 'custom.numeric' }, // CC OPERARIO
            { type: 'text' }, // MUNICIPIO
            {
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
              
            },// Usa el editor personalizado, // FECHA
            { type: 'numeric', validator: 'custom.numeric', renderer: 'customStylesRendererdays' }, // N° ACTA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RP 10444', 'RP 12161', 'RN 12162', 'SA 12164', 'SA 12163'],
            }, // TIPO TRABAJO
            { type: 'numeric', validator: 'custom.numeric'}, // CONTRATO
            { type: 'numeric', validator: 'custom.numeric' }, // ORDEN TRABAJO
            { type: 'numeric', correctFormat: true }, // ORDEN EXT
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['RESIDENCIAL', 'COMERCIAL'],
            }, // CATEGORIA
            {
                editor: 'select', // Tipo combobox
                selectOptions: ['CERTIFICADA', 'CERTIFICADA CON NOVEDADES', 'INSPECCIONADA CON DEFECTO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'],
            }, // RESULTADO CIERRE
           
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
        }});

        cerrar.addEventListener('click', () => {
            try {
                hot_dia.destroy();
            } catch (e) { }
            $('#exampleModal').modal('hide');
        });

        $.ajax({
            url: urlDetalles, // Ruta al archivo PHP que realiza la consulta a la base de datos
            type: 'GET',
            success: function (response) {
            // Asigna los datos obtenidos a la variable
            const array2D = convertirJSONaArray2D(response);
            hot_dia.loadData(array2D);
            if (array2D && array2D.length > 0) {
                document.getElementById('mensajeNoDatos').style.display = 'none';
            } else {
                document.getElementById('mensajeNoDatos').style.display = 'block';
            }
                
            },error: function (xhr, status, error) {
                console.error('Error fetching data:', error);
            },});
    }

    function convertirJSONaArray2D(jsonData) {
        const columnasDeseadas = ['id', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS', 'state', 'diseno_especial'];
    
        return Object.keys(jsonData).map(key => {
            const fila = jsonData[key];
            return columnasDeseadas.map(columna => fila[columna]);
        });
    }