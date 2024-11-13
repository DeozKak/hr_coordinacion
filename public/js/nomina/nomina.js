$(document).ready(function(){

    const formatter = new Intl.NumberFormat('es-CO', {
        style: 'currency',
        currency: 'COP',
        minimumFractionDigits: 0,
    });

    $(document).off('click', '#generarReporte')
    $(document).on('click', '#generarReporte', function(){
        let mesAnio = $('#mesAnio').val();
        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')
        let loaderNomina = $('.loaderDivNomina')
        let [year, month] = mesAnio.split('-');
        // Creamos un objeto de fecha con el primer día del mes actual
        let fechaActual = new Date(year, month - 1); // Mes actual (restamos 1 porque los meses en JS van de 0 a 11)
        // Obtenemos el nombre del mes actual en español
        let nombreMesActual = fechaActual.toLocaleString('es-ES', { month: 'long' });
        // Para obtener el mes anterior, restamos 1 al mes actual
        fechaActual.setMonth(fechaActual.getMonth() - 1); // Restamos 1 mes
        // Obtenemos el nombre del mes anterior
        let nombreMesAnterior = fechaActual.toLocaleString('es-ES', { month: 'long' });
        
        loaderNomina.show()
        if(mesAnio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Debe seleccionar una fecha'
            });
            return
        }else{
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
    
                        $('.reporteNominaTitulo .card-title').text('Reporte de nómina Corte: '+nombreMesAnterior+'-'+nombreMesActual+' '+year)
                        $('.cardReporteProduccion').show()
    
                        let data = []
                        let multasArray = [];
    
                        if(response.length > 0){
                            data = response[0].data.produccionInspector
                            multasArray = response[0].multas
                        }
    
                        let tablaNomina = $('#tablaNomina')
    
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
    
                            let totalBonitifacion = bonificacion + copas;
    
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
    
                            bonoComecial = totalBonitifacion - multas
    
                            totalNomina = bonoComecial + rodamiento
    
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
                                                    let totalNomina = totalBonoComercial + totalRodamiento; // Calcular total de nómina
                        
                                                    // Actualizar los valores en la tabla
                                                    handNomina.setDataAtCell(row, 7, totalBonoComercial, 'programmatic'); // Actualiza bono comercial
                                                    handNomina.setDataAtCell(row, 9, totalNomina, 'programmatic'); // Actualiza total nómina
                        
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
                        
                                                } else if (response == 2) {
                                                    Swal.fire({
                                                        icon: 'error',
                                                        title: 'Error',
                                                        text: 'No se pudo realizar el registro'
                                                    });
                                                }
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
        let headers1 = handNomina.getColHeader(); // Encabezados de la primera tabla
        let datos1 = handNomina.getData(); // Datos de la primera tabla
        
        // Formatear las columnas 5, 6, 7, 8 y 9 para que tengan formato de moneda
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
    
        // Añadir encabezados a los datos
        datos1.unshift(headers1);
    
        // Crear una nueva hoja de trabajo
        const worksheet = XLSX.utils.aoa_to_sheet(datos1);
    
        // Crear un nuevo libro de trabajo y añadir la hoja
        const workbook = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(workbook, worksheet, 'Reporte');
    
        // Exportar el archivo Excel
        XLSX.writeFile(workbook, 'Reporte_nomina.xlsx');
    });
    
    function regex(str) {
        return str === null || str === "" || /^\d+$/.test(str);
    }
})
    