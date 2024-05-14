
$(document).ready(function () {

    // Inicializar la tabla activa con DataTables
    $('table:not(.no-datatable)').DataTable({
        "paging": false,
        "scrollY": "400px",
        "lengthChange": true,
        "searching": true,
        "ordering": true,
        "info": false,
        "autoWidth": true,
        "stripeClasses": ['bg-light', 'bg-secondary'],
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

    // Ocultar todas las tablas y inicializar la tabla activa
    $('table[style*="display: none"]').parent().hide();
    $('div[class*="tab-content"]').show();
    $('div[class*="dt-container dt-empty-footer"]').hide();

    var idElemento = $('.active').attr('href');
    var divid = $('div[id*="' + idElemento + '_wrapper"]').attr('id');

    if (divid) {
        var selectorid = document.getElementById(divid);
        document.getElementById(divid).style.display = 'table';
        var layoutCellDiv = selectorid.querySelector('.dt-layout-table');
        var layoutCellDivStyle = layoutCellDiv.querySelector('.dt-layout-cell ');
        layoutCellDivStyle.style.display = 'table';
    }

    //inicializar contadores pagina 1
    $('.tbl_datos[id]').each(function () {

        // Obtener el ID de la tabla actual
        var idTabla = $(this).attr('id');

        // Ejecutar la función contadores_dinamicos(id) para la tabla actual
        contadores_dinamicos(idTabla);

    });

    // Mostrar la tabla correspondiente cuando se hace clic en una pestaña
    $('.btnav').on('click', function () {
        // Ocultar todas las tablas nuevamente
        $('table').hide();
        $('.btnav').removeClass('active');
        /*  $('table[style*="display: none"]').parent().hide(); */
        $('div[class*="display: none"]').show();
        $('div[style*="display: table"]').hide();
        $('div[class*="tab-content"]').parent().show();
        $('div[class*="col-md-4"]').show();

        //$('table[class*="no-datatable"]').hide();
        // Obtener el ID de la pestaña activa
        var tabId = $(this).attr('href');
        var divid = $('div[id*="' + tabId + '_wrapper"]').attr('id');

        if (divid) {
            const selectorid = document.getElementById(divid);
            document.getElementById(divid).style.display = 'table';
            const styleCell = selectorid.querySelector('.dt-layout-table');
            styleCell.querySelector('.dt-layout-cell').style.display = 'table';
            const styleScroll = selectorid.querySelector('.dt-scroll-headInner');
            styleScroll.style.display = 'table';
            const styleTable = selectorid.querySelector('.dt-scroll-body');
            styleTable.style.display = '';
            styleTable.style.overflowX = 'hidden';
        }

        // habilitar vista tabla contadores
        var tbl_contadores = document.getElementById(tabId);
        // habilitar vista tabla Inspector     
        $('.tbl_datos').attr('style', 'table');


        // Verificar si se encontró la tabla
        if (tbl_contadores) {
            // Cambiar el estilo de la tabla para hacerla visible
            tbl_contadores.style.display = 'table';

        };

        $(this).addClass('active');

        contadores_dinamicos(tabId);

    });


    $('#btnGuardar').on('click', function () {


        $('#loader').show();
        $('#overlay').show();

        setTimeout(function () {
            let valoresSeleccionados = {};
            let datos = [];
            const encabezado = ['INSPECTOR', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO  CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION', '4 RECINTOS O MAS'];
            $('.tbl_datos[id]').each(function (indexTabla) {

                let idTabla = $(this).attr('id');
                let nombre_convertido = idTabla.replace(/\s/g, '\\ ');

                datosTabla = $('' + nombre_convertido + ' .tbl_datos').DataTable().rows().data().toArray();
                datos.push(datosTabla)
                let indexSelect = -1;
                $('' + nombre_convertido + ' .tbl_datos').DataTable().rows().every(function () {

                    indexSelect = indexSelect + 1;

                    let checkbox = $(this.node()).find('td:eq(14) input').is(':checked');

                    let idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    let valorSeleccionado = checkbox;

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                    indexSelect = indexSelect + 1;

                    let selectValueCombobox1 = $(this.node()).find('td:eq(15) select').val();

                    let selectValueCombobox2 = $(this.node()).find('td:eq(16) select').val();

                    idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    valorSeleccionado = selectValueCombobox1

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                    indexSelect = indexSelect + 1;

                    idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    valorSeleccionado = selectValueCombobox2

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                });

            });
          
            let contador_tabla = 0;
            let indicadores = [];
            datos.forEach(element => {
                let contador_combobox1 = 1;

                let certificadaCount = 0;
                let certificadaConNovedadesCount = 0;
                let inspeccionadaConDefectoCriticoCount = 0;
                let inspeccionadaConDefectoNoCriticoCount = 0;
                let totalCount = 0;

                element.forEach(function (value, index) {

                    const selectValueCombobox = valoresSeleccionados["select_" + contador_tabla + "_" + contador_combobox1];
                    const valor_cierre = value[10];
                   
                    // Verificar si la fila cumple con los criterios necesarios para contar
                    if (selectValueCombobox === 'OK') {

                        switch (valor_cierre) {

                            case '.CERTIFICADA':
                                certificadaCount++;
                                totalCount++;
                                break;
                            case 'CERTIFICADA CON NOVEDADES':
                                certificadaConNovedadesCount++;
                                totalCount++;
                                break;
                            case '.INSPECCIONADA CON DEFECTO CRITICO VALLE':
                                inspeccionadaConDefectoCriticoCount++;
                                totalCount++;
                                break;
                            case '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE':
                                inspeccionadaConDefectoNoCriticoCount++;
                                totalCount++;
                                break;

                        }
                    }
                    contador_combobox1 = contador_combobox1 + 3;
                    
                });
                indicadores.push({ certificadaCount, certificadaConNovedadesCount, inspeccionadaConDefectoCriticoCount, inspeccionadaConDefectoNoCriticoCount, totalCount });
                contador_tabla = contador_tabla + 1;
            });
            
            const csrfToken = $('#token').val();
            const url_guardar = $('#url_guardar').val();
            const url_borrar = $('#url_borrar').val();

            // Realizar la petición AJAX
            $.ajax({
                type: 'POST',
                url: url_guardar,
                data: {
                    valoresSeleccionados: valoresSeleccionados,
                    encabezado: encabezado,
                    datos: datos,
                    indicadores: indicadores,
                    _token: csrfToken
                },
                success: function (response) {
                    console.log(response);
                    if (response.ruta) {
                            window.location.href = response.ruta;
                            codigoHTML_tabla_indicadores = null;
                            valoresSeleccionados = null;
                            $('#loader').hide();
                            $('#overlay').hide();
                
                    } else {
                        $('#loader').hide();
                        $('#overlay').hide();
                        Swal.fire({
                            type: 'warning',
                            title: 'Advertencia',
                            text: response.error,
                        });
                    }

                },
                error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                    $('#loader').hide();
                    $('#overlay').hide();
                    Swal.fire({
                        type: 'error',
                        title: 'Error',
                        text: error,
                    });
                }
            });
        }, 100);

    });

    // abrir modal agregar inspecciones en papel
    document.getElementById('btnPapel').addEventListener('click', function () {
        $('#ventanaEmergente').modal('show');
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
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);
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
    //-------------------------------------------------------------------------------- 
    // control de devoluci´pn
    const devolucionSelect = document.getElementById('devolucion');
    const causalDevolucionGroup = document.getElementById('causal_devolucion');

    devolucionSelect.addEventListener('change', function () {
        if (this.value === 'DV') {
            causalDevolucionGroup.style.display = 'block'; // Mostrar el campo "Causal Devolución"
        } else {
            causalDevolucionGroup.style.display = 'none'; // Ocultar el campo "Causal Devolución"
        }
    });

    const btnAgregar = document.getElementById('agregar');




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
            if (campo.value.trim() === '' || campo.value === ':') {
                formularioValido = false;
                campo.style.border = '1px solid red'; // Establecer borde rojo para campos no completados

            } else {
                campo.style.border = ''; // Restablecer estilo de borde por defecto
            }
        });

        if (formularioValido) {

            const nombre_insp = agregar_datos();
            if (nombre_insp) {
                $('#ventanaEmergente').modal('hide');

                campos.forEach(campo => {
                    campo.value = campo.getAttribute('value') || '';
                    switch (campo.id) {
                        case 'causal':
                            campo.value = '--SELECCIONE CAUSAL--';
                            campo.classList.remove('campo-invalido');
                            break;
                        case 'devolucion':
                            campo.value = 'OK';
                            break;
                        case 'recintos':
                            campo.value = 'NO';
                            break;
                    }

                    causalDevolucionGroup.style.display = 'none'; // Ocultar el campo "Causal Devolución"

                });
                contadores_dinamicos('#' + nombre_insp);

                Swal.fire({
                    position: "top-end",
                    type: "success",
                    title: "Inspeccion agregada correctamente",
                    showConfirmButton: false,
                    toast: true,
                    timer: 3000
                });
            }

        } else {
            window.scrollTo({
                top: 0,
                left: 0,
                behavior: 'smooth'
            });
            Swal.fire({
                position: "top-end",
                type: "warning",
                title: "Por favor complete todos los campos",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
        }
    });


});

document.addEventListener('DOMContentLoaded', function () {
    var navWrapper = document.querySelector('.nav-wrapper');
    var scrollStep = 500; // Cantidad de desplazamiento en píxeles

    // Función para desplazar hacia la izquierda
    function scrollLeft() {
        navWrapper.scrollBy({
            left: -scrollStep,
            behavior: 'smooth'
        });
    }

    // Función para desplazar hacia la derecha
    function scrollRight() {
        navWrapper.scrollBy({
            left: scrollStep,
            behavior: 'smooth'
        });
    }

    // Agregar evento clic a los botones de navegación
    document.querySelector('.scroll-left').addEventListener('click', scrollLeft);
    document.querySelector('.scroll-right').addEventListener('click', scrollRight);

    var links = document.querySelectorAll('.btnav');

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault(); // Evitar el comportamiento predeterminado del enlace
            // Puedes agregar aquí cualquier lógica adicional que desees realizar al hacer clic en el enlace
        });
    });

    var comboBoxes = document.querySelectorAll('select.form-select.nombre-columna');

    comboBoxes.forEach(function (comboBox) {
        comboBox.addEventListener('change', function () {

            var id_pestaña = $('.btnav.active').attr('href');

            contadores_dinamicos(id_pestaña);
            cambiarColor(comboBox);
        });
    });

});

function contadores_dinamicos(nombre) {

    // Eliminar el símbolo "#"
    nombre_sin_simbolo = nombre.replace("#", "");

    // Separar apellido y primer nombre
    var partesNombre = nombre_sin_simbolo.split(" ");
    var apellido = partesNombre[0];
    var P_nombre = partesNombre[2];

    // Imprimir los resultados

    var nombre_convertido = nombre.replace(/\s/g, '\\ ');

    // Inicializar contadores
    certificadaCount = 0;
    certificadaConNovedadesCount = 0;
    inspeccionadaConDefectoCriticoCount = 0;
    inspeccionadaConDefectoNoCriticoCount = 0;
    totalCount = 0;
    $('' + nombre_convertido + ' .tbl_datos').DataTable().rows().every(function () {


        var selectValueCombobox = $(this.node()).find('td:eq(15) select').val();
        var valor_cierre = $(this.node()).find('td:eq(10)').text();
        /* console.log(selectValueCombobox); */
        // Verificar si la fila cumple con los criterios necesarios para contar
        if (selectValueCombobox === 'OK') {

            switch (valor_cierre) {

                case '.CERTIFICADA':
                    certificadaCount++;
                    totalCount++;
                    break;
                case 'CERTIFICADA CON NOVEDADES':
                    certificadaConNovedadesCount++;
                    totalCount++;
                    break;
                case '.INSPECCIONADA CON DEFECTO CRITICO VALLE':
                    inspeccionadaConDefectoCriticoCount++;
                    totalCount++;
                    break;
                case '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE':
                    inspeccionadaConDefectoNoCriticoCount++;
                    totalCount++;
                    break;

            }
        }
    });

    // Mostrar los resultados en las celdas correspondientes
    $('.certificadaCount.' + apellido + '.' + P_nombre).text(certificadaCount);
    $('.certificadaConNovedadesCount.' + apellido + '.' + P_nombre).text(certificadaConNovedadesCount);
    $('.inspeccionadaConDefectoCriticoCount.' + apellido + '.' + P_nombre).text(inspeccionadaConDefectoCriticoCount);
    $('.inspeccionadaConDefectoNoCriticoCount.' + apellido + '.' + P_nombre).text(inspeccionadaConDefectoNoCriticoCount);
    $('.totalCount.' + apellido + '.' + P_nombre).text(totalCount);
}


function agregar_datos() {

    //obtener el select del inspector
    const select_insp = document.getElementById('nombre');
    const selectedoption = select_insp.options[select_insp.selectedIndex];
    //obtener el valor de la cedula
    const cedulaInsp = select_insp.value;

    //obtener el nombre del inspector
    const nombre_insp = selectedoption.getAttribute('data-nombres');

    const municipio = document.getElementById('municipio').value;
    const fecha = document.getElementById('fecha').value;
    const acta = document.getElementById('N°acta').value;
    const tipo_trabajo = document.getElementById('tipo_trabajo').value;
    const contrato = document.getElementById('contrato').value;
    const orden = document.getElementById('orden_trabajo').value;
    const categoria = document.getElementById('categoria').value;
    const hora_inicio = document.getElementById('hora_inicio').value;
    const hora_final = document.getElementById('hora_final').value;
    const recintos = document.getElementById('recintos').value;
    const devolucion = document.getElementById('devolucion').value;
    const resultado_cierre = document.getElementById('resultado_cierre').value;
    const causal = document.getElementById('causal').value;

    const duracion = calcularDuracion(hora_inicio, hora_final);

    const [anio, mes, dia] = fecha.split('-').map(Number);

    const fechaObj = new Date(anio, mes - 1, dia); // Restar 1 al mes para que sea 0-indexado

    let diaFormateado = fechaObj.getDate().toString().padStart(2, '0');
    let mesFormateado = (fechaObj.getMonth() + 1).toString().padStart(2, '0');
    let anioFormateado = fechaObj.getFullYear().toString().slice(-2);

    const fechaFormateada = `${diaFormateado}-${mesFormateado}-${anioFormateado}`;

    const tabla = $('table.tbl_datos[id^="#' + nombre_insp + '"]');

    const validador = validacionDatos(orden, contrato, tabla);

    if (validador === false) {
        // Crear una nueva fila y celdas para agregar los valores

        const datos = tabla.DataTable().row.add([
            nombre_insp,
            cedulaInsp,
            municipio,
            fechaFormateada,
            acta,
            tipo_trabajo,
            contrato,
            orden,
            tipo_trabajo === 'RP 12161' ? orden : '',
            categoria,
            resultado_cierre,
            hora_inicio,
            hora_final,
            duracion,
            '<input type="checkbox" ' + (recintos === 'SI' ? 'checked' : '') + '>',
            (devolucion === 'OK' ? '<select class="form-select nombre-columna" style="width: 80px;"><option value="OK" selected>OK</option><option value="DV">DV</option></select>' : '<select class="form-select nombre-columna" style="width: 80px;"><option value="OK">OK</option><option value="DV" selected>DV</option></select>'),
            (causal === '--SELECCIONE CAUSAL--' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--" selected>--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                (causal === 'CONTRATO ERRADO' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO" selected>CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                    (causal === 'NUMERO DE CUOTAS' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS" selected>NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                        (causal === 'FALTA CARTA' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA" selected>FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                            (causal === 'FALTA INFORMACIÓN' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN" selected>FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                                (causal === 'INFORMACION ERRADA' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA" selected>INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>' :
                                    (causal === 'ORDEN YA REGISTRADA' ? '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--">--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA" selected>ORDEN YA REGISTRADA</option></select>' :
                                        '<select class="form-select combo2 nombre-columna" style="width: 220px; display: none;"><option value="--SELECCIONE CAUSAL--" selected>--SELECCIONE CAUSAL--</option><option value="CONTRATO ERRADO">CONTRATO ERRADO</option><option value="NUMERO DE CUOTAS">NUMERO DE CUOTAS</option><option value="FALTA CARTA">FALTA CARTA</option><option value="FALTA INFORMACIÓN">FALTA INFORMACIÓN</option><option value="INFORMACION ERRADA">INFORMACION ERRADA</option><option value="ORDEN YA REGISTRADA">ORDEN YA REGISTRADA</option></select>'))))))),

        ]).draw().data();


        const ultimoSelect = tabla.find('select.form-select.nombre-columna').filter(function () {
            return !$(this).hasClass('combo2');
        }).last();

        cambiarColor(ultimoSelect[0]);
        /* console.log({data: tabla.DataTable().data()}); */
        $('table.tbl_datos').on('change', 'select.form-select.nombre-columna', function () {
            var id_pestaña = $('.btnav.active').attr('href');
            contadores_dinamicos(id_pestaña);
            cambiarColor(this);
        });

        return nombre_insp;

    } else {
        alert('El contrato y la orden de trabajo ya se encuentran registrados en la bitacora. Por favor, verifique los datos ingresados.');
    }

}

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

function cambiarColor(select) {
    var fila = select.parentNode.parentNode;

    var valorSeleccionado = select.value;

    // Verificar si se seleccionó 'DV' en el combobox
    if (valorSeleccionado === 'DV') {
        var celdas = fila.getElementsByTagName('td');
        // Iterar sobre las celdas de la fila
        for (var i = 0; i < celdas.length; i++) {

            if (celdas[i].textContent.includes(':')) {
                celdas[i].style.backgroundColor = 'rgb(255, 0, 0)'; // Cambiar el color de fondo a rojo
                break;
            }
        }
    } else if (valorSeleccionado === 'OK') {
        var celdas = fila.getElementsByTagName('td');
        // Iterar sobre las celdas de la fila
        for (var i = 0; i < celdas.length; i++) {

            if (celdas[i].textContent.includes(':')) {
                celdas[i].style.backgroundColor = 'rgb(146, 208, 80)'; // Cambiar el color de fondo a Verde
                break;
            }
        }
    }

    // Obtener el segundo combobox de la fila
    var segundoComboBox = fila.querySelector('.combo2');

    // Verificar si el primer combobox tiene el valor "OK"
    if (valorSeleccionado === 'OK') {
        // Ocultar el segundo combobox

        segundoComboBox.value = '--SELECCIONE CAUSAL--';
        segundoComboBox.style.display = 'none';
    } else {
        // Mostrar el segundo combobox
        segundoComboBox.style.display = 'block';
    }
}

function validacionDatos(orden, contrato, tabla) {

    const contratoNuevo = contrato;
    const ordenNuevo = orden;

    // Obtener los datos existentes en la tabla
    const data = tabla.DataTable().data();
    let datosRepetidos = false;
    // Verificar si los datos ya existen en la tabla
    data.each(function (value, index) {
        const contratoExistente = value[6]; // Índice de la columna del contrato en los datos existentes
        const ordenExistente = value[7]; // Índice de la columna de la orden en los datos existentes

        if (contratoExistente === contratoNuevo && ordenExistente === ordenNuevo) {
            datosRepetidos = true; // Salir del bucle each si se encuentran datos repetidos
        }
    });
    if (datosRepetidos === true) {
        return true;
    } else {
        return false;
    } // Si no se encontraron datos repetidos, retornar false
}


