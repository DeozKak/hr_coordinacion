var codigoHTML = "";
$(document).ready(function () {


    // Recorrer cada tabla con la clase "tbl_datos"
    $('.tbl_datos').each(function () {

        var tablaHTML = $(this)[0].outerHTML;

        tablaHTML = tablaHTML.replace(/\s+/g, ' ');

        codigoHTML += tablaHTML;

    });

    // Inicializar la tabla activa con DataTables
    $('table:not(.no-datatable)').DataTable({
        "paging": true, // Activar la paginación
        "lengthChange": true, // Desactivar el cambio de cantidad de elementos por página
        "searching": true, // Activar la búsqueda
        "ordering": true, // Activar la ordenación
        "info": true, // Mostrar información sobre la tabla
        "autoWidth": true, // Desactivar el ajuste automático del ancho de las columnas
        "stripeClasses": ['bg-light', 'bg-secondary']
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
    $('.tbl_datos').each(function () {

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
            var selectorid = document.getElementById(divid);
            document.getElementById(divid).style.display = 'table';
            var layoutCellDiv = selectorid.querySelector('.dt-layout-table');
            var layoutCellDivStyle = layoutCellDiv.querySelector('.dt-layout-cell ');
            layoutCellDivStyle.style.display = 'table';
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
            var valoresSeleccionados = {};


            $('.tbl_datos').each(function (indexTabla) {

                var idTabla = $(this).attr('id');
                var nombre_convertido = idTabla.replace(/\s/g, '\\ ');
                var indexSelect = -1;
                $('' + nombre_convertido + ' .tbl_datos').DataTable().rows().every(function () {

                    indexSelect = indexSelect + 1;

                    var checkbox = $(this.node()).find('td:eq(14) input').is(':checked');

                    var idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    var valorSeleccionado = checkbox;

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                    indexSelect = indexSelect + 1;

                    var selectValueCombobox1 = $(this.node()).find('td:eq(15) select').val();

                    var selectValueCombobox2 = $(this.node()).find('td:eq(16) select').val();

                    idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    valorSeleccionado = selectValueCombobox1

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                    indexSelect = indexSelect + 1;

                    idSelect = $(this).attr('id') || 'select_' + indexTabla + '_' + indexSelect;

                    valorSeleccionado = selectValueCombobox2

                    valoresSeleccionados[idSelect] = valorSeleccionado;

                });

            });

            var codigoHTML_tabla_indicadores = ""

            $('.tabla-indicadores').each(function () {
                var tablaHTML_indicadores = $(this).prop('outerHTML');
                codigoHTML_tabla_indicadores += tablaHTML_indicadores;

            })

            const csrfToken = $('#token').val();
            const url_guardar = $('#url_guardar').val();
            const url_borrar = $('#url_borrar').val();

            // Realizar la petición AJAX
            $.ajax({
                type: 'POST',
                url: url_guardar,
                data: {
                    valoresSeleccionados: valoresSeleccionados,
                    codigoHTML: codigoHTML,
                    codigoHTML_tabla_indicadores: codigoHTML_tabla_indicadores,
                    _token: csrfToken
                },
                success: function (response) {

                    if (!response.error) {
                        const nombreArchivo = response.nombreArchivo;
                        const urlarchivo = response.ruta;
                        if (nombreArchivo !== undefined) {
                            const urlDescarga = urlarchivo + nombreArchivo;
                            window.location.href = urlDescarga;
                            codigoHTML_tabla_indicadores = null;
                            valoresSeleccionados = null;
                            $('#loader').hide();
                            $('#overlay').hide();
                        } else {
                            $('#loader').hide();
                            $('#overlay').hide();
                            Swal.fire({
                                type: 'error',
                                title: 'Error',
                                text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                            });
                        }

                    } else {
                        $('#loader').hide();
                        $('#overlay').hide();
                        Swal.fire({
                            type: 'warning',
                            title: 'Advertencia',
                            text: response.error,
                        });
                    }

                    $.ajax({
                        type: 'POST',
                        url: url_borrar,
                        data: {
                            _token: csrfToken
                        },
                        success: function (response) {

                        },
                        error: function (xhr, status, error) {

                            $('#loader').hide();
                            $('#overlay').hide();
                            Swal.fire({
                                type: 'error',
                                title: 'Error',
                                text: error,
                            });
                        }
                    });
                },
                error: function (xhr, status, error) {

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

    var comboBoxes = document.querySelectorAll('select');

    comboBoxes.forEach(function (comboBox) {
        comboBox.addEventListener('change', function () {

            var id_pestaña = $('.btnav.active').attr('href');

            contadores_dinamicos(id_pestaña);
            cambiarColor(comboBox);
        });
    });

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

document.addEventListener('DOMContentLoaded', function () {

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
        this.value = this.value.replace(/[^0-9]/g, '');
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
            this.value = ':' + this.value.replace(/[^0-9]/g, '');

        } else {
            // Si se elimina el ":", volver a agregarlo
            this.value = ':' + this.value.replace(/[^0-9]/g, '');

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
        this.value = this.value.replace(/[^0-9]/g, '');
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

    const btnAgregar = document.getElementById('btn-agregar');

    btnAgregar.addEventListener('click', function () {
        const campos = document.querySelectorAll('#ventanaEmergente input, #ventanaEmergente select');

        let formularioValido = true;

        campos.forEach(campo => {
            if (campo.value.trim() === '' || campo.value === ':') {
                formularioValido = false;
                campo.style.border = '1px solid red'; // Establecer borde rojo para campos no completados
            } else {
                campo.style.border = ''; // Restablecer estilo de borde por defecto
            }
        });

        if (formularioValido) {
            // Aquí puedes enviar el formulario o realizar alguna acción adicional
            campos.forEach(campo => {
                campo.value = campo.getAttribute('value') || ''; 
            });
        } else {
            alert('Por favor complete todos los campos antes de enviar el formulario.');
        }
    });

});

