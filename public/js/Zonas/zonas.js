let tomSelectMunicipio;
document.addEventListener('DOMContentLoaded', () => {

    $('#municipios,#Barrios,#sedes,#grupos,#subgrupos').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        lengthChange: false,
        info: false,
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
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

                    alerta('error','Error al guardar','Ocurrió un error, intente de nuevo.')

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


    // ------------------------- Cambiar Estado Municipios -----------------------------

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

    // ------------------------- Jquery Crear Barrios --------------------------------

    $(document).on('click', '#btnCrearBarrio', function () {
      // Variable global para almacenar la instancia

        $('#idGuardarBarrio').val('');  // Limpiar el campo de ID
        $('#barrio').val('');           // Limpiar el campo de nombre del barrio
        if (tomSelectMunicipio) {
            tomSelectMunicipio.clear(); // Limpiar el select correctamente
        }
        $('#crearBarrioModalLabel').text('Crear Barrio');  // Cambiar el título del modal
        $('#crearBarrio').text('Crear Barrio'); // Cambiar el texto del botón
        $('#crearBarrio').removeClass('guardarCambiosBarrio').addClass('guardarNuevoBarrio');  // Cambiar la clase para detectar el modo
        $('#BarrioModal').modal('show');  // Mostrar el modal

        if (tomSelectMunicipio) {
            tomSelectMunicipio.clear();
            tomSelectMunicipio.destroy();
        }

        tomSelectMunicipio =new TomSelect("#municipioBarrio", {
            maxItems: 1,
            create: false,  // Evita que los usuarios agreguen nuevas zonas manualmente
            placeholder: "Seleccione un municipio",
            persist: false
        });
    });

    $(document).on('click', '.guardarNuevoBarrio', function () {
        let barrio = $('#barrio').val();
        let token = $('#token').val();
        let municipio = $('#municipioBarrio').val();

            $.ajax({
                url: 'zonas/store/Barrio',
                type: 'POST',
                data: {
                    barrio: barrio,
                    municipio: municipio,
                    _token: token
                },
                success: function (response) {
                        $('#BarrioModal').modal('hide');
                        $('#barrio').val('');



                        let nuevaFila = `
                            <tr data-id="${response.ok.id}">
                                <td>${response.ok.barrio}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirBarrioModal" data-barrio-id="${response.ok.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm btnChangeStatusBarrio" data-barrio-id="${response.ok.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                        $('#Barrios tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                        topAlert('success',response.success);
                },
                error: function (xhr,error,status) {

                    alerta('error','Error',xhr.responseJSON.error);


                }
            });

    });


    // ------------------------- Jquery Editar Barrios --------------------------------

    let tomSelectMunicipio; // Variable global para almacenar la instancia de TomSelect

    $(document).on('click', '.abrirBarrioModal', function () {
        let id = $(this).attr('data-barrio-id');
        $('#idGuardarBarrio').val(id);
        $('#crearBarrioModalLabel').text('Editar Barrio');
        $('#crearBarrio').text('Guardar cambios');
        $('#crearBarrio').removeClass('guardarNuevoBarrio').addClass('guardarCambiosBarrio');
        let token = $('#token').val();
        let modalBarrio = $('#BarrioModal');

        // Mostrar el modal
        modalBarrio.modal('show');

        // Hacer la petición AJAX para obtener los datos del barrio
        $.ajax({
            url: 'zonas/' + id + '/editBarrio',
            type: 'GET',
            data: { _token: token },
            success: function (response) {
                $('#barrio').val(response[0].barrio);
               
                // Verificar si TomSelect ya está inicializado y limpiarlo antes de actualizar
                if (tomSelectMunicipio) {
                    tomSelectMunicipio.clear();
                    tomSelectMunicipio.destroy();
                }

                // Inicializar TomSelect para Municipio y seleccionar la opción correspondiente
                tomSelectMunicipio = new TomSelect("#municipioBarrio", {
                    maxItems: 1,
                    create: false,
                    placeholder: "Seleccione un municipio",
                    persist: false
                });

                // Establecer el valor del municipio en el select
                tomSelectMunicipio.addOption({ value: response[0].id_municipio, text: response[0].municipio_nombre });
                tomSelectMunicipio.setValue(response[0].municipios[0].id);
            },error: function (xhr,error,status) {
                alerta('error','Error',xhr.responseJSON.error)
            }
        });
    });

    // ------------------------- Guardar Cambios en Barrio ------------------------------

    $(document).on('click', '.guardarCambiosBarrio', function () {
        let id = $('#idGuardarBarrio').val();
        let barrio = $('#barrio').val().trim();
        let municipio = $('#municipioBarrio').val();
        let token = $('#token').val();

            $.ajax({
                url: 'zonas/' + id + '/updateBarrio',
                type: 'PUT',
                data: {
                    barrio: barrio,
                    municipio: municipio,
                    _token: token
                },
                success: function (response) {

                        $('#BarrioModal').modal('hide');

                        // Actualizar la fila en la tabla
                        let row = $('#Barrios tbody tr[data-id="' + response.ok.id + '"]');
                        console.log(row);
                        row.find('td:nth-child(1)').text(response.ok.barrio);

                       topAlert('success', response.success);

                },
                error: function (xhr) {
                   alerta('error','Error',xhr.responseJSON.error);

                }
            });

    });

function alerta(tipo,encabezado,mensaje){
    Swal.fire({
        icon: tipo,
        title: encabezado,
        text: mensaje,
    });
}

function topAlert(tipo,mensaje){
    Swal.fire({
        position: "top-end",
        icon: tipo,
        title: mensaje,
        showConfirmButton: false,
        toast: true,
        timer: 4000
    });
}

})


