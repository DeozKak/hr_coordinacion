let tomSelectMunicipio;
document.addEventListener('DOMContentLoaded', () => {

    $('#municipios,#Barrios,#sedes,#grupos,#subgrupos').DataTable({
        paging: false, scrollCollapse: true, scrollY: '230px', lengthChange: false, info: false, "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero", "last": "Ultimo", "next": "Siguiente", "previous": "Anterior"
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

        $.ajax({
            url: 'zonas/store/Municipio', type: 'POST', data: {
                nombre: nombre, sede: sede, zona: zona, _token: token
            }, success: function (response) {
                $('#municipioModal').modal('hide');
                $('#nombreMunicipio').val('');
                $('#sedeMunicipio').val('');
                $('#zonaMunicipio').val('');

                let nuevaFila = `
                            <tr data-id="${response.ok.id}">
                                <td>${response.ok.nombre}</td>
                                <td>${response.ok.sede.nombre}</td>
                                <td>${response.ok.zona.nombre}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirMunicipioModal" data-municipio-id="${response.ok.id}">Editar</button>
                                        <button class="btn btn-danger btn-sm" id="btnChangeStatusMunicipio" data-municipio-id="${response.ok.id}">Desactivar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                $('#municipios tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla
                topAlert('success', response.success);
                setTimeout(() => {
                    alertaMunicipio();
                }, 2000)
            }, error: function (xhr, status, error) {
                console.log(xhr.responseText);
                alerta('error', 'Error', xhr.responseJSON.error)
            }

        })

    })

    // ------------------------- Jquey Editar Municipios --------------------------------

    $(document).on('click', '.abrirMunicipioModal', function () {
        let id = $(this).attr('data-municipio-id');
        $('#idGuardarMunicipio').val(id);
        $('#crearMunicipioModalLabel').text('Editar Municipio');
        $('#crearMunicipio').text('Guardar cambios');
        let token = $('#token').val();
        $('#crearMunicipio').removeClass('guardarNuevoMunicipio').addClass('guardarCambiosMunicipio');
        let modalMunicipio = $('#municipioModal');
        modalMunicipio.modal();

        $.ajax({
            url: 'zonas/' + id + '/editMunicipio', type: 'GET', data: {
                _token: token,
            }, success: function (response) {
                $('#nombreMunicipio').val(response[0].nombre);
                $('#sedeMunicipio').val(response[0].id_sede);
                $('#zonaMunicipio').val(response[0].id_zona);
            }

        })
    })

    $(document).on('click', '.guardarCambiosMunicipio', function () {
        let id = $('#idGuardarMunicipio').val();
        let nombre = $('#nombreMunicipio').val().trim();
        let sede = $('#sedeMunicipio').val();
        let zona = $('#zonaMunicipio').val();
        let token = $('#token').val();

        $.ajax({
            url: 'zonas/' + id + '/updateMunicipio', type: 'PUT', data: {
                nombre: nombre, sede: sede, zona: zona, _token: token,
            }, success: function (response) {

                let modalMunicipio = $('#municipioModal')
                modalMunicipio.modal('hide')
                let row = $('#municipios tbody tr[data-id="' + response.ok.id + '"]')
                row.find('td:nth-child(1)').text(response.ok.nombre)
                row.find('td:nth-child(2)').text(response.ok.sede.nombre)
                row.find('td:nth-child(3)').text(response.ok.zona.nombre)

                topAlert('success', response.success);
            }, error(xhr, status, error) {
                alerta('error', 'Error', xhr.responseJSON.error)
            }
        })

    })


    // ------------------------- Cambiar Estado Municipios -----------------------------

    $(document).on('click', '#btnChangeStatusMunicipio', function () {
        let id = $(this).attr('data-municipio-id')
        let url = $('#cambiarEstadoMunicipio').val()
        let token = $('#token').val()

        $.ajax({
            url: url, type: 'POST', data: {
                id: id, _token: token,
            }, success: function (response) {
                let row = $('#municipios tbody tr[data-id="' + response.success.id + '"]')
                if (response.success.status === 0) {
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                } else {
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }, error(xhr, status, error) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        })
    })


    // ------------------------- Jquery Crear Barrios --------------------------------

    $(document).on('click', '#btnCrearBarrio', function () {
        $('#idGuardarBarrio').val(''); // Limpiar ID
        $('#barrio').val(''); // Limpiar campo de barrio
        $('#municipioBarrio').val(''); // Limpiar select de municipio
        $('#crearBarrioModalLabel').text('Crear Barrio');
        $('#crearBarrio').text('Crear Barrio').removeClass('guardarCambiosBarrio').addClass('guardarNuevoBarrio');
        $('#barrioModal').modal('show'); // Mostrar modal


    });

    $(document).on('click', '.guardarNuevoBarrio', function () {
        let barrio = $('#barrio').val().trim();
        let token = $('#token').val();


        $.ajax({
            url: 'zonas/store/Barrio',
            type: 'POST',
            data: {barrio: barrio, _token: token},
            success: function (response) {
                $('#barrioModal').modal('hide');

                let nuevaFila = `
                    <tr data-id="${response.ok.id}">
                        <td>${response.ok.barrio}</td>
                        <td>${response.ok.municipios[0]?.nombre || "N/A"}</td>
                        <td>
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <button class="btn btn-info btn-sm abrirBarrioModal" data-barrio-id="${response.ok.id}">Editar</button>
                            </div>
                        </td>
                    </tr>
                `;
                $('#Barrios tbody').append(nuevaFila); // Agregar nueva fila
                topAlert('success', response.success);
            },
            error: function (xhr) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        });
    });

    // ------------------------- JQuery Editar Barrios -------------------------

    $(document).on('click', '.abrirBarrioModal', function () {
        let id = $(this).data('barrio-id');
        let token = $('#token').val();

        $('#idGuardarBarrio').val(id);
        $('#crearBarrioModalLabel').text('Editar Barrio');
        $('#crearBarrio').text('Guardar cambios').removeClass('guardarNuevoBarrio').addClass('guardarCambiosBarrio');

        $('#barrioModal').modal('show'); // Mostrar modal

        $.ajax({
            url: `zonas/${id}/editBarrio`, type: 'GET', data: {_token: token}, success: function (response) {
                $('#barrio').val(response[0].barrio);

            }, error: function (xhr) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        });
    });

    // ------------------------- Guardar Cambios en Barrios -------------------------

    $(document).on('click', '.guardarCambiosBarrio', function () {
        let id = $('#idGuardarBarrio').val();
        let barrio = $('#barrio').val().trim();
        let token = $('#token').val();


        $.ajax({
            url: `zonas/${id}/updateBarrio`,
            type: 'PUT',
            data: {barrio: barrio, municipio: municipio, _token: token},
            success: function (response) {
                $('#barrioModal').modal('hide');

                // Actualizar fila en la tabla
                let row = $('#Barrios tbody tr[data-id="' + response.ok.id + '"]');
                row.find('td:nth-child(1)').text(response.ok.barrio);
                row.find('td:nth-child(2)').text(response.ok.municipios[0]?.nombre || "N/A");

                topAlert('success', response.success);
            },
            error: function (xhr) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        });
    });


    // ------------------------- Jquery Crear Grupos --------------------------------

    $(document).on('click', '#btnCrearGrupo', function () {
        $('#idGuardarGrupo').val('');  // Limpiar el campo de ID
        $('#grupo').val('');     // Limpiar el campo de nombre
        $('#sedeGrupo').val('');
        $('#crearGrupoModalLabel').text('Crear Grupo');  // Cambiar el título del modal
        $('#crearGrupo').text('Crear Grupo'); // Cambiar el texto del botón
        $('#crearGrupo').removeClass('guardarCambiosGrupo').addClass('guardarNuevoGrupo');  // Cambiar la clase para detectar el modo
        $('#grupoModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '.guardarNuevoGrupo', function () {
        let grupo = $('#grupo').val().trim();
        let id_sede = $('#sedeGrupo').val();
        let token = $('#token').val();

        $.ajax({
            url: 'zonas/store/Grupo', type: 'POST', data: {
                grupo: grupo, id_sede: id_sede, _token: token
            }, success: function (response) {
                console.log(response)

                $('#grupoModal').modal('hide');
                $('#grupo').val('');
                $('#sedeGrupo').val('');
                console.log(response)

                let nuevaFila = `
                            <tr data-id="${response.ok.id}">
                                <td>${response.ok.grupo}</td>
                                <td>${response.nom_sede}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirGrupoModal" data-grupo-id="${response.ok.id}">Editar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                $('#grupos tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                // Mostrar mensaje de éxito
                topAlert('success', response.success);

            }, error: function (xhr, status, error) {

                alerta('error', 'Error al guardar', xhr.responseJSON.error)

                console.log(xhr.responseText);
            }

        })

    })

    // ------------------------- Jquey Editar Grupos --------------------------------

    $(document).on('click', '.abrirGrupoModal', function () {
        let id = $(this).attr('data-grupo-id');
        $('#idGuardarGrupo').val(id);
        $('#crearGrupoModalLabel').text('Editar Grupo');
        $('#crearGrupo').text('Guardar cambios');
        let token = $('#token').val();
        $('#crearGrupo').removeClass('guardarNuevoGrupo').addClass('guardarCambiosGrupo');
        let modalGrupo = $('#grupoModal');
        modalGrupo.modal();

        $.ajax({
            url: 'zonas/' + id + '/editGrupo', type: 'GET', data: {
                _token: token,
            }, success: function (response) {
                $('#grupo').val(response[0].grupo);
                $('#sedeGrupo').val(response[0].id_sede);
            }, error(xhr, status, error) {
                alerta('error', 'Error', xhr - responseJSON.error);
            }

        })
    })

    // ------------------------- Guardar Cambios en Grupo ------------------------------

    $(document).on('click', '.guardarCambiosGrupo', function () {
        let id = $('#idGuardarGrupo').val();
        let grupo = $('#grupo').val().trim();
        let id_sede = $('#sedeGrupo').val();
        let token = $('#token').val();

        $.ajax({
            url: 'zonas/' + id + '/updateGrupo', type: 'PUT', data: {
                grupo: grupo, id_sede: id_sede, _token: token,
            }, success: function (response) {
                console.log(response)

                let modalGrupo = $('#grupoModal')
                modalGrupo.modal('hide')
                let row = $('#grupos tbody tr[data-id="' + response.ok.id + '"]')
                row.find('td:nth-child(1)').text(response.ok.grupo)
                row.find('td:nth-child(2)').text(response.nom_sede)

                topAlert('success', response.success);

            }, error(xhr, status, error) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        })
    })

    // ------------------------- Cambiar Estado Grupo -----------------------------

    $(document).on('click', '#btnChangeStatusGrupo', function () {
        let id = $(this).attr('data-grupo-id')
        let url = $('#cambiarEstadoMunicipio').val()
        let token = $('#token').val()

        $.ajax({
            url: url, type: 'POST', data: {
                id: id, _token: token,
            }, success: function (response) {
                let row = $('#grupos tbody tr[data-id="' + response.success.id + '"]')
                if (response.success.status === 0) {
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
                } else {
                    row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
                }
            }, error(xhr, status, error) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }
        })
    })


    // ------------------------- Jquery Crear Sub Grupos --------------------------------

    $(document).on('click', '#btnCrearSubGrupo', function () {
        $('#idGuardarSubGrupo').val('');  // Limpiar el campo de ID
        $('#subgrupo').val('');     // Limpiar el campo de nombre
        $('#sedeSubGrupo').val('');
        $('#crearSubGrupoModalLabel').text('Crear Sub Grupo');  // Cambiar el título del modal
        $('#crearSubGrupo').text('Crear Sub Grupo'); // Cambiar el texto del botón
        $('#crearSubGrupo').removeClass('guardarCambiosSubGrupo').addClass('guardarNuevoSubGrupo');  // Cambiar la clase para detectar el modo
        $('#subGrupoModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '.guardarNuevoSubGrupo', function () {
        let subgrupo = $('#subgrupo').val().trim();
        let id_sede = $('#sedeSubGrupo').val();
        let token = $('#token').val();

        $.ajax({
            url: 'zonas/store/SubGrupo', type: 'POST', data: {
                subgrupo: subgrupo, id_sede: id_sede, _token: token
            }, success: function (response) {
                console.log(response)

                $('#subGrupoModal').modal('hide');
                $('#subgrupo').val('');
                $('#sedeSubGrupo').val('');
                console.log(response)

                let nuevaFila = `
                            <tr data-id="${response.ok.id}">
                                <td>${response.ok.subgrupo}</td>
                                <td>${response.nom_sede}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirSubGrupoModal" data-subgrupo-id="${response.ok.id}">Editar</button>
                                    </div>
                                </td>
                            </tr>
                        `;
                $('#subgrupos tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                topAlert('success', response.success);

            }, error: function (xhr, status, error) {

                alerta('error', 'Error', xhr.responseJSON.error);

                console.log(xhr.responseText);
            }

        })

    })

    // ------------------------- Jquey Editar Sub Grupos --------------------------------

    $(document).on('click', '.abrirSubGrupoModal', function () {
        let id = $(this).attr('data-subgrupo-id');
        $('#idGuardarSubGrupo').val(id);
        $('#crearSubGrupoModalLabel').text('Editar Sub Grupo');
        $('#crearSubGrupo').text('Guardar cambios');
        let token = $('#token').val();
        $('#crearSubGrupo').removeClass('guardarNuevoSubGrupo').addClass('guardarCambiosSubGrupo');
        let modalSubGrupo = $('#subGrupoModal');
        modalSubGrupo.modal();

        $.ajax({
            url: 'zonas/' + id + '/editSubGrupo', type: 'GET', data: {
                _token: token,
            }, success: function (response) {
                $('#subgrupo').val(response[0].subgrupo);
                $('#sedeSubGrupo').val(response[0].id_sede);
            }, error(xhr, status) {
                alerta('error', 'Error', xhr.responseJSON.error);
            }

        })
    })

    // ------------------------- Guardar Cambios en Sub Grupo ------------------------------

    $(document).on('click', '.guardarCambiosSubGrupo', function () {
        let id = $('#idGuardarSubGrupo').val();
        let subgrupo = $('#subgrupo').val().trim();
        let id_sede = $('#sedeSubGrupo').val();
        let token = $('#token').val();

        $.ajax({
            url: 'zonas/' + id + '/updateSubGrupo', type: 'PUT', data: {
                subgrupo: subgrupo, id_sede: id_sede, _token: token,
            }, success: function (response) {
                console.log(response)

                let modalSubGrupo = $('#subGrupoModal')
                modalSubGrupo.modal('hide')
                let row = $('#subgrupos tbody tr[data-id="' + response.ok.id + '"]')
                row.find('td:nth-child(1)').text(response.ok.subgrupo)
                row.find('td:nth-child(2)').text(response.nom_sede)

                topAlert('success', response.success);
            }, error(xhr, status) {

                alerta('error', 'Error', xhr.responseJSON.error);

            }
        })
    })
});

// ------------------------- Cambiar Estado SubGrupo -----------------------------

$(document).on('click', '#btnChangeStatusSubgrupo', function () {
    let id = $(this).attr('data-subgrupo-id')
    let url = $('#cambiarEstadoMunicipio').val()
    let token = $('#token').val()

    $.ajax({
        url: url, type: 'POST', data: {
            id: id, _token: token,
        }, success: function (response) {
            let row = $('#subgrupos tbody tr[data-id="' + response.success.id + '"]')
            if (response.success.status === 0) {
                row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-danger btn-sm').addClass('btn btn-success btn-sm').text('Activar')
            } else {
                row.find('td:nth-child(4) #btnChangeStatusMunicipio').removeClass('btn btn-success btn-sm').addClass('btn btn-danger btn-sm').text('Desactivar')
            }
        }, error(xhr, status, error) {
            alerta('error', 'Error', xhr.responseJSON.error);
        }
    })
})


function alerta(tipo, encabezado, mensaje) {
    Swal.fire({
        icon: tipo, title: encabezado, text: mensaje,
    });
}

function topAlert(tipo, mensaje) {
    Swal.fire({
        position: "top-end", icon: tipo, title: mensaje, showConfirmButton: false, toast: true, timer: 4000
    });
}


