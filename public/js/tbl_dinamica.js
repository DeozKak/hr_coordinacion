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


    $('table[style*="display: none"]').parent().hide();
    $('div[class*="tab-content"]').show();

    //inicializar contadores pagina 1
    $('.tbl_datos').each(function () {

        // Obtener el ID de la tabla actual
        var idTabla = $(this).attr('id');

        // Ejecutar la función contadores_dinamicos(id) para la tabla actual
        contadores_dinamicos(idTabla);

    });

    // Mostrar la tabla correspondiente cuando se hace clic en una pestaña
    $('.nav-link').on('click', function () {
        // Ocultar todas las tablas nuevamente
        $('table').hide();
        $('.nav-link').removeClass('active');
        $('table[style*="display: none"]').parent().hide();
        $('div[style*="display: table"]').hide();
        $('div[class*="tab-content"]').show();
        $('div[class*="col-md-4"]').show();
        //$('table[class*="no-datatable"]').hide();
        // Obtener el ID de la pestaña activa
        var tabId = $(this).attr('href');
        var divid = $('div[id*="' + tabId + '_wrapper"]').attr('id');

        if (divid) {
            document.getElementById(divid).style.display = 'table';
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

            var csrfToken = $('#token').val();
  
            // Realizar la petición AJAX
            $.ajax({
                type: 'POST',
                url: 'routes.php?accion=Guardar_Tabla',
                data: {
                    valoresSeleccionados: valoresSeleccionados,
                    codigoHTML: codigoHTML,
                    codigoHTML_tabla_indicadores: codigoHTML_tabla_indicadores,
                    csrf_token: csrfToken
                },
                success: function (response) {
                    console.log(response)
                    if (!response.error) {
                        var nombreArchivo = response.nombreArchivo;
                        if (nombreArchivo !== undefined) {
                            var urlDescarga = 'Controlador/Archivos/' + nombreArchivo;
                            window.location.href = urlDescarga;
                            codigoHTML_tabla_indicadores = null;
                            valoresSeleccionados = null;
                            $('#loader').hide();
                            $('#overlay').hide();
                        } else {
                            $('#loader').hide();
                            $('#overlay').hide();
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                            });
                        }

                    } else {
                        $('#loader').hide();
                        $('#overlay').hide();
                        Swal.fire({
                            icon: 'warning',
                            title: 'Advertencia',
                            text: response.error,
                        });
                    }

                    $.ajax({
                        type: 'POST',
                        url: 'routes.php?accion=borrar_archivos',
                        data: {
                            csrf_token: csrfToken
                        },
                        success: function (response) {

                        },
                        error: function (error) {
                            $('#loader').hide();
                            $('#overlay').hide();
                            Swal.fire({
                                icon: 'error',
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
                        icon: 'error',
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

    var links = document.querySelectorAll('.nav-link');

    links.forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault(); // Evitar el comportamiento predeterminado del enlace
            // Puedes agregar aquí cualquier lógica adicional que desees realizar al hacer clic en el enlace
        });
    });

    var comboBoxes = document.querySelectorAll('select');

    comboBoxes.forEach(function (comboBox) {
        comboBox.addEventListener('change', function () {

            var id_pestaña = $('.nav-link.active').attr('href');

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



