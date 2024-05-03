var codigoHTML = "";
$(document).ready(function () {

    $('#devoluciones').each(function () {

        var tablaHTML = $(this)[0].outerHTML;

        tablaHTML = tablaHTML.replace(/\s+/g, ' ');

        codigoHTML += tablaHTML;

    });

    $('#devoluciones').DataTable();

    $('#btnGuardar').on('click', function () {

        $('#loader').show();
        $('#overlay').show();


        $.ajax({
            type: 'POST',
            url: 'routes.php?accion=exportar_tabla',
            data: {
                codigoHTML: codigoHTML,
            },
            success: function (response) {
                var nombreArchivo = response.nombreArchivo;
                if (nombreArchivo !== undefined) {
                    var urlDescarga = 'Controlador/Archivos/' + nombreArchivo;
                    window.location.href = urlDescarga;
                    $('#loader').hide();
                    $('#overlay').hide();
                }else{
                $('#loader').hide();
                $('#overlay').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                });}

            },
            error: function (error) {
                $('#loader').hide();
                $('#overlay').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                });

            }
        });

    })

    $('#btnGuardar_Gestionados').on('click', function () {

        $('#loader').show();
        $('#overlay').show();


        $.ajax({
            type: 'POST',
            url: 'routes.php?accion=exportar_tabla_gestionados',
            data: {
                codigoHTML: codigoHTML,
            },
            success: function (response) {
               
                var nombreArchivo = response.nombreArchivo;
                if (nombreArchivo !== undefined) {
                    var urlDescarga = 'Controlador/Archivos/' + nombreArchivo;
                    window.location.href = urlDescarga;
                    $('#loader').hide();
                    $('#overlay').hide();
                }else{
                $('#loader').hide();
                $('#overlay').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                });}


            },
            error: function (error) {
                $('#loader').hide();
                $('#overlay').hide();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: "error al exportar archivo, intente de nuevo o contacte al administrador del sistema",
                });

            }
        });

    })




})


