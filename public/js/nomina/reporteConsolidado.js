$(document).ready(function () {

    let loaderDiv = $('.loaderDivReporteConsolidado');

    const meses = {
        "Enero": "-01",
        "Febrero": "-02",
        "Marzo": "-03",
        "Abril": "-04",
        "Mayo": "-05",
        "Junio": "-06",
        "Julio": "-07",
        "Agosto": "-08",
        "Septiembre": "-09",
        "Octubre": "-10",
        "Noviembre": "-11",
        "Diciembre": "-12"
    };

    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    });

    let reporteConsolidado = $('#reporteConsolidado')
    let previas = $('#tablaPrevias')
    let selectorMes = $('.divSelector')
    let cardReporte = $('.cardReporte')

    $(document).on('change', '#selectorAnio', function() {
        let url = $(this).attr('data-url');
        let anio = $('#selectorAnio').val();
        let token = $(this).attr('data-token');
        $('#selectorMes').val(0);
        selectorMes.hide();
        $('#btnExportarConsolidado').removeAttr('disabled');

        loaderDiv.show();

        $.post({
            url: url,
            data: {
                _token: token,
                anio: anio
            },
            dataType: 'json',
            success: function(response) {

                cardReporte.show();

                if (window.handReportMes && !window.handReportMes.isDestroyed) {
                    window.handReportMes.destroy();
                    window.handReportMes = null;  // Reiniciar la referencia
                }

                if (window.handReporte && !window.handReporte.isDestroyed) {
                    window.handReporte.destroy();
                    window.handReporte = null;  // Reiniciar la referencia
                }

                if (window.handPrevias && !window.handPrevias.isDestroyed) {
                    window.handPrevias.destroy();
                    window.handPrevias = null;  // Reiniciar la referencia
                }

                loaderDiv.hide();
                selectorMes.show();

                window.handPrevias = new Handsontable(previas[0], {
                    data: [],
                    colHeaders: [],
                    readOnly: true,
                    height: 'auto',
                    licenseKey: 'non-commercial-and-evaluation',
                })
                
                window.handReporte = new Handsontable(reporteConsolidado[0], {
                    data: [],
                    colHeaders: [],
                    readOnly: true,
                    height: 'auto',
                    licenseKey: 'non-commercial-and-evaluation',
                });

                // Mostrar el contenedor de la tabla
                reporteConsolidado.show();
                previas.show();

                let data = response;

                let totalGeneral = 0;

                // Formatear los datos para Handsontable
                let formattedData = data.map(item => {

                    let totalFila = (item.total_residencial + item.total_comercial + item.total_inspecciones) || 0;

                    totalGeneral += totalFila;

                    return [
                        item.nombre_mes,
                        item.total_residencial || 0,
                        item.total_comercial || 0,
                        item.total_inspecciones || 0,
                        totalFila, // Total de la fila
                        item.total_inspectores, // No Inspectores (Placeholder)
                        formatter.format(item.total), // Valor (Placeholder)
                        0, // Promedio Diario UND (Placeholder)
                        0, // Promedio por Inspector UND (Placeholder)
                        0, // Promedio Diario $ (Placeholder)
                        0, // Promedio por Inspector $ (Placeholder)
                        item.metaGyc, // Meta E&C (Placeholder)
                        0 + " %", // % Cumpl (Placeholder)
                        item.metaGdo, // Meta GDO (Placeholder)
                        0 + " %"  // % Cumpl GDO (Placeholder)
                    ];
                });

                // Agregar la fila de totales al final de los datos formateados
                formattedData.push([
                    'TOTAL',
                    "", // Puedes dejar vacías las columnas que no quieras sumar
                    "", // Si deseas sumar otras columnas, puedes hacerlo aquí
                    "",
                    totalGeneral, // Total general sumado
                    "", // No Inspectores (Placeholder)
                    "", // Valor (Placeholder)
                    "", // Promedio Diario UND (Placeholder)
                    "", // Promedio por Inspector UND (Placeholder)
                    "", // Promedio Diario $ (Placeholder)
                    "", // Promedio por Inspector $ (Placeholder)
                    "", // Meta E&C (Placeholder)
                    "", // % Cumpl (Placeholder)
                    "", // Meta GDO (Placeholder)
                    ""  // % Cumpl GDO (Placeholder)
                ]);
    
                // Actualizar los datos en la tabla Handsontable
                handReporte.updateSettings({
                    data: formattedData, // Asigna los datos formateados
                    colHeaders: [
                        'Meses',
                        'INSPECCION RP/AS/NV<br>RESIDENCIAL',
                        'INSPECCION RP/AS/NV<br>COMERCIAL',
                        'INSPECCION<br>INDUSTRIAL',
                        'TOTAL',
                        'N° INSPECTORES',
                        'VALOR',
                        'PROMEDIO<br>DIARIO UND',
                        'PROMEDIO<br>POR INSPECTOR UND',
                        'PROMEDIO<br>DIARIO $',
                        'PROMEDIO<br>POR INSPECTOR $',
                        'META E&C',
                        '% CUMPL',
                        'META GDO',
                        '% CUMPL'
                    ],
                });

                // ponemos las columnas 11 y 13 editables
                handReporte.updateSettings({
                    cells: function (rowIndex, colIndex) {
                        let cellProperties = {};

                        if(colIndex === 0){
                            cellProperties.className = 'cell-mes';
                        }

                        if (colIndex === 13 && rowIndex !== 12) {
                            cellProperties.readOnly = false;
                            cellProperties.className = 'cell-metaGDO';
                        }

                        //ponemos de color rojo la columna 11
                        if (colIndex === 11 && rowIndex !== 12) {
                            cellProperties.readOnly = false;
                            cellProperties.className = 'cell-metaEYC';
                        }
                        return cellProperties;
                    },
                    afterOnCellMouseDown: function(event, coords, TD) {
                        if (coords.col === 11 || coords.col === 13) {
                            setTimeout(function() {
                                const hotTextarea = document.querySelector('textarea.handsontableInput');
                                if (hotTextarea) {
                                    hotTextarea.removeAttribute('aria-hidden');
                                    hotTextarea.setAttribute('tabindex', '0');
                                }
                            }, 0);
                        }
                    },
                });

                let formattedData2 = data.map(item => [
                    item.nombre_mes,
                    item.total_rp || 0,
                    item.total_previas || 0,
                    (item.total_rp + item.total_previas) || 0,
                    0+" %",
                    0+" %"
                ]);

                handPrevias.updateSettings({
                    data: formattedData2,
                    colHeaders: [
                        'Meses',
                        'RP',
                        'PREVIAS',
                        'TOTAL',
                        '% RP',
                        '% PREVIAS',
                    ],
                });

                let ultimaFilaPrevias = handPrevias.countRows();

                for(let i = 0; i < ultimaFilaPrevias; i++) {
                    let rpFila = handPrevias.getDataAtCell(i, 1);
                    let previasFila = handPrevias.getDataAtCell(i, 2);
                    let totalFila = handPrevias.getDataAtCell(i, 3);
                    let porcentajeRp = (rpFila / totalFila) * 100;
                    let porcentajePrevias = (previasFila / totalFila) * 100;

                    // sacamos solo dos decimales sin usar el fixed 
                    let porcentajeRpStr = porcentajeRp.toString();
                    let porcentajePreviasStr = porcentajePrevias.toString();
                    let porcentajeRpArr = porcentajeRpStr.split('.');
                    let porcentajePreviasArr = porcentajePreviasStr.split('.');
                    
                    if(!isNaN(porcentajeRp)){
                        if(porcentajeRpArr[1]){
                            porcentajeRp = porcentajeRpArr[0]+"."+ porcentajeRpArr[1].charAt(0)+""+porcentajeRpArr[1].charAt(1);
                        }else{
                            porcentajeRp = porcentajeRpArr[0];
                        }
                    }else{
                        porcentajeRp = 0;
                    }
                    
                    if(!isNaN(porcentajePrevias)){
                        if(porcentajePreviasArr[1]){
                            porcentajePrevias = porcentajePreviasArr[0]+"."+ porcentajePreviasArr[1].charAt(0)+""+porcentajePreviasArr[1].charAt(1);
                        }else{
                            porcentajePrevias = porcentajePreviasArr[0];
                        }
                    }else{
                        porcentajePrevias = 0;
                    }
                    
                    handPrevias.setDataAtCell(i, 4, porcentajeRp+' %');
                    handPrevias.setDataAtCell(i, 5, porcentajePrevias+' %');
                }

                handPrevias.updateSettings({
                    cells: function (rowIndex, colIndex) {
                        let cellProperties = {};

                        if(colIndex === 0){
                            cellProperties.className = 'cell-mes';
                        }

                        if (colIndex === 1) {
                            cellProperties.readOnly = true;
                            cellProperties.className = 'cell-rp';
                        }

                        //ponemos de color rojo la columna 11
                        if (colIndex === 2) {
                            cellProperties.readOnly = true;
                            cellProperties.className = 'cell-previas';
                        }
                        return cellProperties;
                    },
                });

                let ultimaFila = handReporte.countRows();
                let penultimateRow = ultimaFila - 2

                for(let i = 0; i < penultimateRow; i++) {
                    let insResFila = handReporte.getDataAtCell(i, 1);
                    let insComFila = handReporte.getDataAtCell(i, 2);
                    let numInspectores = handReporte.getDataAtCell(i, 5);

                    // PROMEDIO DIARIO UND -------------------------------------------
                    let promDiarioUnid = (insResFila + insComFila) / 24;
                    let promDiarioUnidStr = promDiarioUnid.toString()
                    if(promDiarioUnidStr != 0 || promDiarioUnidStr != '0'){
                        let promDiarioUnidArr = promDiarioUnidStr.split('.');
                        promDiarioUnid = "";
                        if(promDiarioUnidArr[1]){
                            promDiarioUnid = promDiarioUnidArr[0]+"."+ promDiarioUnidArr[1].charAt(0)+""+promDiarioUnidArr[1].charAt(1);
                        }else{
                            promDiarioUnid = promDiarioUnidArr[0];
                        }
                    }else{
                        promDiarioUnid = 0;
                    }
                    handReporte.setDataAtCell(i, 7, promDiarioUnid);
                    // ---------------------------------------------------------------

                    // PROMEDIO POR INSPECTOR UND ------------------------------------
                    let promInsUni = promDiarioUnid / numInspectores;
                    if(!isNaN(promInsUni)){
                        let promInsUniStr = promInsUni.toString();
                        let promInsUniArr = promInsUniStr.split('.');
                        promInsUni = "";
                        if(promInsUniArr[1]){
                            promInsUni = promInsUniArr[0]+"."+ promInsUniArr[1].charAt(0)+""+promInsUniArr[1].charAt(1);
                        }else{
                            promInsUni = promInsUniArr[0];
                        }
                    }else{
                        promInsUni = 0;
                    }
                    handReporte.setDataAtCell(i, 8, promInsUni);   
                    //----------------------------------------------------------------

                    // PROMEDIO DIARIO $ ---------------------------------------------
                    let valor = handReporte.getDataAtCell(i, 6);
                    let valorArr = valor.split('$');
                    valorArr = valorArr[1].trim();
                    valorArr = valorArr.split('.');
                    let valorFinal = valorArr.join('');
                    valorFinal = parseInt(valorFinal);
                    let promDiario = valorFinal / 30;
                    let promDiarioStr= promDiario.toString();
                    let promDiarioArr = promDiarioStr.split('.');
                    handReporte.setDataAtCell(i, 9, formatter.format(promDiarioArr[0]));
                    // ---------------------------------------------------------------

                    // PROMEDIO POR INSPECTOR $ --------------------------------------
                    let promPorIns = promDiario / numInspectores;
                    if(!isNaN(promPorIns)){
                        let promPorInsStr = promPorIns.toString();
                        let promPorInsArr = promPorInsStr.split('.');
                        promPorIns = promPorInsArr[0];
                    }else{
                        promPorIns = 0;
                    }
                    handReporte.setDataAtCell(i, 10, formatter.format(promPorIns));

                    let totalFila = handReporte.getDataAtCell(i, 4);

                    // % METAGYC -----------------------------------------------------
                    let metagycCol = handReporte.getDataAtCell(i, 11);
                    let porcentaje = (totalFila / metagycCol) * 100;
                    if(porcentaje != Infinity && !isNaN(porcentaje)){
                        // ponemos solo dos decimales
                        let porcentajeStr = porcentaje.toString();
                        let porcentajeArr = porcentajeStr.split('.');
                        if(porcentajeArr[1]){
                            porcentaje = porcentajeArr[0]+"."+ porcentajeArr[1].charAt(0)+""+porcentajeArr[1].charAt(1);
                        }else{
                            porcentaje = porcentajeArr[0];
                        }
                    }else{
                        porcentaje = 0;
                    }
                    handReporte.setDataAtCell(i, 12, porcentaje+' %');
                    // ---------------------------------------------------------------

                    // % METAGDO -----------------------------------------------------
                    let metagdoCol = handReporte.getDataAtCell(i, 13);
                    porcentaje = (totalFila / metagdoCol) * 100;
                    if(porcentaje != Infinity && !isNaN(porcentaje)){
                        // ponemos solo dos decimales
                        let porcentajeStr = porcentaje.toString();
                        let porcentajeArr = porcentajeStr.split('.');
                        if(porcentajeArr[1]){
                            porcentaje = porcentajeArr[0]+"."+ porcentajeArr[1].charAt(0)+""+porcentajeArr[1].charAt(1);
                        }else{
                            porcentaje = porcentajeArr[0];
                        }
                    }else{
                        porcentaje = 0;
                    }

                    handReporte.setDataAtCell(i, 14, porcentaje+' %');
                    // ---------------------------------------------------------------

                }

                handReporte.addHook('afterChange', function (changes, source) {
                    if (source === 'edit') {
                        changes.forEach(([row, col, oldValue, newValue]) => {
                            
                            let totalRows = handReporte.countRows();
                            let penultimateRow = totalRows - 2;

                            if (col === 11 && row === penultimateRow) {
                                if(newValue === ""){
                                    newValue = 0;
                                }
                                if (!regex(newValue)) {
                                    alert('Por favor, ingrese solo números');
                                    if(newValue === "" || newValue === 0 || newValue === "0"){
                                        handReporte.setDataAtCell(row, 12, 0+' %'); 
                                    }
                                }else{
                                    let fechas = handReporte.getData();
                                    let year = fechas[row][0]
                                    let url = $('#guardarMetas').val();
                                    let metagyc = newValue;
        
                                    let numMes = meses[year] || "";
        
                                    let fechaFinal = anio + numMes
                                
                                    $.post({
                                        url: url,
                                        data: {
                                            _token: token,
                                            anioMes: fechaFinal,
                                            metagyc: metagyc
                                        },
                                        success: function(response) {
                                            let total = fechas[row][4];
                                            let porcentaje = (total / metagyc) * 100;
                                            if(porcentaje != Infinity && !isNaN(porcentaje)){
                                                // ponemos solo dos decimales
                                                let porcentajeStr = porcentaje.toString();
                                                let porcentajeArr = porcentajeStr.split('.');
                                                if(porcentajeArr[1]){
                                                    porcentaje = porcentajeArr[0]+"."+ porcentajeArr[1].charAt(0)+""+porcentajeArr[1].charAt(1);
                                                }else{
                                                    porcentaje = porcentajeArr[0];
                                                }
                                            }else{
                                                porcentaje = 0;
                                            }
                                            handReporte.setDataAtCell(row, 12, porcentaje+' %');
                                        },
                                        error: function(xhr, status, error ) {
                                            Swal.fire({
                                                type: 'error',
                                                title: 'Error',
                                                text: error
                                            })
                                        }
                                    })
                                }
                            }else if(col == 13 && row === penultimateRow){
                                if(newValue === ""){
                                    newValue = 0;
                                }
                                if (!regex(newValue)) {
                                    alert('Por favor, ingrese solo números');
                                }else{
                                    let fechas = handReporte.getData();
                                    let year = fechas[row][0]
                                    let url = $('#guardarMetas').val();
                                    let metagdo = newValue;
        
                                    let numMes = meses[year] || "";
        
                                    let fechaFinal = anio + numMes
                                
                                    $.post({
                                        url: url,
                                        data: {
                                            _token: token,
                                            anioMes: fechaFinal,
                                            metagdo: metagdo
                                        },
                                        success: function(response) {
                                            let total = fechas[row][4];
                                            let porcentaje = (total / metagdo) * 100;
                                            if(porcentaje != Infinity && !isNaN(porcentaje)){
                                                // ponemos solo dos decimales
                                                let porcentajeStr = porcentaje.toString();
                                                let porcentajeArr = porcentajeStr.split('.');
                                                if(porcentajeArr[1]){
                                                    porcentaje = porcentajeArr[0]+"."+ porcentajeArr[1].charAt(0)+""+porcentajeArr[1].charAt(1);
                                                }else{
                                                    porcentaje = porcentajeArr[0];
                                                }
                                            }else{
                                                porcentaje = 0;
                                            }
                                            handReporte.setDataAtCell(row, 14, porcentaje+' %');
                                        },
                                        error: function(xhr, status, error ) {
                                            Swal.fire({
                                                type: 'error',
                                                title: 'Error',
                                                text: error
                                            })
                                        }
                                    })
                                }
                            }
                        });
                    }
                });
            },
            error: function(xhr, status, error) {
                loaderDiv.hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                })
            }
        });
    });

    //REPORTE POR MES 
    let reporteMes = $('#reportePorMes')
    $(document).on('change', '#selectorMes', function() {
        let selectorAnio = $('#selectorAnio')
        let selectorMes = $('#selectorMes')
        let mes = selectorMes.val();
        let anio = selectorAnio.val();
        let url = selectorMes.attr('data-url');
        let token = selectorAnio.attr('data-token');
        let data = anio + mes;

        $.post({
            url: url,
            data: {
                _token: token,
                data: data
            },
            dataType: 'json',
            success: function(response) {

                if (window.handReportMes && !window.handReportMes.isDestroyed) {
                    window.handReportMes.destroy();
                    window.handReportMes = null;
                }

                let data = response
                let parametros = data.fechas[0];
    
                window.handReportMes = new Handsontable(reporteMes[0], {
                    data: [],
                    colHeaders: [],
                    readOnly: true,
                    height: 'auto',
                    licenseKey: 'non-commercial-and-evaluation',
                });

                let newHeaders = [
                    'ZONA', 
                    'N° INSPECCIONES',
                    '% INSPECCIONES',
                    'INSPECCIONES $',
                    '% INSPECCIONES $'
                ];

                let totalInspecciones = data.residencial.zona_1 + 
                                        data.residencial.zona_2 + 
                                        data.residencial.zona_3 + 
                                        data.comercial.zona_1 + 
                                        data.comercial.zona_2 + 
                                        data.comercial.zona_3

                let precioRes1 = data.residencial.zona_1 * parametros.res_metro;
                let precioRes2 = data.residencial.zona_2 * parametros.res_norte;
                let precioRes3 = data.residencial.zona_3 * parametros.res_cauca;

                let precioCom1 = data.comercial.zona_1 * parametros.com_metro;
                let precioCom2 = data.comercial.zona_2 * parametros.com_norte;
                let precioCom3 = data.comercial.zona_3 * parametros.com_cauca;

                let sumaTotal = precioRes1 +
                                precioRes2 +
                                precioRes3 +
                                precioCom1 +
                                precioCom2 +
                                precioCom3

                let porcentajeRes1 = generarPorcentaje(data.residencial.zona_1, totalInspecciones)
                let porcentajeRes2 = generarPorcentaje(data.residencial.zona_2, totalInspecciones)
                let porcentajeRes3 = generarPorcentaje(data.residencial.zona_3, totalInspecciones)
                let porcentajeCom1 = generarPorcentaje(data.comercial.zona_1, totalInspecciones)
                let porcentajeCom2 = generarPorcentaje(data.comercial.zona_2, totalInspecciones)
                let porcentajeCom3 = generarPorcentaje(data.comercial.zona_3, totalInspecciones)

                let sumaPorcentaje =parseFloat(porcentajeRes1) +
                                    parseFloat(porcentajeRes2) +
                                    parseFloat(porcentajeRes3) +
                                    parseFloat(porcentajeCom1) +
                                    parseFloat(porcentajeCom2) +
                                    parseFloat(porcentajeCom3)
                
                let procentajeFinal = sumaPorcentaje.toFixed(2);


                let porcentajePrecioRes1 = generarPorcentaje(precioRes1,sumaTotal)
                let porcentajePrecioRes2 = generarPorcentaje(precioRes2,sumaTotal)
                let porcentajePrecioRes3 = generarPorcentaje(precioRes3,sumaTotal)
                let porcentajePrecioCom1 = generarPorcentaje(precioCom1,sumaTotal)
                let porcentajePrecioCom2 = generarPorcentaje(precioCom2,sumaTotal)
                let porcentajePrecioCom3 = generarPorcentaje(precioCom3,sumaTotal)

                let sumaPorcentaje2=parseFloat(porcentajePrecioRes1) +
                                    parseFloat(porcentajePrecioRes2) +
                                    parseFloat(porcentajePrecioRes3) +
                                    parseFloat(porcentajePrecioCom1) +
                                    parseFloat(porcentajePrecioCom2) +
                                    parseFloat(porcentajePrecioCom3)

                let procentajeFinal2 = sumaPorcentaje2.toFixed(2);
                
                handReportMes.updateSettings({
                    data: [
                        ['RESIDENCIAL METROPOLITANA', data.residencial.zona_1, porcentajeRes1+"%",formatter.format(precioRes1), porcentajePrecioRes1+"%"],
                        ['RESIDENCIAL NORTE DEL VALLE', data.residencial.zona_2, porcentajeRes2+"%", formatter.format(precioRes2), porcentajePrecioRes2+"%"],
                        ['RESIDENCIAL CAUCA/BUENAVENTURA', data.residencial.zona_3, porcentajeRes3+"%", formatter.format(precioRes3), porcentajePrecioRes3+"%"],
                        ['COMERCIAL METROPOLITANA', data.comercial.zona_1,porcentajeCom1+"%", formatter.format(precioCom1), porcentajePrecioCom1+"%"],
                        ['COMERCIAL NORTE DEL VALLE', data.comercial.zona_2, porcentajeCom2+"%", formatter.format(precioCom2), porcentajePrecioCom2+"%"],
                        ['COMERCIAL CAUCA/BUENAVENTURA', data.comercial.zona_3, porcentajeCom3+"%", formatter.format(precioCom3), porcentajePrecioCom3+"%"],
                        ['TOTAL', totalInspecciones, procentajeFinal+"%",formatter.format(sumaTotal), procentajeFinal2+"%"],
                    ],
                    colHeaders: newHeaders
                });

                handReportMes.updateSettings({
                    cells: function (rowIndex, colIndex) {
                        let cellProperties = {};

                        if((rowIndex === 0 && colIndex === 0) || (rowIndex === 1 && colIndex === 0) || ( rowIndex === 2 && colIndex === 0)){
                            cellProperties.className = 'cell-rp';
                        }

                        if((rowIndex === 3 && colIndex === 0) || (rowIndex === 4 && colIndex === 0) || (rowIndex === 5 && colIndex === 0)){
                            cellProperties.className = 'cell-comercial';
                        }

                        return cellProperties;
                    }
                });
                reporteMes.show();
            },
            error: function(xhr, status, error) {
                console.log(error)
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: error
                })
            }
        })
    });

    $(document).on('click', '#btnExportarConsolidado', function() {
        // Obtener datos de las tablas Handsontable
        let datos1 = handReporte.getData(); // Datos de la primera tabla
        let datos2 = handPrevias.getData(); // Datos de la segunda tabla
    
        if (datos1.length > 0 && datos2.length > 0) {
            let headers1 = handReporte.getColHeader();
            headers1 = headers1.map(header => header.replace(/<br>/g, ' '));

            let headers2 = handPrevias.getColHeader(); // Encabezados de la segunda tabla
    
            // Añadir encabezados a los datos de las tablas
            datos1.unshift(headers1);
            datos2.unshift(headers2);
    
            let datos3 = []; // Inicializamos datos3 como un array vacío
            let headers3 = [];
    
            // Verificar si handReportMes está definido
            if (typeof handReportMes !== 'undefined') {
                datos3 = handReportMes.getData(); // Datos de la tercera tabla (si existe)
                headers3 = handReportMes.getColHeader(); // Encabezados de la tercera tabla (si existe)
                datos3.unshift(headers3); // Añadir encabezados a los datos de la tercera tabla
            }
    
            // Asegurar que ambas tablas (datos2 y datos3) tengan el mismo número de filas
            let maxRows = Math.max(datos2.length, datos3.length);
            for (let i = 0; i < maxRows; i++) {
                // Si la fila i no existe en datos2 o datos3, la rellenamos con vacío
                if (!datos2[i]) datos2[i] = [];
                if (!datos3[i]) datos3[i] = [];
    
                // Añadir dos columnas vacías al final de cada fila de datos2
                datos2[i].push('', '');
            }
    
            // Combinar ambas tablas horizontalmente (datos2 + espacio + datos3)
            let datosCombinados = datos2.map((fila, index) => {
                return fila.concat(datos3[index] || []);
            });
    
            // Combinar con la tabla 1 (debajo)
            datosCombinados = datos1.concat([[]], [[]], datosCombinados);
    
            // Crear una nueva hoja de trabajo con los datos combinados
            const worksheet = XLSX.utils.aoa_to_sheet(datosCombinados);
    
            // Crear un nuevo libro de trabajo y añadir la hoja
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte Consolidado');
    
            // Descargar el archivo Excel
            XLSX.writeFile(workbook, 'ReporteConsolidado.xlsx');
    
        } else {
            Swal.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione una opcion antes de exportar',
                icon: 'warning'
            });
        }
    });
    
    function generarPorcentaje(valor1, valor2) {

        let porcentaje = (valor1 / valor2) * 100;
        
        if(porcentaje != Infinity && !isNaN(porcentaje)){
            let porcentajeStr = porcentaje.toString();
            let porcentajeArr = porcentajeStr.split('.');
            if(porcentajeArr[1]){
                porcentaje = porcentajeArr[0]+"."+ porcentajeArr[1].charAt(0)+""+porcentajeArr[1].charAt(1);
            }else{
                porcentaje = porcentajeArr[0];
            }
        }else{
            porcentaje = 0;
        }

        return porcentaje;
    }

    function regex(str) {
        return /^\d+$/.test(str);
    }
})