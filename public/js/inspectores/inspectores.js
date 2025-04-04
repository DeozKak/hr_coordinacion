$(document).ready(function () {
    $('#table_users,#table_desactivar').DataTable({
        responsive: true,
        autoWidth: false,
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

    // ------------------------- Jquery Editar Inspector --------------------------------

    $(document).on('click', '.modalEditarInspector', function () {
        let modalEditarInspector = $('#editInspectorModal')
        modalEditarInspector.modal()

        let fila = $(this).closest('tr')
        let id = fila.find('td').eq(0).attr('data-id')
        let url = $('#urlGetData').val()
        let token = $('#tokenGetData').val()

        $.ajax({
            url: url,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success: function (response) {
                $('#idInspectorEditar').val(response.inspector.id)
                $('#idEditar').val(response.inspector.id)
                $('#nombresEditar').val(response.inspector.nombres)
                $('#apellidosEditar').val(response.inspector.apellidos)
                $('#typeIdEditar').val(response.inspector.type_id)
                $('#cedulaEditar').val(response.inspector.cedula)
                $('#supervisorEditar').val(response.inspector.supervisor)
                $('#aprendizEditar').val(response.inspector.aprendiz)
            }
        })
    })

    // ------------------------- Guardar Cambios de Editar Inspector -------------------------

    $(document).on("click", "#guardarEditarInspector", function () {
        let id = $('#idInspectorEditar').val()
        let nombreGuardar = $("#nombresEditar").val()
        let apellidoGuardar = $("#apellidosEditar").val()
        let supervisorGuardar = $("#supervisorEditar").val()
        let supervisorNombre = $("#supervisorEditar option:selected").text();
        let aprendizGuardar = $("#aprendizEditar").val();
        let urlEditar = $(this).attr("data-url")
        let token = $('#tokenGetData').val()

        $.ajax({
            url: urlEditar,
            type: "POST",
            dataType: 'json',
            data: {
                _token: token,
                id: id,
                nombres: nombreGuardar,
                apellidos: apellidoGuardar,
                supervisor: supervisorGuardar,
                aprendiz: aprendizGuardar
            },
            success: function (response) {
                let row = $('#table_users tbody tr[data-id="' + response.inspector.id + '"]');
                if (aprendizGuardar == 1) {
                    row.find('td:nth-child(7)').html('<span class="badge bg-warning text-dark">SI</span>');
                } else {
                    row.find('td:nth-child(7)').html('<span class="badge bg-secondary">NO</span>');
                }
                row.find('td:nth-child(1)').text(response.inspector.id);
                row.find('td:nth-child(2)').text(nombreGuardar);
                row.find('td:nth-child(3)').text(apellidoGuardar);
                row.find('td:nth-child(6)').text(supervisorNombre);

                Swal.fire({
                    icon: 'success',
                    title: 'Exito',
                    text: response.success
                }).then(() => {
                    // Ocultar el modal después de la alerta
                    $('#editInspectorModal').modal('hide');
                })
            }, error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        })
    })

    // ------------------------- Jquery Crear Inspector --------------------------------

    $(document).on('click', '.modalCrearInspector', function () {
        let modalCrearInspector = $('#createInspectorModal')
        modalCrearInspector.modal()
    })

    // ------------------------- Guardar Cambios de Crear Inspector -------------------------

    $(document).on("click", "#guardarCrearInspector", function () {
        let idGuardar = $("#idCrear").val();
        let nombreGuardar = $("#nombresCrear").val()
        let apellidoGuardar = $("#apellidosCrear").val()
        let typeIdGuardar = $("#typeIdCrear").val()
        let cedulaGuardar = $("#cedulaCrear").val()
        let supervisorGuardar = $("#supervisorCrear").val()
        let urlCrear = $(this).attr("data-url")
        let token = $('#tokenGetData').val()

        $.ajax({
            url: urlCrear,
            type: "POST",
            dataType: 'json',
            data: {
                _token: token,
                idCrear: idGuardar,
                nombres: nombreGuardar,
                apellidos: apellidoGuardar,
                supervisor: supervisorGuardar,
                cedula: cedulaGuardar,
                type_id: typeIdGuardar
            },
            success: function (response) {

                $("#idCrear").val("");
                $("#nombresCrear").val("")
                $("#apellidosCrear").val("")
                $("#cedulaCrear").val("")

                let inspector = response.inspector
                let inspectorRow =
                    "<tr data-id='" + inspector.id + "'>" +
                    "<td data-id='" + inspector.id + "'>" + inspector.id + "</td>" +
                    "<td>" + inspector.nombres + "</td>" +
                    "<td>" + inspector.apellidos + "</td>" +
                    "<td>" + inspector.type_id + "</td>" +
                    "<td>" + inspector.cedula + "</td>" +
                    "<td>" + inspector.supervisor.name + "</td>" +
                    "<td>" + "<span class='badge bg-warning text-dark'>SI</span>" + "</td>" +
                    "<td>" +
                    (inspector.state == 1 ?
                        "<span class='badge badge-success'>Activo</span>" :
                        "<span class='badge badge-danger'>Inactivo</span>"
                    ) +
                    "</td>" +
                    "<td>" +
                    "<div class='btn-group' role='group' aria-label='Botones'>" +
                    "<button class='btn btn-warning modalEditarInspector'>Editar</button>" +
                    "<form class='d-inline'>" +
                    "<button type='button' data-url='" + activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-danger change_state'>Desactivar</button>" +
                    "</form>" +
                    "</div>" +
                    "</td>" +
                    "</tr>";

                $('#table_users').DataTable().row.add($(inspectorRow)).draw(false);

                Swal.fire({
                    icon: 'success',
                    title: 'Exito',
                    text: response.success
                }).then(() => {
                    // Ocultar el modal después de la alerta
                    $('#createInspectorModal').modal('hide');
                })

            }, error: function (xhr) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: xhr.responseJSON.error
                });
            }
        })

    })


    // ------------------------- Jquery Modal Inspectores Desactivados --------------------------------

    $(document).on('click', '.modalDesactivados', function () {
        let modalDesactivados = $('#showDisabledInspectorsModal');
        modalDesactivados.modal();
        let url = $(this).attr('data-url');

        $.ajax({
            url: url,
            type: 'GET',
            success: function (response) {
                let table = $("#table_desactivar").DataTable();
                table.clear().draw();

                response.inspectores.forEach(inspector => {
                    let row =
                        "<tr data-id='" + inspector.id + "'>" +
                        "<td data-id='" + inspector.id + "'>" + inspector.id + "</td>" +
                        "<td>" + inspector.nombres + "</td>" +
                        "<td>" + inspector.apellidos + "</td>" +
                        "<td>" + inspector.type_id + "</td>" +
                        "<td>" + inspector.cedula + "</td>" +
                        "<td>" + inspector.supervisor.name + "</td>" +
                        "<td><span class='badge badge-danger'>Inactivo</span></td>" +
                        "<td>" +
                        "<div class='btn-group' role='group' aria-label='Botones'>" +
                        "<form class='d-inline'>" +
                        "<button type='button' data-url='" + activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-success btn-sm active_state'>Activar</button>" +
                        "</form>" +
                        "</div>" +
                        "</td>" +
                        "</tr>";
                    table.row.add($(row)).draw();
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

    // ------------------------- Jquery Cambiar Estado Inspector --------------------------------

    $(document).on('click', '.change_state', function (e) {
        e.preventDefault();
        let url = $(this).attr('data-url');
        let token = $('#tokenGetData').val();
        let idTable = $(this).closest('tr').data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esto cambiará el estado del inspector.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    data: { _token: token },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: response.success
                        }).then(() => {
                            let table = $('#table_users').DataTable();
                            let row = table.row($(`tr[data-id="${idTable}"]`));
                            row.remove().draw(false);
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
            }
        });
    });

    // ------------------------- Jquery Activar Inspector --------------------------------

    $(document).on('click', '.active_state', function (e) {
        e.preventDefault();
        let token = $('#tokenGetData').val();
        let url = $(this).attr('data-url');
        let idTable = $(this).closest('tr').data('id');

        Swal.fire({
            title: '¿Estás seguro?',
            text: 'Esto activará al inspector nuevamente.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, activar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: url,
                    type: 'POST',
                    dataType: 'json',
                    data: { _token: token },
                    success: function (response) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Éxito',
                            text: response.success
                        }).then(() => {
                            let inspector = response.inspector;
                            let aprendiz = inspector.aprendiz == 1 ? "<span class='badge bg-warning text-dark'>SI</span>" : "<span class='badge bg-secondary'>NO</span>";

                            let table = $('#table_desactivar').DataTable();
                            table.row($(`tr[data-id="${idTable}"]`)).remove().draw();

                            let newRow =
                                "<tr data-id='" + inspector.id + "'>" +
                                "<td data-id='" + inspector.id + "'>" + inspector.id + "</td>" +
                                "<td>" + inspector.nombres + "</td>" +
                                "<td>" + inspector.apellidos + "</td>" +
                                "<td>" + inspector.type_id + "</td>" +
                                "<td>" + inspector.cedula + "</td>" +
                                "<td>" + inspector.supervisor.name + "</td>" +
                                "<td>" + aprendiz + "</td>" +
                                "<td><span class='badge badge-success'>Activo</span></td>" +
                                "<td>" +
                                "<div class='btn-group' role='group' aria-label='Botones'>" +
                                "<button class='btn btn-warning modalEditarInspector'>Editar</button>" +
                                "<form class='d-inline'>" +
                                "<button type='button' data-url='" + activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-danger change_state'>Desactivar</button>" +
                                "</form>" +
                                "</div>" +
                                "</td>" +
                                "</tr>";
                            $("#table_users").DataTable().row.add($(newRow)).draw(false);
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
            }
        });
    });

    $(document).on('input', '#cedulaCrear', function () {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
