document.addEventListener('DOMContentLoaded', () => {
    $('#cortes, #causal').DataTable({
        paging: false,
        scrollCollapse: true,
        scrollY: '230px',
        lengthChange: false,
        order: [[1, 'desc']],
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

    $('#cortes').on('click', '#btn_detallesCorte', function () {

        const corteId = $(this).data('corte-id');
        detallesCorte(corteId);

    });

    $('#cortes').on('click', '#btn_fallidas', function () {

        const corteId = $(this).data('corte-id');
        fallidas(corteId);

    });

    $('#cortes').on('click', '#btn_graficos', function () {
        const corteId = $(this).data('corte-id');
        Graficos(corteId);

    });

    // ------------------------- JQuery Crear Cortes --------------------------------

    $(document).on('click', '#btnCrearCorte', function () {
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
    $(document).on('click', '#btn_cerrarCorte', function () {
        $('#corteModal').modal('hide');
    })

    $(document).on('click', '.guardarNuevoCorte', function () {
        let nombre = $('#nombreCorte').val().trim();
        let fecha_inicio = $('#fecha_inicio').val();
        let fecha_fin = $('#fecha_fin').val();
        let meta = $('#meta').val();
        let dobles = $('#dobles').val();
        let token = $('#token').val();


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
            success: function (response) {

                $('#corteModal').modal('hide');
                $('#nombreCorte').val('');
                $('#fecha_inicio').val('');
                $('#fecha_fin').val('');
                $('#meta').val('');
                $('#dobles').val('');


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

            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error al guardar',
                    text: xhr.responseJSON.error,
                });
                console.log(xhr.responseText);
            }

        })

    })


    // ------------------------- JQuery Editar Cortes --------------------------------

    $(document).on('click', '.abrirCorteModal', function () {
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
            success: function (response) {
                $('#nombreCorte').val(response[0].nombre);
                $('#fecha_inicio').val(response[0].fecha_inicio);
                $('#fecha_fin').val(response[0].fecha_fin);
                $('#meta').val(response[0].meta);
                $('#dobles').val(response[0].dobles);
            }

        })
    })

    // ------------------------- Guardar Cambios en Cortes ------------------------------

    $(document).on('click', '.guardarCambiosCorte', function () {
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
        } else {
            $.ajax({
                url: 'cortes_produccion/' + id + '/updateCorte',
                type: 'PUT',
                data: {
                    nombre: nombre,
                    fecha_inicio: fecha_inicio,
                    fecha_fin: fecha_fin,
                    meta: meta,
                    dobles: dobles,
                    _token: token,
                },
                success: function (response) {
                    console.log(response)
                    if (response.status == 'error') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: response.message
                        });
                    } else if (response.status == 'fechaMayor') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'La fecha de incio es mayor',
                            text: response.message,
                        });
                    } else if (response.status == 'fechas_iguales') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Error',
                            text: response.message
                        });
                    } else {
                        let modalMunicipio = $('#corteModal')
                        modalMunicipio.modal('hide')
                        let row = $('#cortes tbody tr[data-id="' + response.success.id + '"]')
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


    // ------------------------- JQuery Crear Causal --------------------------------

    // Abrir el modal en modo creación
    $(document).on('click', '#btnCrearCausal', function () {
        $('#idGuardarCausal').val('');  // Limpiar el campo de ID
        $('#nombreCausal').val('');     // Limpiar el campo de nombre
        $('#crearCausalModalLabel').text('Crear Causal');  // Cambiar el título del modal
        $('#crearCausal').text('Crear Causal'); // Cambiar el texto del botón
        $('#crearCausal').removeClass('guardarCambiosCausal').addClass('guardarNuevoCausal');  // Cambiar la clase para detectar el modo
        $('#causalModal').modal('show');  // Mostrar el modal
    });

    $(document).on('click', '#btn_cerrarCausal', function () {
        $('#causalModal').modal('hide');
    });

    // Enviar el formulario para crear una nueva causal
    $(document).on('click', '.guardarNuevoCausal', function () {
        let nom_causal = $('#nombreCausal').val().trim();
        let token = $('#token').val();

        $.ajax({
            url: 'cortes_produccion/store/Causal',  // URL de la ruta para almacenar la causal
            type: 'POST',
            data: {
                nom_causal: nom_causal,
                _token: token,
            },
            success: function (response) {
                console.log(response)
                // Ocultar el modal y limpiar campos
                $('#causalModal').modal('hide');
                $('#nombreCausal').val('');

                // Agregar la nueva sede a la tabla sin recargar la página
                let nuevaFila = `
                    <tr data-id="${response.causal.id}">
                        <td>${response.causal.nom_causal}</td>
                        <td>
                            <div style="display: flex; gap: 5px; justify-content: center;">
                                <button class="btn btn-info btn-sm abrirCausalModal" data-causal-id="${response.causal.id}">Editar</button>
                                <button class="btn btn-danger btn-sm" id="btnChangeStatusCausal" data-causal-id="${response.causal.id}">Desactivar</button>
                            </div>
                        </td>
                    </tr>
                `;
                $('#causal tbody').append(nuevaFila);  // Agregar la nueva fila al cuerpo de la tabla

                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.success
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        });
    });

    // ------------------------- JQuery Editar Causal --------------------------------

    $(document).on('click', '.abrirCausalModal', function () {
        let id = $(this).attr('data-causal-id')
        $('#idGuardarCausal').val(id)
        $('#crearCausalModalLabel').text('Editar causal')
        $('#crearCausal').text('Guardar cambios')
        $('#crearCausal').removeClass('guardarNuevoCausal').addClass('guardarCambiosCausal')
        $('#causalModal').modal('show');

        $.ajax({
            url: 'cortes_producction/' + id + '/editCausal',
            type: 'GET',
            success: function (response) {
                $('#nombreCausal').val(response[0].nom_causal)
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        })
    })

    // ------------------------- Guardar Cambios en Causal ------------------------------

    $(document).on('click', '.guardarCambiosCausal', function () {
        let nombre = $('#nombreCausal').val().trim()
        let id = $('#idGuardarCausal').val()
        let token = $('#token').val()

        $.ajax({
            url: 'cortes_produccion/' + id + '/updateCausal',
            type: 'PUT',
            data: {
                nombre: nombre,
                _token: token,
            },
            success: function (response) {
                $('#causalModal').modal('hide')
                console.log(response)

                let row = $('#causal tbody tr[data-id="' + response.causal.id + '"]')
                row.find('td:nth-child(1)').text(response.causal.nom_causal)

                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.success
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        })
    })


    // ------------------------- Jquery Cambiar Estado Causal --------------------------------

    $(document).on('click', '#btnChangeStatusCausal', function () {
        let id = $(this).attr('data-causal-id');
        let url = $('#cambiarEstadoCausal').val();
        let token = $('#token').val();

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                id: id,
                _token: token,
            },
            success: function (response) {
                let row = $('#causal tbody tr[data-id="' + response.causal.id + '"]');

                if (response.causal.status == 0) {
                    row.find('td:nth-child(2) #btnChangeStatusCausal')
                        .removeClass('btn-danger')
                        .addClass('btn-success')
                        .text('Activar');
                } else {
                    row.find('td:nth-child(2) #btnChangeStatusCausal')
                        .removeClass('btn-success')
                        .addClass('btn-danger')
                        .text('Desactivar');
                }

                // Mostrar mensaje de éxito
                Swal.fire({
                    icon: 'success',
                    title: 'Éxito',
                    text: response.success
                });
            },
            error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        });
    });

    $(document).on('input', '.inputNumericoMeta', function () {
        this.value = this.value.replace(/[^0-9]/g, ''); // Permitir solo números
        if (this.value.length > 3) {
            this.value = this.value.slice(0, 3); // Limitar a 3 dígitos
        }
    })

    $(document).on('input', '.inputNumericoDobles', function () {
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

function fallidas(id) {
    window.location.href = `produccion/detalles/${id}`;
}

function Graficos(id) {
    window.location.href = `produccion?id=${id}`;
}
