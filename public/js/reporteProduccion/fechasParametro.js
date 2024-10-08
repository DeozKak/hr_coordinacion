$(document).ready(function () {

    $('#tableParametro').DataTable({
        ordering: false,
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

    $(document).on('click', '#guardarParametro', function(){
        let fechaPrecioInicio = $('#fechaPrecioInicio').val();
        let fechaPrecioFin = $('#fechaPrecioFin').val();
        let metroRes = formatterNumber($('#metroRes').val());
        let norteRes = formatterNumber($('#norteRes').val());
        let caucaRes = formatterNumber($('#caucaRes').val());

        let metroCom = formatterNumber($('#metroCom').val());
        let norteCom = formatterNumber($('#norteCom').val());
        let caucaCom = formatterNumber($('#caucaCom').val());

        let inspeccionInd = formatterNumber($('#inspeccionInd').val());

        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')

        // validamos que las fechas no esten vacias
        if(fechaPrecioFin == "" || fechaPrecioInicio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Las fechas son obligatorias'
            });
            return
        }

        // realizamos las validaciones
        if(fechaPrecioFin < fechaPrecioInicio){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La fecha de fin debe ser posterior a la de inicio'
            });
            return
        }else{
            if(metroRes != "" && norteRes != "" && caucaRes != "" && 
                metroCom != "" && norteCom != "" && caucaCom != "" && inspeccionInd != ""){
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: '¿Desea guardar los cambios?',
                    showCancelButton: true,
                    confirmButtonText: 'Guardar',
                    cancelButtonText: 'Cancelar',
                    customClass: {
                        cancelButton: 'btn btn-danger mx-2',
                        confirmButton: 'btn btn-success mx-2'
                    },
                    buttonsStyling: false 
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.post({
                            url: url,
                            data: {
                                fechaPrecioInicio: fechaPrecioInicio,
                                fechaPrecioFin: fechaPrecioFin,
                                metroRes: metroRes,
                                norteRes: norteRes,
                                caucaRes: caucaRes,
                                metroCom: metroCom,
                                norteCom: norteCom,
                                caucaCom: caucaCom,
                                inspeccionInd: inspeccionInd,
                                _token: token
                            },
                            success: function (response) {
                                switch (response.status) {
                                    case 1:
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Advertencia',
                                            text: 'Las fechas son obligatorias'
                                        });
                                        break;
                                    case 2:
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Advertencia',
                                            text: 'La fecha de inicio no puede ser mayor a la fecha de fin'
                                        });
                                        break;
                                    case 3:
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Advertencia',
                                            text: 'Los datos ingresados no son inválidos'
                                        });
                                        break;
                                    case 4:
                                        Swal.fire({
                                            icon: 'warning',
                                            title: 'Advertencia',
                                            text: `Ya existe un registro en ese rango de fechas. Id: ${response.id}, Fechas: ${response.fecha_inicio} a ${response.fecha_fin}`,
                                        });
                                        break;
                                    case 5:
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Guardado',
                                            text: 'Los datos se han guardado correctamente'
                                        });
                                        setTimeout(() => {
                                            window.location.reload();
                                        }, 2000);
                                        break;
                                    case 6:
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Error al guardar los datos'
                                        });
                                        break;
                                    default:
                                        console.log("Código de respuesta no reconocido");
                                        break;
                                }
                            },
                            error: function (xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'error',
                                    text: error
                                });
                            }
                        })
                    }
                })
            }else{
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Los datos ingresados son invalidos'
                });
                return
            }
        }
    })

    $(document).on('click', '.editarFechasParametros', function(){
        let tr = $(this).closest('tr');
        let id = tr.find('td').eq(0).text();
        let fechaPrecioInicio = tr.find('td').eq(1).text();
        let fechaPrecioFin = tr.find('td').eq(2).text();
        let resMetro = tr.find('td').eq(3).text();
        let resNorte = tr.find('td').eq(4).text();
        let resCauca = tr.find('td').eq(5).text();
        let comMetro = tr.find('td').eq(6).text(); 
        let comNorte = tr.find('td').eq(7).text();
        let comCauca = tr.find('td').eq(8).text();
        let inspeccionInd = tr.find('td').eq(9).text();
        let url = $('#ditarParametro').attr('data-url');

        $('.tituloForm .card-title').text('Editar precios registro #'+id);

        // escondemos el boton de guardar
        $('#guardarParametro').hide();

        // creamos el boton de editar y lo agregamos donde estaba el de editar
        if( $('#actualizarParametro').length == 0 ){
            let nuevoBoton = $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-info mx-2')
                .attr('data-id', tr.find('td').eq(0).text())
                .attr('data-url', url)
                .attr('data-token', $('meta[name="csrf-token"]').attr('content'))
                .attr('id', 'actualizarParametro')
                .text('Editar')
            
            $('.botonesFormulario').append(nuevoBoton)
        }else{
            $('#actualizarParametro').attr('data-id', tr.find('td').eq(0).text())
        }

        // hacemos que haga scroll automaticomente
        $('html, body').animate({
            scrollTop: 0
        }, 'slow');
        
        $('#inspeccionInd').val(inspeccionInd);
        $('#fechaPrecioInicio').val(fechaPrecioInicio);
        $('#fechaPrecioFin').val(fechaPrecioFin);
        $('#metroRes').val(resMetro);
        $('#norteRes').val(resNorte);
        $('#caucaRes').val(resCauca);
        $('#metroCom').val(comMetro);
        $('#norteCom').val(comNorte);
        $('#caucaCom').val(comCauca);

        if (tr.find('.cancelarEdicion').length === 0) {
            let nuevoBoton = $('<button>')
                .attr('class', 'btn btn-danger btn-sm cancelarEdicion')
                .attr('title', 'Cancelar Edición')
                .html('<i class="fas fa-times"></i>');
            
            tr.find('.btn-group').append(nuevoBoton);
        }
    })

    $(document).on('click', '.cancelarEdicion', function() {
        $('.cancelarEdicion').remove();
        let reset = $('.resetForm');
        reset.trigger('click');
        $('.tituloForm .card-title').text('Parametrizar precios');
        // hacemos que haga scroll automaticomente
        $('html, body').animate({
            scrollTop: 0
        }, 'slow');
        $('#guardarParametro').show();
        $('#actualizarParametro').remove();
    });
    

    $(document).on('input', '.inputNumerico', function() {
        let inputVal = $(this).val();

        // Remover todo lo que no sea números
        inputVal = inputVal.replace(/[^\d]/g, '');

        // Formatear el número con separador de miles
        inputVal = inputVal.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

        // Agregar el símbolo de dólar al principio
        $(this).val('$ ' + inputVal);
    });

    $(document).on('click', '#actualizarParametro', function(){
        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')
        let id = $(this).attr('data-id')
        let fechaPrecioInicio = $('#fechaPrecioInicio').val();
        let fechaPrecioFin = $('#fechaPrecioFin').val();
        let metroRes = formatterNumber($('#metroRes').val());
        let norteRes = formatterNumber($('#norteRes').val());
        let caucaRes = formatterNumber($('#caucaRes').val());
        let metroCom = formatterNumber($('#metroCom').val());
        let norteCom = formatterNumber($('#norteCom').val());
        let caucaCom = formatterNumber($('#caucaCom').val());
        let inspeccionInd = formatterNumber($('#inspeccionInd').val());

        // validamos los datos antes de enviarlos
        if(fechaPrecioFin == "" || fechaPrecioInicio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Las fechas son obligatorias'
            });
            return
        }

        // realizamos las validaciones
        if(fechaPrecioFin < fechaPrecioInicio){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La fecha de fin debe ser posterior a la de inicio'
            });
            return
        }else{
            if(metroRes != "" && norteRes != "" && caucaRes != "" && 
                metroCom != "" && norteCom != "" && caucaCom != "" && inspeccionInd != ""){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Advertencia',
                        text: '¿Desea actualizar los datos?',
                        showCancelButton: true,
                        confirmButtonText: 'Guardar',
                        cancelButtonText: 'Cancelar',
                        customClass: {
                            cancelButton: 'btn btn-danger mx-2',
                            confirmButton: 'btn btn-success mx-2'
                        },
                        buttonsStyling: false 
                    }).then((result) => {
                        if (result.isConfirmed) {
                            $.post({
                                url: url,
                                data: {
                                    id: id,
                                    fechaPrecioInicio: fechaPrecioInicio,
                                    fechaPrecioFin: fechaPrecioFin,
                                    metroRes: metroRes,
                                    norteRes: norteRes,
                                    caucaRes: caucaRes,
                                    metroCom: metroCom,
                                    norteCom: norteCom,
                                    caucaCom: caucaCom,
                                    inspeccionInd: inspeccionInd,
                                    _token: token
                                },
                                success: function (response) {
                                    switch (response.status) {
                                        case 1:
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Advertencia',
                                                text: 'Las fechas son obligatorias'
                                            });
                                            break;
                                        case 2:
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Advertencia',
                                                text: 'La fecha de inicio no puede ser mayor a la fecha de fin'
                                            });
                                            break;
                                        case 3:
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Advertencia',
                                                text: 'Los datos ingresados no son inválidos'
                                            });
                                            break;
                                        case 4:
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Advertencia',
                                                text: `Ya existe un registro en ese rango de fechas. Id: ${response.id}, Fechas: ${response.fecha_inicio} a ${response.fecha_fin}`,
                                            });
                                            break;
                                        case 5:
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Guardado',
                                                text: 'Los datos se han actualizado correctamente'
                                            });
                                            setTimeout(function () {
                                                window.location.reload();
                                            },2000);
                                            break;
                                        case 6:
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Error',
                                                text: 'Error al actualizar los datos'
                                            });
                                            break;
                                        case 7:
                                            Swal.fire({
                                                icon: 'warning',
                                                title: 'Advertencia',
                                                text: 'No se realizaron cambios'
                                            });
                                            break;
                                        default:
                                            console.log("Código de respuesta no reconocido");
                                            break;
                                    }
                                },
                                error: function (xhr, status, error) {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'error',
                                        text: error
                                    });
                                } 
                            })
                        }
                    })
            }else{
                Swal.fire({
                    icon: 'warning',
                    title: 'Advertencia',
                    text: 'Los datos ingresados son invalidos'
                });
                return
            }
        }
    })

    function formatterNumber(str){
        if(str == ""){
            return ""
        }else{
            let strPartes = str.split('$');
            strPartes = strPartes[1].split(',');
            strPartes = strPartes.join('');
            let strFinal = parseInt(strPartes);
            return strFinal
        }
    }
})