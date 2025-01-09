

document.addEventListener('DOMContentLoaded', () => {
    $('#cortes, #municipios, #sedes, #zonas, #causal').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        lengthChange: false,
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

    // btnCrearCorte.addEventListener('click', () => {
    //     CrearCorte();
    // });

    // btnCrearMunicipio.addEventListener('click', () => {
    //     CrearMunicipio();
    // });

    // btnCrearSede.addEventListener('click', () => {
    //     CrearSede();
    // });

    // btnCrearCausal.addEventListener('click', () => {
    //     CrearCausal();
    // });
    //--------------------------------------------------------------------------------------------
    // $('#cortes').on('click', '.btn-success[data-corte-id]', function () {
    //     const corteId = $(this).data('corte-id');
    //     editarCorte(corteId);

    // });

    $('#cortes').on('click', '.btn-primary[data-corte-id]', function () {
        const corteId = $(this).data('corte-id');
        detallesCorte(corteId);

    });

    $('#cortes').on('click', '.btn-secondary[data-corte-id]', function () {
        const corteId = $(this).data('corte-id');
        Graficos(corteId);

    });

    // ------------------------- Jquey Crear Cortes --------------------------------

    $(document).on('click', '#btnCrearCorte', function(){
        $('#idGuardarCorte').val('');  // Limpiar el campo de ID
        $('#nombreCorte').val('');     // Limpiar el campo de nombre
        $('#fecha_inicio').val('');
        $('#fecha_fin').val('');
        $('#meta').val('');
        $('#dobles').val('');
        $('#crearCorteModalLabel').text('Crear Corte');  // Cambiar el título del modal
        $('#crearCorte').text('Crear Corte'); // Cambiar el texto del botón
        $('#crearCorte').removeClass('guardarCambiosCorte').addClass('guardarNuevoCorte');  // Cambiar la clase para detectar el modo
        $('#corteModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '.guardarNuevoCorte', function(){
        let nombre = $('#nombreCorte').val().trim();
        let fecha_inicio = $('#fecha_inicio').val();
        let fecha_fin = $('#fecha_fin').val();
        let meta = $('#meta').val();
        let dobles = $('#dobles').val();
        let token = $('#token').val();

        if (nombre == '' || fecha_inicio == '' || fecha_fin == '' || meta == '' || dobles == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        }else{
            $.ajax({
                url: 'cortes_produccion/store/Corte',
                type: 'POST',
                data: {
                    nombre: nombre,
                    fecha_inicio: fecha_inicio,
                    fecha_fin: fecha_fin,
                    meta: meta,
                    dobles: dobles,
                    _token: token
                },
                success:function(response){
                    console.log(response)
                    if(response.status== 'igual'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Las fechas son iguales',
                            text: response.message,
                        });
                    }else if(response.status== 'fechaMayor'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'La fecha de incio es mayor',
                            text: response.message,
                        });
                    }else if(response.status== 'solapamiento'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Aviso',
                            text: response.message,
                        });
                    }else{
                        $('#corteModal').modal('hide');
                        $('#nombreCorte').val('');
                        $('#fecha_inicio').val('');
                        $('#fecha_fin').val('');
                        $('#meta').val('');
                        $('#dobles').val('');
                        console.log(response)

                        let nuevaFila = `
                            <tr data-id="${response.success.id}">
                                <td class="sorting_1">${response.success.nombre}</td>
                                <td class="dt-type-date">${response.success.fecha_inicio}</td>
                                <td class="dt-type-date">${response.success.fecha_fin}</td>
                                <td class="dt-type-numeric">${response.success.meta}</td>
                                <td class="dt-type-numeric">${response.success.dobles}</td>
                                <td>
                                    <div class="d-flex justify-content-center gap-2;">
                                        <button class="btn btn-info btn-sm abrirCorteModal" data-corte-id="${response.success.id}">Editar</button>&nbsp;
                                        <button class="btn btn-primary btn-sm btndetallesCorte" data-corte-id="${response.success.id}">Detalles</button>&nbsp;
                                        <button class="btn btn-secondary btn-sm" data-corte-id="${response.success.id}">Graficos</button>&nbsp;
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#cortes tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'El corte se ha creado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: 'Ocurrió un error, intente de nuevo.',
                    });
                    console.log(xhr.responseText);
                }

            })
        }
    })


    // ------------------------- Jquey Editar Cortes --------------------------------

    $(document).on('click', '.abrirCorteModal', function(){
        let id = $(this).attr('data-corte-id');
        $('#idGuardarCorte').val(id);
        $('#crearCorteModalLabel').text('Editar Corte');
        $('#crearCorte').text('Guardar cambios');
        let token = $('#token').val();
        $('#crearCorte').removeClass('guardarNuevoCorte').addClass('guardarCambiosCorte');
        let modalCorte = $('#corteModal');
        modalCorte.modal();

        $.ajax({
            url: 'cortes_producction/' + id + '/editCorte',
            type: 'GET',
            data: {
                _token: token,
            },
            success: function(response) {
                $('#nombreCorte').val(response[0].nombre);
                $('#fecha_inicio').val(response[0].fecha_inicio);
                $('#fecha_fin').val(response[0].fecha_fin);
                $('#meta').val(response[0].meta);
                $('#dobles').val(response[0].dobles);
            }

        })
    })

    $(document).on('click', '.guardarCambiosCorte', function(){
        let id = $('#idGuardarCorte').val();
        let nombre = $('#nombreCorte').val().trim();
        let fecha_inicio = $('#fecha_inicio').val();
        let fecha_fin = $('#fecha_fin').val();
        let meta = $('#meta').val();
        let dobles = $('#dobles').val();
        let token = $('#token').val();

        if (nombre == '' || fecha_inicio == '' || fecha_fin == '' || meta == '' || dobles == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        }else{
            $.ajax({
                url: 'cortes_produccion/' + id + '/updateCorte',
                type: 'PUT',
                data: {
                    nombre:nombre,
                    fecha_inicio:fecha_inicio,
                    fecha_fin:fecha_fin,
                    meta:meta,
                    dobles:dobles,
                    _token:token,
                },
                success:function(response){
                    console.log(response)
                    if(response.status=='error'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: response.message
                        });
                    }else if(response.status== 'fechaMayor'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'La fecha de incio es mayor',
                            text: response.message,
                        });
                    }else if(response.status=='fechas_iguales'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: response.message
                        });
                    }else{
                        let modalMunicipio = $('#corteModal')
                        modalMunicipio.modal('hide')
                        let row = $('#cortes tbody tr[data-id="'+response.success.id+'"]')
                        row.find('td:nth-child(1)').text(response.success.nombre)
                        row.find('td:nth-child(2)').text(response.success.fecha_inicio)
                        row.find('td:nth-child(3)').text(response.success.fecha_fin)
                        row.find('td:nth-child(4)').text(response.success.meta)
                        row.find('td:nth-child(5)').text(response.success.dobles)

                        Swal.fire({
                            icon: 'success',
                            title: 'Exito al guardar',
                            text: 'El corte se actualizó con éxito',
                        });
                    }
                }
            })
        }
    })

    // ------------------------- Jquey Crear Municipios --------------------------------

    $(document).on('click', '#btnCrearMunicipio', function(){
        $('#idGuardarMunicipio').val('');  // Limpiar el campo de ID
        $('#nombreMunicipio').val('');     // Limpiar el campo de nombre
        $('#sedeMunicipio').val('');
        $('#zonaMunicipio').val('');
        $('#crearMunicipioModalLabel').text('Crear Municipio');  // Cambiar el título del modal
        $('#crearMunicipio').text('Crear Municipio'); // Cambiar el texto del botón
        $('#crearMunicipio').removeClass('guardarCambiosMunicipio').addClass('guardarNuevoMunicipio');  // Cambiar la clase para detectar el modo
        $('#municipioModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '.guardarNuevoMunicipio', function(){
        let nombre = $('#nombreMunicipio').val().trim();
        let sede = $('#sedeMunicipio').val();
        let zona = $('#zonaMunicipio').val();
        let token = $('#token').val();

        if (nombre == '' || sede == '' || zona == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        }else{
            $.ajax({
                url: 'cortes_produccion/store/Municipio',
                type: 'POST',
                data: {
                    nombre: nombre,
                    sede: sede,
                    zona: zona,
                    _token: token
                },
                success:function(response){
                    console.log(response)
                    if(response.status== 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Municipio duplicado',
                            text: response.message,
                        })
                    }else{
                        $('#municipioModal').modal('hide');
                        $('#nombreMunicipio').val('');
                        $('#sedeMunicipio').val('');
                        $('#zonaMunicipio').val('');
                       

                        let nuevaFila = `
                            <tr data-id="${response.success.id}">
                                <td>${response.success.nombre}</td>
                                <td>${response.success.sede.nombre}</td>
                                <td>${response.success.zona.nombre}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirMunicipioModal" data-municipio-id="${response.success.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusMunicipio" data-municipio-id="${response.success.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#municipios tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'El municipio se ha creado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: 'Ocurrió un error, intente de nuevo.',
                    });
                    console.log(xhr.responseText);
                }

            })
        }
    })


    // ------------------------- Jquey Editar Municipios --------------------------------

    $(document).on('click', '.abrirMunicipioModal', function(){
        let id = $(this).attr('data-municipio-id');
        $('#idGuardarMunicipio').val(id);
        $('#crearMunicipioModalLabel').text('Editar Municipio');
        $('#crearMunicipio').text('Guardar cambios');
        let token = $('#token').val();
        $('#crearMunicipio').removeClass('guardarNuevoMunicipio').addClass('guardarCambiosMunicipio');
        let modalMunicipio = $('#municipioModal');
        modalMunicipio.modal();

        $.ajax({
            url: 'cortes_producction/' + id + '/editMunicipio',
            type: 'GET',
            data: {
                _token: token,
            },
            success: function(response) {
                $('#nombreMunicipio').val(response[0].nombre);
                $('#sedeMunicipio').val(response[0].id_sede);
                $('#zonaMunicipio').val(response[0].id_zona);
            }

        })
    })

    $(document).on('click', '.guardarCambiosMunicipio', function(){
        let id = $('#idGuardarMunicipio').val();
        let nombre = $('#nombreMunicipio').val().trim();
        let sede = $('#sedeMunicipio').val();
        let zona = $('#zonaMunicipio').val();
        let token = $('#token').val();

        if (nombre == '' || sede == '' || zona == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        }else{
            $.ajax({
                url: 'cortes_produccion/' + id + '/updateMunicipio',
                type: 'PUT',
                data: {
                    nombre:nombre,
                    sede:sede,
                    zona:zona,
                    _token:token,
                },
                success:function(response){
                    console.log(response)
                    if(response.status== 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Municipio duplicado',
                            text: response.message,
                        })
                    }else{
                        let modalMunicipio = $('#municipioModal')
                        modalMunicipio.modal('hide')
                        let row = $('#municipios tbody tr[data-id="'+response.success.id+'"]')
                        row.find('td:nth-child(1)').text(response.success.nombre)
                        row.find('td:nth-child(2)').text(response.success.sede.nombre)
                        row.find('td:nth-child(3)').text(response.success.zona.nombre)

                        Swal.fire({
                            icon: 'success',
                            title: 'Exito al guardar',
                            text: 'El municipio se actualizó con éxito',
                        });
                    }
                }
            })
        }
    })


    // ------------------------- Camabiar Estado Municipios -----------------------------

    $(document).on('click', '#btnChangeStatusMunicipio', function(){
        let id = $(this).attr('data-municipio-id')
        let url = $('#cambiarEstadoMunicipio').val()
        let token = $('#token').val()

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: id,
                _token:token,
            },success:function(response){
                console.log(response)
                let row = $('#municipios tbody tr[data-id="'+response.success.id+'"]')
                if(response.success.status == 0){
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                }else{
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }
        })
    })


    // ------------------------- Jquey Crear Sedes --------------------------------

    // Abrir el modal en modo creación
    $(document).on('click', '#btnCrearSede', function(){
        $('#idGuardarSede').val('');  // Limpiar el campo de ID
        $('#nombreSede').val('');     // Limpiar el campo de nombre
        $('#crearSedeModalLabel').text('Crear Sede');  // Cambiar el título del modal
        $('#crearSede').text('Crear Sede'); // Cambiar el texto del botón
        $('#crearSede').removeClass('guardarCambiosSede').addClass('guardarNuevoSede');  // Cambiar la clase para detectar el modo
        $('#sedeModal').modal('show');  // Mostrar el modal
    });

    // Enviar el formulario para crear una nueva sede
    $(document).on('click', '.guardarNuevoSede', function(){
        let nombre = $('#nombreSede').val().trim();
        let token = $('#token').val();

        if (nombre == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        } else {
            $.ajax({
                url: 'cortes_produccion/store/Sede',  // URL de la ruta para almacenar la sede
                type: 'POST',
                data: {
                    nombre: nombre,
                    _token: token,
                },
                success: function(response) {
                    console.log(response)
                    if(response.status== 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sede duplicado',
                            text: response.message,
                        })
                    }else{
                        // Ocultar el modal y limpiar campos
                        $('#sedeModal').modal('hide');
                        $('#nombreSede').val('');
                        console.log()

                        // Agregar la nueva sede a la tabla sin recargar la página
                        let nuevaFila = `
                            <tr data-id="${response.success.id}">
                                <td>${response.success.nombre}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirSedeModal" data-sede-id="${response.success.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusSede" data-sede-id="${response.success.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#sedes tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'La sede se ha creado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: 'Ocurrió un error, intente de nuevo.',
                    });
                    console.log(xhr.responseText);
                }
            });
        }
    });


    // ------------------------- Jquey Editar Sedes --------------------------------

    $(document).on('click', '.abrirSedeModal', function(){
        let id = $(this).attr('data-sede-id')
        $('#idGuardarSede').val(id)
        $('#crearSedeModalLabel').text('Editar sede')
        $('#crearSede').text('Guardar cambios')
        $('#crearSede').removeClass('guardarNuevo').addClass('guardarCambios');
        let modalSede = $('#sedeModal')
        modalSede.modal()

        $.ajax({
            url: 'cortes_producction/' + id + '/editSede',
            type: 'GET',
            success:function (response){
                $('#nombreSede').val(response[0].nombre)
            }
        })
    })

    $(document).on('click', '.guardarCambios', function(){
        let nombre = $('#nombreSede').val().trim()
        let id = $('#idGuardarSede').val()
        let token = $('#token').val()

        if(nombre == '' ){
            Swal.fire({
                type: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacio',
            });
        }else{
            $.ajax({
                url:'cortes_produccion/' + id + '/updateSede',
                type: 'PUT',
                data: {
                    nombre:nombre,
                    _token:token,
                },
                success:function(response){
                    console.log(response)
                    if(response.status == 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Sede duplicada',
                            text: response.message,
                        })
                    }else{
                        let modalSede = $('#sedeModal')
                        modalSede.modal('hide')
                        let row = $('#sedes tbody tr[data-id="'+response.success.id+'"]')
                        row.find('td:nth-child(1)').text(response.success.nombre)
                        Swal.fire({
                            icon: 'success',
                            title: 'Exito al guardar',
                            text: 'La sede se actualizó con éxito',
                        });
                    }
                }
            })
        }
    })

    // ------------------------- Camabiar Estado Sedes -----------------------------

    $(document).on('click', '#btnChangeStatusSede', function(){
        let id = $(this).attr('data-sede-id')
        let url = $('#cambiarEstadoSede').val()
        let token = $('#token').val()

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: id,
                _token:token,
            },success:function(response){
                let row = $('#sedes tbody tr[data-id="'+response.success.id+'"]')
                if(response.success.status == 0){
                    row.find('td:nth-child(2) #btnChangeStatusSede').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                }else{
                    row.find('td:nth-child(2) #btnChangeStatusSede').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }
        })
    })

    // ------------------------- Jquey Crear Zonas --------------------------------

    // Abrir el modal en modo creación
    $(document).on('click', '#btnCrearZona', function(){
        $('#idGuardarZona').val('');  // Limpiar el campo de ID
        $('#nombreZona').val('');     // Limpiar el campo de nombre
        $('#crearZonaModalLabel').text('Crear Zona');  // Cambiar el título del modal
        $('#crearZona').text('Crear Zona'); // Cambiar el texto del botón
        $('#crearZona').removeClass('guardarCambiosZona').addClass('guardarNuevoZona');  // Cambiar la clase para detectar el modo
        $('#zonaModal').modal('show');  // Mostrar el modal
    });

    // Enviar el formulario para crear una nueva zona
    $(document).on('click', '.guardarNuevoZona', function(){
        let nombre = $('#nombreZona').val().trim();
        let token = $('#token').val();

        if (nombre == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        } else {
            $.ajax({
                url: 'cortes_produccion/store/Zona',  // URL de la ruta para almacenar la zona
                type: 'POST',
                data: {
                    nombre: nombre,
                    _token: token,
                },
                success: function(response) {
                    console.log(response)
                    if(response.status== 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Zona duplicado',
                            text: response.message,
                        })
                    }else{
                        // Ocultar el modal y limpiar campos
                        $('#zonaModal').modal('hide');
                        $('#nombreZona').val('');

                        // Agregar la nueva sede a la tabla sin recargar la página
                        let nuevaFila = `
                            <tr data-id="${response.success.id}">
                                <td>${response.success.nombre}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirZonaModal" data-zona-id="${response.success.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusZona" data-zona-id="${response.success.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#zonas tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'La zona se ha creado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: 'Ocurrió un error, intente de nuevo.',
                    });
                    console.log(xhr.responseText);
                }
            });
        }
    });


    // ------------------------- Jquey Editar Zonas --------------------------------

    $(document).on('click', '.abrirZonaModal', function(){
        let id = $(this).attr('data-zona-id')
        $('#idGuardarZona').val(id)
        $('#crearZonaModalLabel').text('Editar zona')
        $('#crearZona').text('Guardar cambios')
        $('#crearZona').addClass('guardarCambiosZona')
        let modalZona = $('#zonaModal')
        modalZona.modal()

        $.ajax({
            url: 'cortes_producction/' + id + '/editZona',
            type: 'GET',
            success:function(response){
                $('#nombreZona').val(response[0].nombre)
            }
        })
    })

    $(document).on('click', '.guardarCambiosZona', function(){
        let nombre = $('#nombreZona').val().trim()
        let id = $('#idGuardarZona').val()
        let token = $('#token').val()

        if(nombre == ''){
            Swal.fire({
                type: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacio',
            });
        }else{
            $.ajax({
                url:'cortes_produccion/' + id + '/updateZona',
                type: 'PUT',
                data: {
                    nombre:nombre,
                    _token:token,
                },
                success:function(response){
                    console.log(response)
                    if(response.status == 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Zona duplicada',
                            text: response.message,
                        })
                    }else{
                        let modalZona = $('#zonaModal')
                        modalZona.modal('hide')
                        let row = $('#zonas tbody tr[data-id="'+response.success.id+'"]')
                        row.find('td:nth-child(1)').text(response.success.nombre)
                        Swal.fire({
                            icon: 'success',
                            title: 'Exito al guardar',
                            text: 'La zona se actualizó con éxito',
                        });
                    }
                }
            })
        }
    })


    // ------------------------- Jquey Cambiar Estado Zona --------------------------------

    $(document).on('click', '#btnChangeStatusZona', function(){
        let id = $(this).attr('data-zona-id')
        let url = $('#cambiarEstadoZona').val()
        let token = $('#token').val()

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: id,
                _token:token,
            },success:function(response){
                let row = $('#zonas tbody tr[data-id="'+response.success.id+'"]')
                if(response.success.status == 0){
                    row.find('td:nth-child(2) #btnChangeStatusZona').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                }else{
                    row.find('td:nth-child(2) #btnChangeStatusZona').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }
        })
    })

    // ------------------------- Jquey Crear Causal --------------------------------

    // Abrir el modal en modo creación
    $(document).on('click', '#btnCrearCausal', function(){
        $('#idGuardarCausal').val('');  // Limpiar el campo de ID
        $('#nombreCausal').val('');     // Limpiar el campo de nombre
        $('#crearCausalModalLabel').text('Crear Causal');  // Cambiar el título del modal
        $('#crearCausal').text('Crear Causal'); // Cambiar el texto del botón
        $('#crearCausal').removeClass('guardarCambiosCausal').addClass('guardarNuevoCausal');  // Cambiar la clase para detectar el modo
        $('#causalModal').modal('show');  // Mostrar el modal
    });

    // Enviar el formulario para crear una nueva causal
    $(document).on('click', '.guardarNuevoCausal', function(){
        let nom_causal = $('#nombreCausal').val().trim();
        let token = $('#token').val();

        if (nom_causal == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacío',
            });
        } else {
            $.ajax({
                url: 'cortes_produccion/store/Causal',  // URL de la ruta para almacenar la causal
                type: 'POST',
                data: {
                    nom_causal: nom_causal,
                    _token: token,
                },
                success: function(response) {
                    console.log(response)
                    if(response.status== 'exist'){


                    }else{
                        // Ocultar el modal y limpiar campos
                        $('#causalModal').modal('hide');
                        $('#nombreCausal').val('');
                        console.log(response)

                        // Agregar la nueva sede a la tabla sin recargar la página
                        let nuevaFila = `
                            <tr data-id="${response.success.id}">
                                <td>${response.success.nom_causal}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirCausalModal" data-causal-id="${response.success.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusCausal" data-causal-id="${response.success.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#causal tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        // Mostrar mensaje de éxito
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: 'El causal se ha creado correctamente.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    }

                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error al guardar',
                        text: 'Ocurrió un error, intente de nuevo.',
                    });
                    console.log(xhr.responseText);
                }
            });
        }
    });

    // ------------------------- Jquey Editar Causal --------------------------------

    $(document).on('click', '.abrirCausalModal', function(){
        let id = $(this).attr('data-causal-id')
        $('#idGuardarCausal').val(id)
        $('#crearCausalModalLabel').text('Editar causal')
        $('#crearCausal').text('Guardar cambios')
        $('#crearCausal').addClass('guardarCambiosCausal')
        let modalCausal = $('#causalModal')
        modalCausal.modal()

        $.ajax({
            url: 'cortes_producction/' + id + '/editCausal',
            type: 'GET',
            success:function(response){
                $('#nombreCausal').val(response[0].nom_causal)
            }
        })
    })

    $(document).on('click', '.guardarCambiosCausal', function(){
        let nombre = $('#nombreCausal').val().trim()
        let id = $('#idGuardarCausal').val()
        let token = $('#token').val()

        if(nombre == ''){
            Swal.fire({
                type: 'warning',
                title: 'Error al guardar',
                text: 'Complete el campo vacio',
            });
        }else{
            $.ajax({
                url:'cortes_produccion/' + id + '/updateCausal',
                type: 'PUT',
                data: {
                    nombre:nombre,
                    _token:token,
                },
                success:function(response){
                    console.log(response)
                    if(response.status == 'exist'){
                        Swal.fire({
                            icon: 'warning',
                            title: 'Causal duplicado',
                            text: response.message,
                        })
                    }else{
                        let modalCausal = $('#causalModal')
                        modalCausal.modal('hide')
                        let row = $('#causal tbody tr[data-id="'+response.success.id+'"]')
                        row.find('td:nth-child(1)').text(response.success.nom_causal)
                        Swal.fire({
                            icon: 'success',
                            title: 'Exito al guardar',
                            text: 'El causal se actualizó con éxito',
                        });
                    }
                }
            })
        }
    })


    // ------------------------- Jquey Cambiar Estado Causal --------------------------------

    $(document).on('click', '#btnChangeStatusCausal', function(){
        let id = $(this).attr('data-causal-id')
        let url = $('#cambiarEstadoCausal').val()
        let token = $('#token').val()

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: id,
                _token:token,
            },success:function(response){
                let row = $('#causal tbody tr[data-id="'+response.success.id+'"]')
                if(response.success.status == 0){
                    row.find('td:nth-child(2) #btnChangeStatusCausal').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                }else{
                    row.find('td:nth-child(2) #btnChangeStatusCausal').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }
        })
    })

    $(document).on('input', '.inputNumericoMeta', function(){
        this.value = this.value.replace(/[^0-9]/g, ''); // Permitir solo números
        if (this.value.length > 3) {
            this.value = this.value.slice(0, 3); // Limitar a 3 dígitos
        }
    })

    $(document).on('input', '.inputNumericoDobles', function(){
        this.value = this.value.replace(/[^0-9]/g, ''); // Permitir solo números
        if (this.value.length > 2) {
            this.value = this.value.slice(0, 2); // Limitar a 3 dígitos
        }
    })

});

// function CrearCorte() {
//     const campos = document.querySelectorAll('#CorteModal input');
//     campos.forEach(campo => campo.value = '');
//     $('#crearCorteModalLabel').text('Crear Corte');
//     $('#crear').text('Crear');
//     $('#CorteModal').modal('show');
//     /* Validaciones inputs cortes */

//     ValidarFormularioCortes();

//     const btncrear = document.getElementById('crear');
//     // btnCrear.addEventListener('click', validarFormulario);
//     function validarFormulario() {
//         // Obtener todos los campos del formulario
//         const camposFormulario = document.querySelectorAll('#CorteModal input');

//         // Validar si todos los campos están llenos
//         const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
//         if (todosLosCamposLlenos) {
//             EnviarServidorStore("Corte");
// /*             camposFormulario.forEach(campo => campo.value = '');
//  */        } else {
//             // Si algún campo está vacío, mostrar un mensaje de error
//             alert('Por favor, complete todos los campos.');
//         }
//     }
// }

// function editarCorte(id) {

//     $.ajax({
//         url: 'cortes_producction/' + id + '/editCorte', // Ruta para obtener los datos del corte
//         method: 'GET',
//         success: function (response) {
//             $('#crearCorteModalLabel').text('Editar Corte');
//             $('#crear').text('Guardar Cambios');
//             $('#nombre').val(response[0].nombre);
//             $('#fecha_inicio').val(response[0].fecha_inicio);
//             $('#fecha_fin').val(response[0].fecha_fin);
//             $('#meta').val(response[0].meta);
//             $('#dobles').val(response[0].dobles);
//             $('#CorteModal').modal('show');
//         }, error: function (xhr, status, error) {
//             console.error("Error al obtener los datos del corte.");
//         }
//     });
//     ValidarFormularioCortes();
//     const btnCrear = document.getElementById('crear');
//     btnCrear.addEventListener('click', validarFormulario);
//     function validarFormulario() {
//         const camposFormulario = document.querySelectorAll('#CorteModal input');
//         const todosLosCamposLlenos = Array.from(camposFormulario).every(campo => campo.value.trim() !== '');
//        console.log(todosLosCamposLlenos);
//         if (todosLosCamposLlenos) {
//             EnviarServidorUpdate("Corte", id);
//            /*  camposFormulario.forEach(campo => campo.value = ''); */
//         } else {
//             alert('Por favor, complete todos los campos.');
//         }
//     }
// }

function ValidarFormularioCortes() {
    const inputMeta = document.querySelectorAll('#meta');
    const inputDobles = document.querySelectorAll('#dobles');
    inputDobles.forEach((input) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 2);
        })
    })
    inputMeta.forEach((input) => {
        input.addEventListener('input', function () {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 3);
        });

        const inputNombre = document.getElementById('nombreCorte');
        inputNombre.addEventListener('input', function () {
            if (this.value.length > 30) {
                this.value = this.value.slice(0, 30);
            }
        });
    });

    const fechaInicioInput = document.getElementById('fecha_inicio');
    const fechaFinInput = document.getElementById('fecha_fin');

    // Encontrar la fecha de fin máxima entre los rangos y la fecha actual
    let fechaMinima = new Date(); // Comenzar con la fecha actual
    rangosFechasExistentes.forEach(rango => {
        const rangoFin = new Date(rango.fecha_fin);
        if (rangoFin > fechaMinima) {
            fechaMinima = rangoFin; // Actualizar si se encuentra una fecha mayor
        }
    });

    // Sumar un día para garantizar que la nueva fecha sea posterior al último corte
    fechaMinima.setDate(fechaMinima.getDate() + 2);

    // Formatear la fecha mínima
    const diaMin = ("0" + fechaMinima.getDate()).slice(-2);
    const mesMin = ("0" + (fechaMinima.getMonth() + 1)).slice(-2);
    const fechaMinimaFormateada = fechaMinima.getFullYear() + "-" + mesMin + "-" + diaMin;



    // Validar fechas al cambiar
    fechaInicioInput.addEventListener('input', validarFechas);
    fechaFinInput.addEventListener('input', validarFechas);

    function validarFechas() {

        const fechaInicioSeleccionada = new Date(fechaInicioInput.value);
        const fechaFinSeleccionada = new Date(fechaFinInput.value);

        // Validar que fecha inicio no sea mayor a fecha fin
        if (fechaInicioSeleccionada > fechaFinSeleccionada) {
            alert('Fecha fin debe ser posterior a fecha inicio.');
            fechaFinInput.value = ''; // Limpiar fecha fin si es inválido
        } else {
            fechaFinInput.setCustomValidity(''); // Limpiar mensaje de error si es válido
        }
    }
}

function detallesCorte(id) {
    window.location.href = `produccion/detalles_corte/${id}`;
}

function Graficos(id) {
    window.location.href = `produccion?id=${id}`;
}
