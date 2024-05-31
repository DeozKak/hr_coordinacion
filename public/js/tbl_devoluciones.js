let codigoHTMLdev = "";
let codigoHTMLges = "";

$(document).ready(function () {

    $('#devoluciones').each(function () {

        let tablaHTMLdev = $(this)[0].outerHTML;

        tablaHTMLdev = tablaHTMLdev.replace(/\s+/g, ' ');

        codigoHTMLdev += tablaHTMLdev;

    });
    
    $('#gestionados').each(function () {

        let tablaHTMLges = $(this)[0].outerHTML;

        tablaHTMLges = tablaHTMLges.replace(/\s+/g, ' ');

        codigoHTMLges += tablaHTMLges;

    });
    
    $('#devoluciones').DataTable({
        scrollCollapse: true,
        scrollX: true,
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

    $('#gestionados').DataTable({
        scrollCollapse: true,
        scrollX: true,
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

    $('div[id="gestionados_wrapper"]').hide();

    $('.btnav').on('click', function () {
        
        const btn = $(this).attr('id');
        console.log(btn);
        if (btn === 'Devoluciones') {
            const devoluciones = $('a[id="Devoluciones"]');
            devoluciones.addClass('active');
            $('div[id="devoluciones_wrapper"]').show();
            $('div[id="gestionados_wrapper"]').hide();
            const gestionados = $('a[id="Gestionados"]');
            gestionados.removeClass('active');
        } else {
            const devoluciones = $('a[id="Devoluciones"]');
            devoluciones.removeClass('active');
            $('div[id="devoluciones_wrapper"]').hide();
            $('div[id="gestionados_wrapper"]').show();
            const gestionados = $('a[id="Gestionados"]');
            gestionados.addClass('active');
        }
    })

    $('#btnGuardar').on('click', function () {

        $('#loader').show();
        $('#overlay').show();

        const url = document.getElementById('exportar_devoluciones').value;
        const csrfToken = document.getElementById('token').value;
        $.ajax({
            type: 'POST',
            url: url,
            data: {
                codigoHTMLdev: codigoHTMLdev,
                codigoHTMLges: codigoHTMLges,
                _token: csrfToken
            },
            success: function (response) {
                console.log(response);
                const nombreArchivo = response.nombreArchivo;
                const urlarchivo = response.ruta;
                if (nombreArchivo !== undefined) {
                    const urlDescarga = urlarchivo + nombreArchivo;
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
            error: function (xhr,error) {
                console.log(xhr.responseText);
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


