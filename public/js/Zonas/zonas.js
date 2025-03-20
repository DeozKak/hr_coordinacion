document.addEventListener('DOMContentLoaded', () => {

    $('#municipios, #sedes').DataTable({
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


// ------------------------- Jquey Crear Municipios --------------------------------

    $(document).on('click', '#btnCrearMunicipio', function () {
        $('#idGuardarMunicipio').val('');  // Limpiar el campo de ID
        $('#nombreMunicipio').val('');     // Limpiar el campo de nombre
        $('#sedeMunicipio').val('');
        $('#zonaMunicipio').val('');
        $('#crearMunicipioModalLabel').text('Crear Municipio');  // Cambiar el título del modal
        $('#crearMunicipio').text('Crear Municipio'); // Cambiar el texto del botón
        $('#crearMunicipio').removeClass('guardarCambiosMunicipio').addClass('guardarNuevoMunicipio');  // Cambiar la clase para detectar el modo
        $('#municipioModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '.guardarNuevoMunicipio', function () {
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
        } else {
            $.ajax({
                url: 'zonas/store/Municipio',
                type: 'POST',
                data: {
                    nombre: nombre,
                    sede: sede,
                    zona: zona,
                    _token: token
                },
                success: function (response) {
                    console.log(response)
                    if (response.status == 'exist') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Municipio duplicado',
                            text: response.message,
                        })
                    } else {
                        $('#municipioModal').modal('hide');
                        $('#nombreMunicipio').val('');
                        $('#sedeMunicipio').val('');
                        $('#zonaMunicipio').val('');
                        console.log(response)

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
                error: function (xhr, status, error) {
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
            url: 'zonas/' + id + '/editMunicipio',
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
                url: 'zonas/' + id + '/updateMunicipio',
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



})


