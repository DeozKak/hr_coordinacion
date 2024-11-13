$(document).ready(function(){

    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    });

    $(document).on('click', '#generarReporte', function(){
        let mesAnio = $('#mesAnio').val();
        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')
        let loaderNomina = $('.loaderDivNomina')
        let tablaNomina = $('#tablaNomina')
        let tablaCostosProyecto = $('#tablaCostosProyecto')
        let loaderTablaNomina = $('.loaderTablaNomina') 
        let loaderTablaCostosProyecto = $('.loaderTablaCostosProyecto')
        let [year, month] = mesAnio.split('-');
        // Creamos un objeto de fecha con el primer día del mes actual
        let fechaActual = new Date(year, month - 1); // Mes actual (restamos 1 porque los meses en JS van de 0 a 11)
        // Obtenemos el nombre del mes actual en español
        let nombreMesActual = fechaActual.toLocaleString('es-ES', { month: 'long' });
        // Para obtener el mes anterior, restamos 1 al mes actual
        fechaActual.setMonth(fechaActual.getMonth() - 1); // Restamos 1 mes
        // Obtenemos el nombre del mes anterior
        let nombreMesAnterior = fechaActual.toLocaleString('es-ES', { month: 'long' });
        
        if(mesAnio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Debe seleccionar una fecha'
            });
            return
        }else{
            loaderNomina.show()
            $.post({
                url:url,
                data:{
                    mesAnio: mesAnio,
                    _token: token
                },
                dataType: 'json',
                success:function(response){
                    if(response.length > 0){
                        loaderNomina.hide()
                        
                        if (window.handNomina && !window.handNomina.isDestroyed) {
                            window.handNomina.destroy();
                            window.handNomina = null;
                        }

                        if (window.handCostosProyecto && !window.handCostosProyecto.isDestroyed) {
                            window.handCostosProyecto.destroy();
                            window.handCostosProyecto = null;
                        }
    
                        $('.reporteNominaTitulo .card-title').text('Reporte de nómina Corte: '+nombreMesAnterior+'-'+nombreMesActual+' '+year)
                        $('.cardReporteProduccion').show()
    
                        let data = []
                        let multasArray = []
                        let inspectoresAprendiz = []
                        let arraySalarioAux = []
    
                        if(response.length > 0){
                            data = response[0].data.produccionInspector
                            multasArray = response[0].multas
                            inspectoresAprendiz = response[0].inspectores
                            arraySalarioAux = response[0].salariosAux
                        }

                        // TABLA COSTOS PROYECTO --------------------------------------------------
                        window.handCostosProyecto = new Handsontable(tablaCostosProyecto[0], {
                            data: [],
                            colHeaders: [],
                            readOnly: true,
                            height: 'auto',
                            licenseKey: 'non-commercial-and-evaluation',
                        })

                        // TABLA NOMINA --------------------------------------------------
                        window.handNomina = new Handsontable(tablaNomina[0], {
                            data: [],
                            colHeaders: [],
                            readOnly: true,
                            height: 'auto',
                            licenseKey: 'non-commercial-and-evaluation',
                        })
    
                        let arrayNomina = data.map(item => {
                            
                            let bonificacion = 0;
                            let diferencia = 0;
                            let copas = 0;
    
                            if(item.total > 199 && item.total < 250){
                                copas = 180000
                            }else if(item.total >= 250 && item.total < 300){
                                copas = 330000
                            }else if(item.total >= 300){
                                copas = 500000
                            }else{
                                copas = 0
                            }
    
                            if(item.total > 180){
                                diferencia = item.total - 180;
                                bonificacion = diferencia * 13000;
                            }else{
                                bonificacion = 0;
                            }
                            
                            let totalBonitifacion = Math.trunc(bonificacion + copas);
    
                            let multas = 0;
                            let rodamiento = 325000;
    
                            let bonoComecial = 0;
                            let totalNomina = 0;
    
                            if(multasArray.length > 0){
                                multasArray.forEach(multa => {
                                    if(multa.cc_operario == item.cedula){
                                        multas = multa.multa
                                        rodamiento = multa.rodamiento
                                    }
                                })
                            }
    
                            bonoComecial = Math.trunc(totalBonitifacion - multas)
    
                            totalNomina = Math.trunc(bonoComecial + rodamiento)
    
                            return [
                                item.cedula,
                                item.nombres,
                                item.total,
                                formatter.format(bonificacion),
                                formatter.format(copas),
                                totalBonitifacion,
                                multas,
                                bonoComecial,
                                rodamiento,
                                totalNomina
                            ]
                        });
    
                        arrayNomina.push(['TOTAL', '', '', '', '', '', '', '', '', '']);
    
                        // Actualizar los datos en la tabla Handsontable
                        handNomina.updateSettings({
                            data: arrayNomina,
                            colHeaders: [
                                'CC',
                                'INSPECTORES CONTRATO CALI',
                                'PRODUCCIÓN',
                                'BONIFICACION > 180',
                                'COPAS',
                                'TOTAL BONIFICACION',
                                'MULTAS',
                                'BONO COMERCIAL',
                                'VALOR RODAMIENTO',
                                'TOTAL'
                            ],
                        });
                        
                        let totalFilas = handNomina.countRows() - 1;

                        let totalBonifSum = 0;
                        let totalMultasSum = 0;
                        let totalBonoComercialSum = 0;
                        let totalRodamientoSum = 0;
                        let totalNominaSum = 0;

                        // sumamos las columnas 
                        for(let i = 0; i < totalFilas; i++ ){
                            totalBonifSum += parseFloat(handNomina.getDataAtCell(i, 5)) || 0;
                            totalMultasSum += parseFloat(handNomina.getDataAtCell(i, 6)) || 0;
                            totalBonoComercialSum += parseFloat(handNomina.getDataAtCell(i, 7)) || 0;
                            totalRodamientoSum += parseFloat(handNomina.getDataAtCell(i, 8)) || 0;
                            totalNominaSum += parseFloat(handNomina.getDataAtCell(i, 9)) || 0;
                        }

                        handNomina.setDataAtCell(totalFilas, 5, totalBonifSum);
                        handNomina.setDataAtCell(totalFilas, 6, totalMultasSum);
                        handNomina.setDataAtCell(totalFilas, 7, totalBonoComercialSum);
                        handNomina.setDataAtCell(totalFilas, 8, totalRodamientoSum);
                        handNomina.setDataAtCell(totalFilas, 9, totalNominaSum);
            
                        handNomina.updateSettings({
                            cells: function (rowIndex, colIndex) {
                                let cellProperties = {};
                            
                                // Columnas con formato numérico y pattern de dinero
                                const columnasNumericas = [5, 6, 7, 8, 9];
                            
                                // Asignar clase según la columna
                                const classMap = {
                                    4: 'cell-copas',
                                    5: 'cell-total-bonif',
                                    6: 'cell-multas',
                                    7: 'cell-bono-comercial',
                                    8: 'cell-rodamiento'
                                };
                            
                                // Asignar formato numérico en las columnas específicas
                                if (columnasNumericas.includes(colIndex)) {
                                    cellProperties.type = 'numeric';
                                    cellProperties.numericFormat = {
                                        pattern: '$0,0',
                                        culture: 'en-US'
                                    };
                                }
                            
                                // Asignar clase a las celdas basadas en colIndex
                                if (classMap[colIndex]) {
                                    cellProperties.className = classMap[colIndex];
                                }
                            
                                // Columna 6 y 8 son editables excepto en la fila total
                                if ((colIndex === 6 || colIndex === 8) && rowIndex !== totalFilas) {
                                    cellProperties.readOnly = false;
                                } 
                                // Columnas 5, 7, 9 son solo de lectura excepto en la fila total
                                else if ((colIndex === 5 || colIndex === 7 || colIndex === 9) && rowIndex !== totalFilas) {
                                    cellProperties.readOnly = true;
                                }
                            
                                // Todas las columnas son solo de lectura en la fila total
                                if (rowIndex === totalFilas && columnasNumericas.includes(colIndex)) {
                                    cellProperties.readOnly = true;
                                }
                                
                                if(colIndex >=0 && colIndex <= 3 || colIndex === 9){
                                    cellProperties.className = 'celdasGeneral';
                                }
                            
                                return cellProperties;
                            },
                            afterOnCellMouseDown: function(event, coords, TD) {
                                if (coords.col === 6 || coords.col === 8) {
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
    
                        handNomina.addHook('afterChange', function(changes, source) {
                            if (source === 'programmatic') {
                                return;
                            }
                            changes.forEach(([row, col, oldValue, newValue]) => {
                                if (!regex(newValue)) {
                                    alert('Por favor, ingrese solo números en el campo.');
                                    return;
                                } else {
                                    // Manejo del valor de nueva entrada
                                    newValue = parseInt(newValue) || 0; // Asignar 0 si es NaN
                                    // asignamos el nuevo valor a la celda
                                    handNomina.setDataAtCell(row, col, newValue, 'programmatic');
                        
                                    if ((col === 6 || col === 8) && row !== totalFilas) {
                                        let ccOperario = handNomina.getDataAtCell(row, 0);
                                        let token = $('#tokenReporteNomina').val();
                                        let url = $('#guardarMultaRodamiento').val();
                                        let multa = null;
                                        let rodamiento = null;
                        
                                        if (col === 6) {
                                            multa = newValue; // Se toma el nuevo valor directamente
                                        } else if (col === 8) {
                                            rodamiento = newValue; // Se toma el nuevo valor directamente
                                        }
                                        tablaNomina[0].style.opacity = '0.5';
                                        tablaCostosProyecto[0].style.opacity = '0.5';
                                        loaderTablaNomina.show();
                                        loaderTablaCostosProyecto.show();
                                        // Enviar los datos al servidor
                                        $.post({
                                            url: url,
                                            data: {
                                                ccOperario: ccOperario,
                                                multa: multa,
                                                rodamiento: rodamiento,
                                                fecha: mesAnio,
                                                _token: token
                                            },
                                            success: function(response) {
                                                if (response == 1) {
                                                    // Actualiza el bono comercial usando el valor actualizado de multas
                                                    let totalBoni = handNomina.getDataAtCell(row, 5); // Total bonos
                                                    let totalMultas = handNomina.getDataAtCell(row, 6); // Se usa el nuevo valor de multas
                                                    let totalBonoComercial = totalBoni - totalMultas; // Calcular bono comercial
                                                    let totalRodamiento = handNomina.getDataAtCell(row, 8); // Total rodamiento
                                                    let totalNomina = totalBonoComercial; // Calcular total de nómina

                                                    // Actualizar los valores en la tabla
                                                    handNomina.setDataAtCell(row, 7, totalBonoComercial, 'programmatic'); // Actualiza bono comercial
                                                    handNomina.setDataAtCell(row, 9, totalNomina + totalRodamiento, 'programmatic'); // Actualiza total nómina
                        
                                                    // Poner en 0 la última fila de las columnas 7, 8 y 9
                                                    handNomina.setDataAtCell(totalFilas, 7, 0, 'programmatic');
                                                    handNomina.setDataAtCell(totalFilas, 8, 0, 'programmatic');
                                                    handNomina.setDataAtCell(totalFilas, 9, 0, 'programmatic');

                                                    // Actualizar los totales
                                                    let totalMultasSum = 0;
                                                    let totalBonoComercialSum = 0;
                                                    let totalRodamientoSum = 0;
                                                    let totalNominaSum = 0;
                        
                                                    setTimeout(function() {
                                                        for (let i = 0; i < totalFilas; i++) {
                                                            totalMultasSum += parseFloat(handNomina.getDataAtCell(i, 6)) || 0; // Total multas
                                                            totalBonoComercialSum += parseFloat(handNomina.getDataAtCell(i, 7)) || 0; // Total bono comercial
                                                            totalRodamientoSum += parseFloat(handNomina.getDataAtCell(i, 8)) || 0; // Total rodamiento
                                                            totalNominaSum += parseFloat(handNomina.getDataAtCell(i, 9)) || 0; // Total nómina
                                                        }
                                                        // Actualizar la fila TOTAL
                                                        handNomina.setDataAtCell(totalFilas, 6, totalMultasSum, 'programmatic');
                                                        handNomina.setDataAtCell(totalFilas, 7, totalBonoComercialSum, 'programmatic');
                                                        handNomina.setDataAtCell(totalFilas, 8, totalRodamientoSum, 'programmatic');
                                                        handNomina.setDataAtCell(totalFilas, 9, totalNominaSum, 'programmatic');
                                                    },0);

                                                    if(rodamiento != null){
                                                        handCostosProyecto.setDataAtCell(row, 3, formatter.format(rodamiento));
                                                    }

                                                    let cedulaOperario = handNomina.getDataAtCell(row, 0);
                                                    let salario = 0;
                                                    let auxilioTransporte = 0;
                                                    let salud = 0;
                                                    let pension = 0;
                                                    let arl = 0;
                                                    let caja = 0;
                                                    let prima = 0;
                                                    let cesantias = 0;
                                                    let intCesantias = 0;
                                                    let vacaciones = 0;
                                                    let total = 0;

                                                    inspectoresAprendiz.forEach(inspector => {
                                                        if(inspector.cedula == cedulaOperario){
                                                            // validamos si el inspector es aprendiz o no
                                                            if(inspector.aprendiz == 1){
                                                                salario = totalNomina +  arraySalarioAux.salarioMinimo
                                                            }else{
                                                                salario = totalNomina +  arraySalarioAux.salarioMinimo + 150000
                                                            }

                                                            if(salario > arraySalarioAux.salarioMinimo * 2){
                                                                auxilioTransporte = 0
                                                            }else{
                                                                auxilioTransporte = arraySalarioAux.auxilioTransporte;
                                                            }

                                                            salud = Math.trunc(salario * arraySalarioAux.salud / 100);
                                                            pension = Math.trunc(salario * arraySalarioAux.pension / 100);
                                                            arl = Math.trunc(salario * arraySalarioAux.arl / 100);
                                                            caja = Math.trunc(salario * arraySalarioAux.caja / 100);
                                                            prima = Math.trunc(salario * arraySalarioAux.prima / 100);
                                                            cesantias = Math.trunc(salario * arraySalarioAux.cesantias / 100);
                                                            intCesantias = Math.trunc(salario * arraySalarioAux.intCesantias / 100);
                                                            vacaciones = Math.trunc(salario * arraySalarioAux.vacaciones / 100);

                                                            total = salario + salud + rodamiento + 
                                                                    pension + arl + caja + 
                                                                    prima + cesantias + 
                                                                    intCesantias + vacaciones;
                                                            
                                                        }
                                                    })

                                                    // establecemos el salario y el auxilio de transporte a la 
                                                    // celda correspondiente en la tabla de costos proyecto
                                                    handCostosProyecto.setDataAtCell(row, 2, formatter.format(salario));
                                                    handCostosProyecto.setDataAtCell(row, 4, formatter.format(auxilioTransporte));
                                                    handCostosProyecto.setDataAtCell(row, 5, formatter.format(salud));
                                                    handCostosProyecto.setDataAtCell(row, 6, formatter.format(pension));
                                                    handCostosProyecto.setDataAtCell(row, 7, formatter.format(arl));
                                                    handCostosProyecto.setDataAtCell(row, 8, formatter.format(caja));
                                                    handCostosProyecto.setDataAtCell(row, 9, formatter.format(prima));
                                                    handCostosProyecto.setDataAtCell(row, 10, formatter.format(cesantias));
                                                    handCostosProyecto.setDataAtCell(row, 11, formatter.format(intCesantias));
                                                    handCostosProyecto.setDataAtCell(row, 12, formatter.format(vacaciones));
                                                    handCostosProyecto.setDataAtCell(row, 13, formatter.format(total));

                                                    let ultimafila = handCostosProyecto.countRows() - 1;

                                                    let sumaTotalSalario = 0;
                                                    let sumaTotalRodamiento = 0;
                                                    let sumaTotalAuxTransporte = 0;
                                                    let sumaTotalSalud = 0;
                                                    let sumaTotalPension = 0;
                                                    let sumaTotalArl = 0;
                                                    let sumaTotalCaja = 0;
                                                    let sumaTotalPrima = 0;
                                                    let sumaTotalCesantias = 0;
                                                    let sumaTotalIntCesantias = 0;
                                                    let sumaTotalVacaciones = 0;
                                                    let sumaTotalTotal = 0;

                                                    for(let i = 0; i < ultimafila; i++){
                                                        // recorremos las filas de la tabla de costos proyecto
                                                        let celdaSalario = formtaterNumber(handCostosProyecto.getDataAtCell(i, 2));
                                                        let celdaRodamiento = formtaterNumber(handCostosProyecto.getDataAtCell(i, 3));
                                                        let celdaAuxTransporte = formtaterNumber(handCostosProyecto.getDataAtCell(i, 4));
                                                        let celdaSalud = formtaterNumber(handCostosProyecto.getDataAtCell(i, 5));
                                                        let celdaPension = formtaterNumber(handCostosProyecto.getDataAtCell(i, 6));
                                                        let celdaArl = formtaterNumber(handCostosProyecto.getDataAtCell(i, 7));
                                                        let celdaCaja = formtaterNumber(handCostosProyecto.getDataAtCell(i, 8));
                                                        let celdaPrima = formtaterNumber(handCostosProyecto.getDataAtCell(i, 9));
                                                        let celdaCesantias = formtaterNumber(handCostosProyecto.getDataAtCell(i, 10));
                                                        let celdaIntCesantias = formtaterNumber(handCostosProyecto.getDataAtCell(i, 11));
                                                        let celdaVacaciones = formtaterNumber(handCostosProyecto.getDataAtCell(i, 12));
                                                        let celdaTotal = formtaterNumber(handCostosProyecto.getDataAtCell(i, 13));

                                                        sumaTotalSalario += celdaSalario
                                                        sumaTotalRodamiento += celdaRodamiento
                                                        sumaTotalAuxTransporte += celdaAuxTransporte
                                                        sumaTotalSalud += celdaSalud
                                                        sumaTotalPension += celdaPension
                                                        sumaTotalArl += celdaArl
                                                        sumaTotalCaja += celdaCaja
                                                        sumaTotalPrima += celdaPrima
                                                        sumaTotalCesantias += celdaCesantias
                                                        sumaTotalIntCesantias += celdaIntCesantias
                                                        sumaTotalVacaciones += celdaVacaciones
                                                        sumaTotalTotal += celdaTotal
                                                    }

                                                    // establecemos el total de la tabla de costos proyecto
                                                    handCostosProyecto.setDataAtCell(ultimafila, 2, formatter.format(sumaTotalSalario));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 3, formatter.format(sumaTotalRodamiento));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 4, formatter.format(sumaTotalAuxTransporte));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 5, formatter.format(sumaTotalSalud));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 6, formatter.format(sumaTotalPension));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 7, formatter.format(sumaTotalArl));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 8, formatter.format(sumaTotalCaja));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 9, formatter.format(sumaTotalPrima));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 10, formatter.format(sumaTotalCesantias));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 11, formatter.format(sumaTotalIntCesantias));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 12, formatter.format(sumaTotalVacaciones));
                                                    handCostosProyecto.setDataAtCell(ultimafila, 13, formatter.format(sumaTotalTotal));
                                                   
                                                } else if (response == 2) {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: 'No se pudo realizar el registro'
                                                    });
                                                }
                                                tablaNomina[0].style.opacity = '1';
                                                tablaCostosProyecto[0].style.opacity = '1';
                                                loaderTablaNomina.hide();
                                                loaderTablaCostosProyecto.hide();
                                            },
                                            error: function(xhr, status, error) {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: error
                                                });
                                            }
                                        });
                                    }
                                }
                            });
                        });

                        // Fin de la función ---------------------------------------------
                        let arrayCostosProyecto = data.map(item => {
                            let salario = 0;
                            let auxTransPorte = 0;
                            let salud = 0;
                            let pension = 0;
                            let arl = 0;
                            let caja = 0;
                            let prima = 0;
                            let cesantias = 0;
                            let intCesantias = 0;
                            let vacaciones = 0;
                            let total = 0;
                            let valorRod = 0;

                           
                            inspectoresAprendiz.forEach(inspector => {
                                if (item.cedula == inspector.cedula) {
                                    let filaInspector = handNomina.getData().findIndex(fila => fila[0] == inspector.cedula);
                                    if (filaInspector !== -1) {

                                        // traemos el total de la nomina del inspector
                                        let salarioInspectorTotal = handNomina.getDataAtCell(filaInspector, 9);
                                        valorRod = handNomina.getDataAtCell(filaInspector, 8);

                                        // validamos si es aprendiz o no
                                        if (inspector.aprendiz === 1) {
                                            salario = salarioInspectorTotal + arraySalarioAux.salarioMinimo;
                                        } else {
                                            salario = salarioInspectorTotal + arraySalarioAux.salarioMinimo + 150000;
                                        }
                        
                                        // Verificar si el salario supera el doble del salario mínimo
                                        if (salario > arraySalarioAux.salarioMinimo * 2) {
                                            auxTransPorte = 0;
                                        } else {
                                            auxTransPorte = arraySalarioAux.auxilioTransporte;
                                        }

                                        salud = salario * arraySalarioAux.salud / 100;
                                        pension = salario * arraySalarioAux.pension / 100;
                                        arl = salario * arraySalarioAux.arl / 100;
                                        caja = salario * arraySalarioAux.caja / 100;
                                        prima = salario * arraySalarioAux.prima / 100
                                        cesantias = salario * arraySalarioAux.cesantias / 100
                                        intCesantias = salario * arraySalarioAux.intCesantias / 100
                                        vacaciones = salario * arraySalarioAux.vacaciones / 100;

                                        total = salario + auxTransPorte + 
                                                salud + pension + arl + 
                                                caja + prima + 
                                                cesantias + intCesantias + 
                                                vacaciones;
                                    }
                                }
                            });
                            return [
                                item.cedula,
                                item.nombres,
                                parseInt(salario),
                                parseInt(valorRod),
                                parseInt(auxTransPorte),
                                parseInt(salud),
                                parseInt(pension),
                                parseInt(arl),
                                parseInt(caja),
                                parseInt(prima),
                                parseInt(cesantias),
                                parseInt(intCesantias),
                                parseInt(vacaciones),
                                parseInt(total)
                            ];
                        });

                        arrayCostosProyecto.push(['TOTAL', '', '', '', '', '', '', '', '', '']);

                        handCostosProyecto.updateSettings({
                            data: arrayCostosProyecto,
                            colHeaders: [
                                'CODIGO',
                                'NOMBRE DEL EMPLEADO',
                                'SALARIO <br>'+formatter.format(arraySalarioAux.salarioMinimo)+'',
                                'VALOR <br> RODAMIENTO',
                                'AUX TRA <br>'+formatter.format(arraySalarioAux.auxilioTransporte)+'',
                                'SALUD <br>'+arraySalarioAux.salud+'%',
                                'PENSION <br>'+arraySalarioAux.pension+'%',
                                'ARL <br>'+arraySalarioAux.arl+'%',
                                'CAJA <br>'+arraySalarioAux.caja+'%',
                                'PRIMA <br>'+arraySalarioAux.prima+'%',
                                'CESANTIAS <br>'+arraySalarioAux.cesantias+'%',
                                'INT CESANTIAS <br>'+arraySalarioAux.intCesantias+'%',
                                'VACACIONES <br>'+arraySalarioAux.vacaciones+'%',
                                'TOTAL'
                            ],
                        });

                        let ultimafila = handCostosProyecto.countRows() - 1;

                        let totalSalario = 0;
                        let totalValorRod = 0;
                        let totalAuxTrans = 0;
                        let totalSalud = 0;
                        let totalPension = 0;
                        let totalArl = 0;
                        let totalCaja = 0;
                        let totalPrima = 0;
                        let totalCesantias = 0;
                        let totalIntCesantias = 0;
                        let totalVacaciones = 0;
                        let totalGeneral = 0;
                        
                        for (let i = 0; i < ultimafila; i++) {
                            // asignamos el valor al salario
                            let salarioInsFila = handCostosProyecto.getDataAtCell(i, 2);
                            let valorRodFila = handCostosProyecto.getDataAtCell(i, 3);

                            // volvemos a setear el valor de rodamiento
                            handCostosProyecto.setDataAtCell(i, 3, formatter.format(valorRodFila));
                            handCostosProyecto.setDataAtCell(i, 2, formatter.format(salarioInsFila - valorRodFila));
                            // volvemos a tomar el valor de la celda
                            salarioInsFila = salarioInsFila - valorRodFila

                            // volvemos a calcular los valores con el salario actualziado
                            if(salarioInsFila > arraySalarioAux.salarioMinimo * 2){
                                auxTrans = 0;
                            }else{
                                auxTrans = arraySalarioAux.auxilioTransporte;
                            }

                            let salud = salarioInsFila * arraySalarioAux.salud / 100;
                            let pension = salarioInsFila * arraySalarioAux.pension / 100;
                            let arl = salarioInsFila * arraySalarioAux.arl / 100;
                            let caja = salarioInsFila * arraySalarioAux.caja / 100;
                            let prima = salarioInsFila * arraySalarioAux.prima / 100
                            let cesantias = salarioInsFila * arraySalarioAux.cesantias / 100
                            let intCesantias = salarioInsFila * arraySalarioAux.intCesantias / 100
                            let vacaciones = salarioInsFila * arraySalarioAux.vacaciones / 100;
                                        
                            auxTrans = Math.trunc(auxTrans)
                            salud =  Math.trunc(salud)
                            pension =  Math.trunc(pension)
                            arl =  Math.trunc(arl)
                            caja =  Math.trunc(caja)
                            prima =  Math.trunc(prima)
                            cesantias =  Math.trunc(cesantias)
                            intCesantias =  Math.trunc(intCesantias)
                            vacaciones =  Math.trunc(vacaciones)
                            let total = salarioInsFila + valorRodFila + auxTrans + salud + 
                                        pension + arl + caja + prima + 
                                        cesantias + intCesantias + vacaciones;

                            handCostosProyecto.setDataAtCell(i, 4, formatter.format(auxTrans));
                            handCostosProyecto.setDataAtCell(i, 5, formatter.format(salud));
                            handCostosProyecto.setDataAtCell(i, 6, formatter.format(pension));
                            handCostosProyecto.setDataAtCell(i, 7, formatter.format(arl));
                            handCostosProyecto.setDataAtCell(i, 8, formatter.format(caja));
                            handCostosProyecto.setDataAtCell(i, 9, formatter.format(prima));
                            handCostosProyecto.setDataAtCell(i, 10, formatter.format(cesantias));
                            handCostosProyecto.setDataAtCell(i, 11, formatter.format(intCesantias));
                            handCostosProyecto.setDataAtCell(i, 12, formatter.format(vacaciones));
                            handCostosProyecto.setDataAtCell(i, 13, formatter.format(total));
                        
                            // Acumular los valores
                            totalSalario += salarioInsFila;
                            totalValorRod += valorRodFila;
                            totalAuxTrans += auxTrans;
                            totalSalud += salud;
                            totalPension += pension;
                            totalArl += arl;
                            totalCaja += caja;
                            totalPrima += prima;
                            totalCesantias += cesantias;
                            totalIntCesantias += intCesantias;
                            totalVacaciones += vacaciones;
                            totalGeneral += total;
                        }

                        // establecemos los valores en la ultima fila 
                        handCostosProyecto.setDataAtCell(ultimafila, 2, formatter.format(totalSalario));
                        handCostosProyecto.setDataAtCell(ultimafila, 3, formatter.format(totalValorRod));
                        handCostosProyecto.setDataAtCell(ultimafila, 4, formatter.format(totalAuxTrans));
                        handCostosProyecto.setDataAtCell(ultimafila, 5, formatter.format(totalSalud));
                        handCostosProyecto.setDataAtCell(ultimafila, 6, formatter.format(totalPension));
                        handCostosProyecto.setDataAtCell(ultimafila, 7, formatter.format(totalArl));
                        handCostosProyecto.setDataAtCell(ultimafila, 8, formatter.format(totalCaja));
                        handCostosProyecto.setDataAtCell(ultimafila, 9, formatter.format(totalPrima));
                        handCostosProyecto.setDataAtCell(ultimafila, 10, formatter.format(totalCesantias));
                        handCostosProyecto.setDataAtCell(ultimafila, 11, formatter.format(totalIntCesantias));
                        handCostosProyecto.setDataAtCell(ultimafila, 12, formatter.format(totalVacaciones));
                        handCostosProyecto.setDataAtCell(ultimafila, 13, formatter.format(totalGeneral));

                        handCostosProyecto.updateSettings({
                            cells: function (rowIndex, colIndex) {
                                
                                let cellProperties = {};

                                if(colIndex >= 0 && colIndex <= 12){
                                    cellProperties.className = 'celdasGeneral';
                                }

                                if(colIndex === 13){
                                    cellProperties.className = 'cell-total-costo';
                                }

                                if(rowIndex === ultimafila){
                                    cellProperties.className = 'cell-ultima-fila';
                                }
                                return cellProperties;
                            }
                        });
                    }else{
                        Swal.fire({
                            icon: 'info',
                            title: 'No hay datos',
                            text: 'No hay datos para mostrar'
                        });
                        if (window.handNomina && !window.handNomina.isDestroyed) {
                            window.handNomina.destroy();
                            window.handNomina = null;
                        }
                        loaderNomina.hide()
                        $('.cardReporteProduccion').hide()
                    }
                },
                error: function(xhr, status, error) {
                    loaderNomina.hide()
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: error
                    });
                }
            })
        }
    })

    // Descargar excel
    $(document).on('click', '#descargarExcelNomina', function(){
        // Obtener encabezados y datos de la primera tabla (handNomina)
        let headers1 = handNomina.getColHeader(); // Encabezados de la primera tabla
        let datos1 = handNomina.getData(); // Datos de la primera tabla

        // Formatear las columnas 5, 6, 7, 8 y 9 para que tengan formato de moneda en handNomina
        datos1 = datos1.map((fila, rowIndex) => {
            return fila.map((cell, colIndex) => {
                if ([5, 6, 7, 8, 9].includes(colIndex)) {
                    // Formatear solo si el valor es numérico
                    if (typeof cell === 'number') {
                        return `$${cell.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
                    }
                }
                return cell; // Devolver el valor original para otras columnas
            });
        });

        // Añadir encabezados a los datos de handNomina
        datos1.unshift(headers1);

        // Obtener encabezados y datos de la segunda tabla (handCostosProyecto)
        let headers2 = handCostosProyecto.getColHeader().map(header => header.replace(/<br\s*\/?>/gi, ' ')); // Reemplazar <br> por espacio
        let datos2 = handCostosProyecto.getData(); // Datos de la segunda tabla

        // Formatear las columnas 2 a 12 para que tengan formato de moneda en handCostosProyecto
        datos2 = datos2.map((fila, rowIndex) => {
            return fila.map((cell, colIndex) => {
                if (colIndex >= 2 && colIndex <= 12) {
                    // Formatear solo si el valor es numérico
                    if (typeof cell === 'number') {
                        return `$${cell.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".")}`;
                    }
                }
                return cell; // Devolver el valor original para otras columnas
            });
        });

        // Añadir encabezados a los datos de handCostosProyecto
        datos2.unshift(headers2);

        // Añadir una fila vacía para separar las tablas
        datos1.push([],[]);
        
        // Unir las dos tablas (datos de handNomina y handCostosProyecto)
        let datosCombinados = datos1.concat(datos2);

        // Crear una nueva hoja de trabajo
        const worksheet = XLSX.utils.aoa_to_sheet(datosCombinados);

        // Crear un nuevo libro de trabajo y añadir la hoja
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte_Completo');

        // Exportar el archivo Excel
        XLSX.writeFile(workbook, 'Reporte_nomina_costos.xlsx');
    });

    
    function regex(str) {
        return str === null || str === "" || /^\d+$/.test(str);
    }

    function formtaterNumber(str){
        let partes1 = str.split('$');
        let partes2 = partes1[1].split('.')
        let parteRetornar = partes2.join('')
        parteRetornar = parseInt(parteRetornar)
        return parteRetornar
    }
})