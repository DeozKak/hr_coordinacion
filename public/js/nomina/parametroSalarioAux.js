$(document).ready(function () {

    $('#tableParametroSalarioAux').DataTable({
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

    $(document).on('click', '#guardarParametroSalarioAux', function(){
        let fechaSalAuxInicio = $('#fechaSalAuxInicio').val();
        let fechaSalAuxFin = $('#fechaSalAuxFin').val();
        let salMin = formatterNumber($('#salMin').val());
        let auxTrans = formatterNumber($('#auxTrans').val());
        let salud = formatterPorcentaje($('#salud').val());
        let pension = formatterPorcentaje($('#pension').val());
        let arl = formatterPorcentaje($('#arl').val());
        let caja = formatterPorcentaje($('#caja').val());
        let prima = formatterPorcentaje($('#prima').val());
        let cesantias = formatterPorcentaje($('#cesantias').val());
        let intCesantias = formatterPorcentaje($('#intCesantias').val());
        let vacaciones = formatterPorcentaje($('#vacaciones').val());

        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')

        // validamos que las fechas no esten vacias
        if(fechaSalAuxFin == "" || fechaSalAuxInicio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Las fechas son obligatorias'
            });
            return
        }

        // realizamos las validaciones
        if(fechaSalAuxFin < fechaSalAuxInicio){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La fecha de fin debe ser posterior a la de inicio'
            });
            return
        }else{
            if(salMin != "" && auxTrans != "" && salud != "" && 
                pension != "" && arl != "" && caja != "" && 
                prima != "" && cesantias != "" && intCesantias != "" && vacaciones != ""
            ){
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
                                fechaSalAuxInicio: fechaSalAuxInicio,
                                fechaSalAuxFin: fechaSalAuxFin,
                                salMin: salMin,
                                auxTrans: auxTrans,
                                salud: salud,
                                pension: pension,
                                arl: arl,
                                caja: caja,
                                prima: prima,
                                cesantias: cesantias,
                                intCesantias: intCesantias,
                                vacaciones: vacaciones,
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
        let fechaSalAuxInicio = tr.find('td').eq(1).text();
        let fechaSalAuxFin = tr.find('td').eq(2).text();
        let salMin = tr.find('td').eq(3).text();
        let auxTrans = tr.find('td').eq(4).text();
        let salud = tr.find('td').eq(5).text();
        let pension = tr.find('td').eq(6).text();
        let arl = tr.find('td').eq(7).text();
        let caja = tr.find('td').eq(8).text();
        let prima = tr.find('td').eq(9).text();
        let cesantias = tr.find('td').eq(10).text();
        let intCesantias = tr.find('td').eq(11).text();
        let vacaciones = tr.find('td').eq(12).text();
        let url = $('#editarParametroSalarioAux').attr('data-url');

        $('.tituloFormSalAux .card-title').text('Editar salario minimo - auxilio transporte registro #'+id);

        // escondemos el boton de guardar
        $('#guardarParametroSalarioAux').hide();

        // creamos el boton de editar y lo agregamos donde estaba el de editar
        if( $('#actualizarParametroSalAux').length == 0 ){
            let nuevoBoton = $('<button>')
                .attr('type', 'button')
                .addClass('btn btn-info mx-2')
                .attr('data-id', tr.find('td').eq(0).text())
                .attr('data-url', url)
                .attr('data-token', $('meta[name="csrf-token"]').attr('content'))
                .attr('id', 'actualizarParametroSalAux')
                .text('Editar')
            
            $('.botonesFormularioSalAux').append(nuevoBoton)
        }else{
            $('#actualizarParametroSalAux').attr('data-id', tr.find('td').eq(0).text())
        }

        // hacemos que haga scroll automaticomente
        $('html, body').animate({
            scrollTop: 0
        }, 'slow');
        
        $('#fechaSalAuxInicio').val(fechaSalAuxInicio);
        $('#fechaSalAuxFin').val(fechaSalAuxFin);
        $('#salMin').val(salMin);
        $('#auxTrans').val(auxTrans);
        $('#salud').val(salud);
        $('#pension').val(pension);
        $('#arl').val(arl);
        $('#caja').val(caja);
        $('#prima').val(prima);
        $('#cesantias').val(cesantias);
        $('#intCesantias').val(intCesantias);
        $('#vacaciones').val(vacaciones);

        if (tr.find('.cancelarEdicionSalAux').length === 0) {
            let nuevoBoton = $('<button>')
                .attr('class', 'btn btn-danger btn-sm cancelarEdicionSalAux')
                .attr('title', 'Cancelar Edición')
                .html('<i class="fas fa-times"></i>');
            
            tr.find('.btn-group').append(nuevoBoton);
        }
    })

    $(document).on('click', '.cancelarEdicionSalAux', function() {
        $('.cancelarEdicionSalAux').remove();
        let reset = $('.resetFormSalAux');
        reset.trigger('click');
        $('.tituloFormSalAux .card-title').text('Parametrizar salario minimo - auxilio de transporte');
        // hacemos que haga scroll automaticomente
        $('html, body').animate({
            scrollTop: 0
        }, 'slow');
        $('#guardarParametroSalarioAux').show();
        $('#actualizarParametroSalAux').remove();
    });

    $(document).on('click', '#actualizarParametroSalAux', function(){
        let url = $(this).attr('data-url')
        let token = $(this).attr('data-token')
        let id = $(this).attr('data-id')
        let fechaSalAuxInicio = $('#fechaSalAuxInicio').val();
        let fechaSalAuxFin = $('#fechaSalAuxFin').val();
        let salMin = formatterNumber($('#salMin').val());
        let auxTrans = formatterNumber($('#auxTrans').val());
        let salud = formatterPorcentaje($('#salud').val());
        let pension = formatterPorcentaje($('#pension').val());
        let arl = formatterPorcentaje($('#arl').val());
        let caja = formatterPorcentaje($('#caja').val());
        let prima = formatterPorcentaje($('#prima').val());
        let cesantias = formatterPorcentaje($('#cesantias').val());
        let intCesantias = formatterPorcentaje($('#intCesantias').val());
        let vacaciones = formatterPorcentaje($('#vacaciones').val());

        debugger

        // validamos los datos antes de enviarlos
        if(fechaSalAuxFin == "" || fechaSalAuxInicio == ""){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Las fechas son obligatorias'
            });
            return
        }

        // realizamos las validaciones
        if(fechaSalAuxFin < fechaSalAuxInicio){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'La fecha de fin debe ser posterior a la de inicio'
            });
            return
        }else{
            if(salMin != "" && auxTrans != "" && salud != "" && 
                pension != "" && arl != "" && caja != "" && 
                prima != "" && cesantias != "" && intCesantias != "" && vacaciones != ""){
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
                                    fechaSalAuxInicio: fechaSalAuxInicio,
                                    fechaSalAuxFin: fechaSalAuxFin,
                                    salMin: salMin,
                                    auxTrans: auxTrans,
                                    salud: salud,
                                    pension: pension,
                                    arl: arl,
                                    caja: caja,
                                    prima: prima,
                                    cesantias: cesantias,
                                    intCesantias: intCesantias,
                                    vacaciones: vacaciones,
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

    $(document).on('input', '.inputNumerico', function() {
        let inputVal = $(this).val();

        inputVal = inputVal.replace(/[^\d]/g, '');

        inputVal = inputVal.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
       
        $(this).val('$ ' + inputVal);
    });

    $(document).on('input', '.inputPorcentaje', function() {
        let inputVal = $(this).val();
    
        // Permitir solo números y puntos (para decimales escritos manualmente)
        inputVal = inputVal.replace(/[^\d.]/g, '');
    
        // Mostrar el valor con el símbolo de porcentaje
        $(this).val('%' + inputVal);
    });

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

    function formatterPorcentaje(str){
        if(str == ""){
            return ""
        }else{
            let strPartes = str.split('%');
            let strfinal = strPartes[1]
            return strfinal
        }
    }
})