document.addEventListener("DOMContentLoaded", function() {
    let container = document.querySelector('#example');
    let containerResumen = document.getElementById('tablaResumen');
    let loaderDiv = document.querySelector('.loaderDiv');
    let nominaSelectorMes = document.querySelector('.nominaSelectorMes');
    let loaderTablaDiario = document.querySelector('.loaderTablaDiario');

    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    });

    let handDos = new Handsontable(containerResumen, {
        data: [
            ['PREVIA/PERIODICA (metropolitana residencial)', formatter.format(38990)],
            ['PREVIA/PERIODICA (Zona norte residencial)', formatter.format(42451)],
            ['PREVIA/PERIODICA (Zona Buenaventura y Cauca residencial)', formatter.format(55479)],
            ['PREVIA/PERIODICA (Zona metropolitana comercial)', formatter.format(70810)],
            ['PREVIA/PERIÓDICA (Zona norte comercial)', formatter.format(71240), '',],
            ['PREVIA /PERIÓDICA (Zona Buenaventura y Cauca comercial)', formatter.format(76300)],
            ['INSPECCION INDUSTRIAL', formatter.format(680000)],
            ['','']
        ], 
        height: 'auto', 
        licenseKey: 'non-commercial-and-evaluation', // Licencia
        readOnly: true,
    });

    let hot = new Handsontable(container, {
        data: [],
        rowHeaders: true,
        colHeaders: [],
        height: 'auto',
        autoWrapRow: true,
        autoWrapCol: true,
        licenseKey: 'non-commercial-and-evaluation',
        readOnly: true
    });

    const headersEnero = [
        'Fechas',
        'RP/ AS / NV METRO <br> RES', 
        'RP/ AS / NV NORTE <br> RES', 
        'RP/ AS / NV CAUCA <br> RES', 
        'RP/ AS / NV METRO <br> COM',
        'RP/ AS / NV NORTE <br> COM', 
        'RP/ AS / NV CAUCA <br> COM', 
        'FACTURACION RP/ AS / NV <br> METRO RES',
        'FACTURACION RP/ AS / NV<br>NORTE RES', 
        'FACTURACION RP/ AS / NV<br>CAUCA RES',
        'FACTURACION RP/ AS / NV<br>METRO COM', 
        'FACTURACION RP/ AS / NV<br>NORTE COM', 
        'FACTURACION RP/ AS / NV<br>CAUCA COM',
        'FACTURACION <br> VALLE DEL CAUCA',
        'INSPECTORES',
        'PROMEDIO',
        'CANTIDAD <br> EJECUTADA',
        'DIFERENCIA',
        'CANTIDAD <br> PROYECTADA',
        'VALOR <br> PROYECTADO',
        '%',
        'VALOR <br> EJECUTADO',
        '%'
    ];

    const headersMeses = headersEnero;

    let valorInspeccionIndustrial = "";

    const loadData = (url, headers) => {
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('La respuesta de la red no fue correcta');
                }
                return response.json();
            }).then(data => {

                    $('.cardReporteDiarioProduccion').show();
                
                    hot.updateSettings({
                        colHeaders: headers,
                        data: data
                    });
                    
                    diasFestivosRender = [];
                    diasSabadosRender = [];
                    valoresPrevios = [];

                    const preciosParametro = data.preciosParametros;
                    const fechas = data.conteos;
                    const diasFestivos = data.diasFestivos;
                    const diasSabados = data.diasSabados;
                    const nomina = data.nomina;
                    const inspeccionIndustriales = data.inspeccionIndustrial;
    
                    if(inspeccionIndustriales.length > 0){
                        valorInspeccionIndustrial = inspeccionIndustriales[0].cantidad;
                    }
    
                    if(nomina.length > 0){
                        for (let i = 0; i < nomina.length; i++) {
                            valoresPrevios[i] = nomina[i].proyeccion; 
                        }
                    }
    
                    for (let i = 0; i < diasFestivos.length; i++) {
                        diasFestivosRender.push(diasFestivos[i]);
                    }
    
                    for(let i = 0; i < diasSabados.length; i++){
                        diasSabadosRender.push(diasSabados[i]);
                    }
                    
                    let filasData = [];
                    let totalRes1 = 0;
                    let totalRes2 = 0;
                    let totalRes3 = 0;
                    let totalCom1 = 0;
                    let totalCom2 = 0;
                    let totalCom3 = 0;
                    let sumaTotalColumnaRes1 = 0;
                    let sumaTotalColumnaRes2 = 0;
                    let sumaTotalColumnaRes3 = 0;
                    let sumaTotalColumnaCom1 = 0;
                    let sumaTotalColumnaCom2 = 0;
                    let sumaTotalColumnaCom3 = 0;
                    let sumaGeneral = 0;
                    let cantidadEjecutadaAnterior = 0;
                    let valorEjecutadoAnterior = 0;
                    let sumaPromedioFila = 0;
    
                    fechas.forEach(fecha => {
                        let fila = [fecha.fecha];
                        let conteoZonaRes1 = 0;
                        let conteoZonaRes2 = 0;
                        let conteoZonaRes3 = 0;
                        let conteoZonaCom1 = 0;
                        let conteoZonaCom2 = 0;
                        let conteoZonaCom3 = 0;
                        let totalPrecioPorFilaRes1 = 0;
                        let totalPrecioPorFilaRes2 = 0;
                        let totalPrecioPorFilaRes3 = 0;
                        let totalPrecioPorFilaCom1 = 0;
                        let totalPrecioPorFilaCom2 = 0;
                        let totalPrecioPorFilaCom3 = 0;
                        let sumaTotalFila = 0;
                        let promedioFila = 0;
                        let totalInspectoresFila = fecha.conteos.length;
            
                        fecha.conteos.forEach(conteo => {
                            // zona residencial-------------------------------------------------
                            if(conteo.count_residencial_zona_1 > 0){
                                conteoZonaRes1 += conteo.count_residencial_zona_1;
                                totalPrecioPorFilaRes1 += conteo.count_residencial_zona_1 * preciosParametro[0].res_metro;
                                totalRes1 += conteo.count_residencial_zona_1;
                            }
                            if(conteo.count_residencial_zona_2 > 0){
                                conteoZonaRes2 += conteo.count_residencial_zona_2;
                                totalPrecioPorFilaRes2 += conteo.count_residencial_zona_2 * preciosParametro[0].res_norte;
                                totalRes2 += conteo.count_residencial_zona_2;
                            }
                            if(conteo.count_residencial_zona_3 > 0){
                                conteoZonaRes3 += conteo.count_residencial_zona_3;
                                totalPrecioPorFilaRes3 += conteo.count_residencial_zona_3 * preciosParametro[0].res_cauca;
                                totalRes3 += conteo.count_residencial_zona_3;
                            }
                            promedioFila += conteo.count_residencial_zona_1 + conteo.count_residencial_zona_2 + conteo.count_residencial_zona_3;

                            // zona comercial----------------------------------------------------
                            if(conteo.count_comercial_zona_1 > 0){
                                conteoZonaCom1 += conteo.count_comercial_zona_1;
                                totalPrecioPorFilaCom1 += conteo.count_comercial_zona_1 * preciosParametro[0].com_metro;
                                totalCom1 += conteo.count_comercial_zona_1;
                            }
                            if(conteo.count_comercial_zona_2 > 0){
                                conteoZonaCom2 += conteo.count_comercial_zona_2;
                                totalPrecioPorFilaCom2 += conteo.count_comercial_zona_2 * preciosParametro[0].com_norte;
                                totalCom2 += conteo.count_comercial_zona_2;
                            }
                            if(conteo.count_comercial_zona_3 > 0){
                                conteoZonaCom3 += conteo.count_comercial_zona_3;
                                totalPrecioPorFilaCom3 += conteo.count_comercial_zona_3 * preciosParametro[0].com_cauca;
                                totalCom3 += conteo.count_comercial_zona_3;
                            }
                            promedioFila += conteo.count_comercial_zona_1 + conteo.count_comercial_zona_2 + conteo.count_comercial_zona_3;
                        });

                        
                        // realizamos la suma de las columnas por fila
                        sumaTotalFila = totalPrecioPorFilaRes1 + 
                                        totalPrecioPorFilaRes2 + 
                                        totalPrecioPorFilaRes3 + 
                                        totalPrecioPorFilaCom1 + 
                                        totalPrecioPorFilaCom2 + 
                                        totalPrecioPorFilaCom3;
    
                        sumaGeneral += sumaTotalFila;
                        sumaTotalColumnaRes1 += totalPrecioPorFilaRes1;
                        sumaTotalColumnaRes2 += totalPrecioPorFilaRes2;
                        sumaTotalColumnaRes3 += totalPrecioPorFilaRes3;
                        sumaTotalColumnaCom1 += totalPrecioPorFilaCom1;
                        sumaTotalColumnaCom2 += totalPrecioPorFilaCom2;
                        sumaTotalColumnaCom3 += totalPrecioPorFilaCom3;
                    
                        // Calcular promedio
                        sumaPromedioFila = promedioFila / totalInspectoresFila;

                        sumaPromedioFila = sumaPromedioFila.toString();
                    
                        let promedio = sumaPromedioFila.split('.');
                        let promedioFinal = '';
                        if (promedio[1]) {
                            let decimales1 = promedio[1].charAt(0);
                            let decimales2 = promedio[1].charAt(1);
                            promedioFinal = promedio[0] + "." + decimales1+""+decimales2;
                        } else {
                            promedioFinal = promedio[0];
                        }
    
                        if(isNaN(promedioFinal)){
                            promedioFinal = '0.0';
                        }
                    
                        cantidadEjecutadaAnterior += promedioFila;
                        valorEjecutadoAnterior += sumaTotalFila;
                    
                        // Añadir la fila con los conteos por zona y el total de precios por fila
                        filasData.push([...fila,
                            conteoZonaRes1,
                            conteoZonaRes2,
                            conteoZonaRes3,
                            conteoZonaCom1,
                            conteoZonaCom2,
                            conteoZonaCom3,
                            formatter.format(totalPrecioPorFilaRes1), 
                            formatter.format(totalPrecioPorFilaRes2), 
                            formatter.format(totalPrecioPorFilaRes3),
                            formatter.format(totalPrecioPorFilaCom1),
                            formatter.format(totalPrecioPorFilaCom2),
                            formatter.format(totalPrecioPorFilaCom3),
                            formatter.format(sumaTotalFila),
                            totalInspectoresFila,
                            promedioFinal,
                            cantidadEjecutadaAnterior,
                            "",
                            "",
                            formatter.format(0),
                            '% '+0,
                            formatter.format(valorEjecutadoAnterior),
                            '% '+0,
                        ]);
                    });
    
                    // Añadir la fila de totales al final
                    let filaTotal = ['TOTAL',
                                        totalRes1,
                                        totalRes2,
                                        totalRes3,
                                        totalCom1,
                                        totalCom2,
                                        totalCom3,
                                        formatter.format(sumaTotalColumnaRes1),
                                        formatter.format(sumaTotalColumnaRes2),
                                        formatter.format(sumaTotalColumnaRes3),
                                        formatter.format(sumaTotalColumnaCom1),
                                        formatter.format(sumaTotalColumnaCom2),
                                        formatter.format(sumaTotalColumnaCom3),
                                        formatter.format(sumaGeneral),
                                        "",
                                        "",
                                        cantidadEjecutadaAnterior,
                                        "",
                                        "",
                                        formatter.format(0),
                                        '% '+0,
                                        formatter.format(valorEjecutadoAnterior),
                                        '% '+0]; 
    
                    filasData.push(filaTotal);
                    
                    let firstColumnData = [];
                    let celdasActualizadas = false;
    
                    hot.updateSettings({
                        data: filasData,
                        afterRender: function() {
                            if (celdasActualizadas) return; // Evita ejecuciones repetidas
                            
                            // Actualiza el array de la primera columna antes de renderizar
                            firstColumnData = filasData.map(row => row[0]);
                    
                            // Validamos que el array 'firstColumnData' y 'nomina' tengan datos
                            if (firstColumnData.length === 0 || nomina.length === 0) {
                                return; // Si no hay datos, no hacemos nada
                            }
                    
                            hot.suspendRender(); // Desactiva temporalmente el renderizado
                    
                            // Recorremos el array 'nomina'
                            nomina.forEach(item => {
                                for (let i = 0; i < 31; i++) {
                                    let fecha = firstColumnData[i]; // Obtenemos la fecha desde la columna 0
                                    if (fecha === item.fechaNomina) {
                                        // Si la fecha coincide, actualizamos la cantidad en la columna 18
                                        hot.setDataAtCell(i, 18, item.proyeccion);
                                    }
                                }
                            });
                            hot.resumeRender(); // Vuelve a activar el renderizado
                            celdasActualizadas = true; // Indicamos que ya se actualizaron las celdas
                        },
                        cells: function (row, col) {
                            const totalRowIndex = filasData.length; // Ajustar para obtener el índice de la última fila
    
                            // Estilo y no editable para la última fila
                            if (row === totalRowIndex - 1) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#3A3838'; // Color de fondo para la última fila
                                        td.style.color = 'white'; // Color de texto para la última fila
                                    },
                                    readOnly: true // Hacer la última fila no editable
                                };
                            }
                        
                            // Aplicar colores a los días festivos
                            if (diasFestivos.includes(firstColumnData[row])) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#00B0F0';
                                        td.style.color = col === 0 ? 'black' : 'white';
                                    },
                                    readOnly: col === 18 ? false : true // Hacer la columna 18 editable
                                };
                            }
                        
                            // Aplicar colores a los sábados
                            if (diasSabados.includes(firstColumnData[row])) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#00FF00';
                                        td.style.color = col === 0 ? 'black' : 'white';
                                    },
                                    readOnly: col === 18 ? false : true // Hacer la columna 18 editable
                                };
                            }
                        
                            // Estilo para otras filas
                            if ([1, 2, 3, 7, 8, 9].includes(col)) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#002060';
                                        td.style.color = 'white';
                                    },
                                    readOnly: col === 18 ? false : true // Hacer la columna 18 editable
                                };
                            }
                        
                            if ([4, 5, 6, 10, 11, 12].includes(col)) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#34A30D';
                                        td.style.color = 'white';
                                    },
                                    readOnly: col === 18 ? false : true // Hacer la columna 18 editable
                                };
                            }
                        
                            if ([13, 14].includes(col)) {
                                return {
                                    renderer: function (instance, td, row, col, prop, value, cellProperties) {
                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                        td.style.backgroundColor = '#000000';
                                        td.style.color = 'white';
                                    },
                                    readOnly: col === 18 ? false : true // Hacer la columna 18 editable
                                };
                            }
                        
                            // Por defecto, la columna 18 es editable
                            return col === 18 ? { readOnly: false } : {};
                        },
                        
                        afterOnCellMouseDown: function(event, coords, TD) {
                            if (coords.col === 18) {
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
                     
                    let totalMetroRes = totalRes1;
                    let totalNorteRes = totalRes2;
                    let totalCaucaRes = totalRes3;
    
                    let totalMetroCom = totalCom1;
                    let totalNorteCom = totalCom2;
                    let totalCaucaCom = totalCom3;
    
                    let totalFacMetroRes = totalMetroRes * preciosParametro[0].res_metro;
                    let totalFacNorteRes = totalNorteRes * preciosParametro[0].res_norte;
                    let totalFacCaucaRes = totalCaucaRes * preciosParametro[0].res_cauca;
                    let totalFacMetroCom = totalMetroCom * preciosParametro[0].com_metro;
                    let totalFacNorteCom = totalNorteCom * preciosParametro[0].com_norte;
                    let totalFacCaucaCom = totalCaucaCom * preciosParametro[0].com_cauca;
    
                    handDos.updateSettings({
                        data: [
                            ['PREVIA/PERIODICA (metropolitana residencial)', formatter.format(preciosParametro[0].res_metro), totalMetroRes, formatter.format(totalFacMetroRes), 0+" %"],
                            ['PREVIA/PERIODICA (Zona norte residencial)', formatter.format(preciosParametro[0].res_norte), totalNorteRes, formatter.format(totalFacNorteRes), 0+" %"],
                            ['PREVIA/PERIODICA (Zona Buenaventura y Cauca residencial)', formatter.format(preciosParametro[0].res_cauca), totalCaucaRes, formatter.format(totalFacCaucaRes), 0+" %"],
                            ['PREVIA/PERIODICA (Zona metropolitana comercial)', formatter.format(preciosParametro[0].com_metro), totalMetroCom, formatter.format(totalFacMetroCom), 0+" %"],
                            ['PREVIA/PERIÓDICA (Zona norte comercial)', formatter.format(preciosParametro[0].com_norte), totalNorteCom, formatter.format(totalFacNorteCom), 0+" %"],
                            ['PREVIA /PERIÓDICA (Zona Buenaventura y Cauca comercial)', formatter.format(preciosParametro[0].com_cauca), totalCaucaCom, formatter.format(totalFacCaucaCom), 0+" %"],
                            ['INSPECCION INDUSTRIAL', formatter.format(preciosParametro[0].inspeccion_industrial), valorInspeccionIndustrial, 0+" %"],
                            ['', '', '', '', '']
                        ],
                        cells: function (row, col) {
                            if (col === 2 && row === 6) {
                                return {
                                    readOnly: false
                                };
                            } else {
                                return {
                                    readOnly: true
                                };
                            }
                        },
                        afterOnCellMouseDown: function(event, coords, TD) {
                            if (coords.col === 2) {
                                setTimeout(function() {
                                    const handDosTextarea = document.querySelector('textarea.handsontableInput');
                                    if (handDosTextarea) {
                                        handDosTextarea.removeAttribute('aria-hidden');
                                        handDosTextarea.setAttribute('tabindex', '0');
                                    }
                                }, 1);
                            }
                        },
                    });
                    // Cargar los datos en la tabla
                    hot.loadData(filasData);
                loaderDiv.style.display = 'none';
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un problema al cargar los datos.');
                loaderDiv.style.display = 'none';
            });
    };

    document.getElementById('nomina-selector').addEventListener('change', function () {
        let url = this.value;
        let headers;
        let anio = document.getElementById('nominaSelectorAnio').value; 
    
        if (!url) {
            console.error('No se ha seleccionado una opción válida.');
            return;
        }else{
            // retiramos el display none del div
            container.style.display = 'block';
            containerResumen.style.display = 'block';
            loaderDiv.style.display = 'block';
        }

        url += `?anio=${anio}`;

        headers = headersMeses;
    
        loadData(url, headers);
    });

    document.getElementById('nominaSelectorAnio').addEventListener('change', function () {
        nominaSelectorMes.style.display = 'block';
    });

    let sumaValorProyectado = 0;
    let contador = 0;
    let diasFestivosRender = [];
    let diasSabadosRender = [];
    let valoresPrevios = [];

    hot.addHook('afterChange', function (changes, source) {
        if (source === 'edit') {
            let totalRows = hot.countRows();
            let ultimaFila = totalRows - 1;
    
            changes.forEach(([row, col, oldValue, newValue]) => {
                if(col === 18){
                    if (row === ultimaFila) {
                        return;
                    }
                    if(newValue === "" || newValue === 0 || newValue === "0"){
                        alert('Por favor, ingrese un valor mayor a 0 en el campo CANTIDAD PROYECTADA.');
                        hot.setDataAtCell(row, 17, "");
                        hot.setDataAtCell(row, 19, formatter.format(0));
                    }else{
                        if (!regex(newValue)) {
                            alert('Por favor, ingrese solo números en el campo CANTIDAD PROYECTADA.');
                            hot.setDataAtCell(row, 17, ""); 
                            hot.setDataAtCell(row, 19, formatter.format(0));
                            const callback = function() {
                                hot.setCellMeta(row, col, 'className', 'cell-error');
                                hot.render();
                            };
                            callback();
                        } else {
                            let nuevaCant = parseInt(newValue);
                            let valorProyectado = nuevaCant * 38990;
            
                            let url = $('#guardarNomina').val();
                            let tokenNomina = $('#tokenNomina').val();
                            let fechaFila = hot.getDataAtCell(row, 0);
            
                            if (valoresPrevios[row] !== nuevaCant) {
                                valoresPrevios[row] = nuevaCant;
                                $.post({
                                    url: url,
                                    data: {
                                        _token: tokenNomina,
                                        nuevaCant: nuevaCant,
                                        fechaFila: fechaFila
                                    },
                                    success: function (response) {
                                        if(response == 2){
                                            Swal.fire({
                                                title: 'Error',
                                                text: 'No se pudo cambiar la cantidad',
                                                icon: 'error'
                                            });
                                        }
                                    },
                                    error: function (xhr, status, error) {
                                        Swal.fire({
                                            title: 'Error',
                                            text: 'No se pudo cambiar la cantidad',
                                            icon: 'error'
                                        });
                                    },
                                });
                            }
            
                            if (nuevaCant === 0 || isNaN(nuevaCant) || nuevaCant === "") {
                                hot.setDataAtCell(row, 17, "");
                                hot.setDataAtCell(row, 19, formatter.format(0));
                                let arrayValoresColumna19 = [];
                                for (let i = 0; i < ultimaFila; i++) {
                                    let valor = hot.getDataAtCell(i, 19);
                                    let valorString = valor.split('$');
                                    valorString = valorString[1].trim();
                                    valorString = valorString.split('.');
                                    let valorFinal = valorString.join('');
                                    valorFinal = parseInt(valorFinal);
                                    if(valorFinal !== 0){
                                        arrayValoresColumna19.push(valorFinal);
                                    }
                                }
                                let sumaValorProyectado = arrayValoresColumna19[arrayValoresColumna19.length - 1];
                                if(isNaN(sumaValorProyectado)){
                                    sumaValorProyectado = 0;
                                }
                                hot.setDataAtCell(ultimaFila, 19, formatter.format(sumaValorProyectado));
                                if (oldValue !== null && oldValue !== undefined && oldValue !== "" && !isNaN(parseFloat(oldValue))) {
                                    contador--;
                                }
                            } else {
                                contador = 0;
                                for (let i = 0; i < ultimaFila; i++) {
                                    let valor = hot.getDataAtCell(i, 18);
                                    if (i === row) {
                                        valor = nuevaCant;
                                    }
                                    if (valor !== null && valor !== undefined && valor !== "" && !isNaN(valor)) {
                                        contador++;
                                    }
                                }

                                let arrayFechas = [];
                                let dataLoaded = false;
                                let cantEjecutada = parseFloat(hot.getDataAtCell(row, 16));
                                let diferencia = cantEjecutada - nuevaCant;
            
                                sumaValorProyectado = valorProyectado; 
                                hot.setDataAtCell(row, 17, diferencia); 
                                hot.setDataAtCell(row, 19, formatter.format(valorProyectado));
                                hot.setDataAtCell(ultimaFila, 18, nuevaCant);
                                hot.setDataAtCell(ultimaFila, 19, formatter.format(sumaValorProyectado));
            
                                if(contador == ultimaFila){

                                    loaderTablaDiario.style.display = 'block';
                                    container.style.opacity = '0.5';

                                    setTimeout(function() {
                                        let porcentajeTotal = "";
                                        let porcentajeTotal2 = "";
    
                                        let total = hot.getDataAtCell(ultimaFila, 19);
                                        let totalString = total.split('$')
                                        totalString = totalString[1].trim();
                                        totalString = totalString.split('.');
                                        let resultadoFinal = totalString.join('')
                                        resultadoFinal = parseInt(resultadoFinal);
    
                                        for (let i = 0; i < ultimaFila; i++) {
                                            let valor = hot.getDataAtCell(i, 19);
                                            let valorString = valor.split('$')
                                            valorString = valorString[1].trim();
                                            valorString = valorString.split('.');
                                            let valorFinal = valorString.join('')
                                            valorFinal = parseInt(valorFinal);
                
                                            let porcentaje = (valorFinal / resultadoFinal) * 100;
                                            let porcentajeString = porcentaje.toString().split('.')[0];
                                            porcentajeTotal = porcentajeString
                                            hot.setDataAtCell(i, 20, '% ' + porcentajeString);
                                        }
                                        
                                        hot.setDataAtCell(ultimaFila, 20, '% ' + porcentajeTotal);
                
                                        for (let i = 0; i < ultimaFila; i++) {
                                            let valor = hot.getDataAtCell(i, 21);
                                            let valorString = valor.split('$')
                                            valorString = valorString[1].trim();
                                            valorString = valorString.split('.');
                                            let valorFinal = valorString.join('')
                                            valorFinal = parseInt(valorFinal);
                
                                            let porcentaje = (valorFinal / resultadoFinal) * 100;
                                            let porcentajeString = porcentaje.toString().split('.')[0];
                                            porcentajeTotal2 = porcentajeString
                                            hot.setDataAtCell(i, 22, '% ' + porcentajeString);
                                        }
    
                                        hot.setDataAtCell(ultimaFila, 22, '% ' + porcentajeTotal2);

                                        loaderTablaDiario.style.display = 'none';
                                        container.style.opacity = '1';
                                    },3000)
                                }

                                hot.updateSettings({
                                    afterRender: function () {
                                        if (!dataLoaded) {
                                            arrayFechas = [];
                                            const totalRows = hot.countRows();
                                            for (let i = 0; i < totalRows; i++) {
                                                let fecha = hot.getDataAtCell(i, 0);
                                                arrayFechas.push(fecha);
                                            }
                                            dataLoaded = true; 
                                            hot.updateSettings({
                                                cells: function (rowIndex, colIndex) {
                                                    let cellProperties = {};
                                                    
                                                    // Verificar si es la última fila
                                                    if (rowIndex === ultimaFila && colIndex === 18) {
                                                        cellProperties.readOnly = true; // Hacer la última fila en la columna 18 no editable
                                                    } else if (colIndex === 18) {
                                                        cellProperties.readOnly = false; // Hacer las demás filas en la columna 18 editables
                                                    }
                                
                                                    // Definir renderer
                                                    cellProperties.renderer = function (instance, td, row, col, prop, value, cellProperties) {
                                                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                                
                                                        // Mantener el color de la última fila en la columna 17
                                                        if (rowIndex === ultimaFila && colIndex === 17) {
                                                            td.style.backgroundColor = '#3A3838';
                                                            td.style.color = 'white';
                                                            return;
                                                        }
                                
                                                        if (colIndex === 17) {
                                                            let cantEjecutada = parseFloat(hot.getDataAtCell(row, 16));
                                                            let nuevaCant = parseFloat(hot.getDataAtCell(row, 18));
                                                            let diferencia = cantEjecutada - nuevaCant;
                                
                                                            if (diferencia < 0) {
                                                                td.style.backgroundColor = '#FFC7CE';
                                                                td.style.color = '#A90046';
                                                                return; 
                                                            } else if (diferencia > 0) {
                                                                td.style.backgroundColor = '#00B050';
                                                                td.style.color = 'white';
                                                                return;
                                                            } else if (diferencia === 0) {
                                                                td.style.backgroundColor = '#00B0F0';
                                                                td.style.color = 'black';
                                                                return;
                                                            }
                                                        }
                                
                                                        // Aplicar color para días festivos
                                                        if (diasFestivosRender.includes(arrayFechas[rowIndex])) {
                                                            td.style.backgroundColor = '#00B0F0'; 
                                                            td.style.color = (col === 0) ? 'black' : 'white';
                                                            return; 
                                                        }
                                
                                                        // Aplicar color para sábados
                                                        if (diasSabadosRender.includes(arrayFechas[rowIndex])) {
                                                            td.style.backgroundColor = '#00FF00'; 
                                                            td.style.color = (col === 0) ? 'black' : 'white';
                                                            return; 
                                                        }
                                
                                                        // Estilos por columna
                                                        if ([1, 2, 3, 7, 8, 9].includes(colIndex)) {
                                                            td.style.backgroundColor = '#002060';
                                                            td.style.color = 'white';
                                                        } else if ([4, 5, 6, 10, 11, 12].includes(colIndex)) {
                                                            td.style.backgroundColor = '#34A30D';
                                                            td.style.color = 'white';
                                                        } else if ([13, 14].includes(colIndex)) {
                                                            td.style.backgroundColor = '#000000';
                                                            td.style.color = 'white';
                                                        }
                                
                                                        // Aplicar estilo para la última fila
                                                        if (rowIndex === ultimaFila) {
                                                            td.style.backgroundColor = '#3A3838';
                                                            td.style.color = 'white';
                                                            td.readOnly = true;
                                                        }
                                                    };
                                
                                                    return cellProperties;
                                                }
                                            });
                                        }
                                    }
                                });
                            }
                        }
                    }
                }
            });
        }
    });

    let totalFinalInspeccion = 0;
    handDos.addHook('afterChange', function (changes, source) {
        if (source === 'programmatic') {
            return;
        }

        let fechaFila = hot.getDataAtCell(0, 0);
        let url = $('#guardarInspeccion').val();
        let tokenInspeccion= $('#tokenNomina').val();
        let totalFilas = handDos.countRows();
        let ultimaFila = totalFilas - 2;
        let porcentajeTotal = 0;
        let totalInspeccion = 0;
        let totalFinal = 0;    
    
        let cantidadInspeccion = handDos.getDataAtCell(ultimaFila, 2);
        cantidadInspeccion = parseInt(cantidadInspeccion);
    
        let valorInspeccionString = handDos.getDataAtCell(ultimaFila, 1);
        valorInspeccionString = valorInspeccionString.split('$');
        valorInspeccionString = valorInspeccionString[1].trim();
        valorInspeccionString = valorInspeccionString.split('.');
        let valorInspeccion = valorInspeccionString.join('');
        valorInspeccion = parseInt(valorInspeccion);
        
        totalInspeccion = valorInspeccion * cantidadInspeccion;
        if(isNaN(totalInspeccion)){
            totalInspeccion = 0;
        }

        handDos.setDataAtCell(ultimaFila, 3, formatter.format(totalInspeccion), 'programmatic');
        
        for(let i = 0; i <= ultimaFila; i++){
            let totalFila = handDos.getDataAtCell(i, 3);
            let totalFilaString = totalFila.split('$');
            totalFilaString = totalFilaString[1].trim();
            totalFilaString = totalFilaString.split('.');
            let totalFilaFinal = totalFilaString.join('');
            totalFilaFinal = parseInt(totalFilaFinal);
            totalFinal += totalFilaFinal;
        }
      
        handDos.setDataAtCell(ultimaFila+1, 3, formatter.format(totalFinal), 'programmatic');

        totalFinalInspeccion = totalFinal;

        $.post({
            url: url,
            data: {
                _token: tokenInspeccion,
                totalFinal: totalFinal,
                valor: valorInspeccionIndustrial,
                fechaFila: fechaFila
            },
            success: function (response) {
                
            },
            error: function (xhr, status, error) {
                Swal.fire({
                    type: 'error',
                    title: 'Error al guardar',
                    text: error,
                })
            }
        })
        valorInspeccionIndustrial = "";
        let porcentaje = 0;
        for(let i = 0; i <= ultimaFila; i++){
            let totalFila = handDos.getDataAtCell(i, 3);
            let totalFilaString = totalFila.split('$');
            totalFilaString = totalFilaString[1].trim();
            totalFilaString = totalFilaString.split('.');
            let totalFilaFinal = totalFilaString.join('');
            totalFilaFinal = parseInt(totalFilaFinal);

            porcentaje = (totalFilaFinal / totalFinal) * 100;
            porcentajeTotal += porcentaje;
            let porcentajeString = porcentaje.toString().split('.')[0];
            let porcentajeString1 = porcentaje.toString().split('.')[1];
            let porcentajeFinalFila = "";
            let porcentajeString2 = "";
            if(porcentajeString1 != undefined){
                porcentajeString2 = porcentajeString1.charAt(0);
                porcentajeFinalFila = porcentajeString + '.' + porcentajeString2
            }else{
                porcentajeFinalFila = porcentajeString = porcentaje.toString().split('.')[0];
            }
            if(isNaN(porcentajeFinalFila)){
                porcentajeFinalFila = 0;
            }
            handDos.setDataAtCell(i, 4,  porcentajeFinalFila+' % ','programmatic');
        }

        porcentajeTotal = porcentajeTotal.toString().split('.')[0];
        let porcentajeTotalString = porcentajeTotal.toString().split('.')[1];
        let porcentajeTotal2 = "";
        if(porcentajeTotalString != undefined){
            porcentajeTotal2 = porcentajeTotalString.charAt(0);
            porcentajeTotal = porcentajeTotal + '.' + porcentajeTotal2
        }else{
            porcentajeTotal = porcentajeTotal.toString().split('.')[0];
        }
        if(isNaN(porcentajeTotal)){
            porcentajeTotal = 0;
        }
        handDos.setDataAtCell(ultimaFila+1, 4,  porcentajeTotal+' % ','programmatic');

        let cantidadTotal = 0;
        for(let i = 0; i <= ultimaFila; i++){
            let cantidadFila = handDos.getDataAtCell(i, 2);
            if(cantidadFila != "" && regex(cantidadFila)){
                cantidadTotal += parseInt(cantidadFila);
            }
        }
        handDos.setDataAtCell(ultimaFila+1, 2,  cantidadTotal, 'programmatic');

        handDos.updateSettings({
            cells: function (row, col) {
                let totalFilas = handDos.countRows();
                let ultimaFila = totalFilas - 2;  // Penúltima fila

                if (col === 2 && row === 6) {
                    return {
                        renderer: function (instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.backgroundColor = '#FFC000';
                            td.style.color = 'white';
                        },
                        readOnly: false
                    };
                }

                if (row === ultimaFila && (col === 1 || col === 2 || col === 3)) {
                    return {
                        renderer: function (instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.backgroundColor = '#FFC000';
                            td.style.color = 'white';
                        },
                    };
                }

                if ((row === 0 || row === 1 || row === 2) && (col === 1 || col === 2 || col === 3)) {
                    return {
                        renderer: function (instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.backgroundColor = '#002060';
                            td.style.color = 'white';
                        },
                    };
                }

                if ((row === 3 || row === 4 || row === 5) && (col === 1 || col === 2 || col === 3)) {
                    return {
                        renderer: function (instance, td, row, col, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.backgroundColor = '#34A30D';
                            td.style.color = 'white';
                        },
                    };
                }
            }
        });
    });

    handDos.addHook('afterChange', function (changes, source) {
        if (source === 'edit') {
            changes.forEach(([row, col, oldValue, newValue]) => {
                if (col === 2 && row === 6) {
                    if (!regex(newValue)) {
                        alert('Por favor, ingrese solo números');
                    }else{
                        let fechaFila = hot.getDataAtCell(0, 0);
                        let valor = parseInt(newValue)
                        let url = $('#guardarInspeccion').val();
                        let tokenInspeccion= $('#tokenNomina').val();
                        $.post({
                            url: url,
                            data: {
                                _token: tokenInspeccion,
                                totalFinal: totalFinalInspeccion,
                                fechaFila: fechaFila,
                                valor: valor
                            },
                            success: function (response) {
                                
                            }
                        })
                    }
                }
            });          
        }
    });

    $(document).on('click', '#btnExportarDiraio', function() {
        // Obtener datos de la primera tabla (handsontable)
        let headers1 = hot.getColHeader(); // Encabezados de la primera tabla
        let datos1 = hot.getData(); // Datos de la primera tabla
        let datos2 = handDos.getData(); // Datos de la segunda tabla

        if(datos1.length > 0 && datos2.length > 0){
            // Reemplazar <br> por un espacio en los encabezados
            headers1 = headers1.map(header => header.replace(/<br>/g, ' '));
        
            // Añadir encabezados y datos de la primera tabla
            datos1.unshift(headers1);
        
            // Obtener datos de la segunda tabla (handDos)
        
            // Combinar ambas tablas
            const datosCombinados = datos1.concat([[]], datos2); // Añadir una fila vacía entre las tablas
        
            // Crear una nueva hoja de trabajo
            const worksheet = XLSX.utils.aoa_to_sheet(datosCombinados);
        
            // Crear un nuevo libro de trabajo y añadir la hoja
            const workbook = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte');
        
            // Exportar el archivo Excel
            XLSX.writeFile(workbook, 'Reporte_de_produccion_diario.xlsx');
        }else{
            Swal.fire({
                title: 'Advertencia',
                text: 'Por favor seleccione una opcion antes de exportar',
                icon: 'warning'
            });
        }
    });

    function regex(int){
        return /^\d*$/.test(int);
    }
});