@extends('adminlte::page')

@section('title', 'Gestión de Notificaciones')

@section('content_header')
    <h1>Gestión de Notificaciones</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/admin/index.css')}}">
    <div class="card">
        <div class="card-body">
            <button class="btn btn-success mb-2 modalCrearNotificacion" data-toggle="modal"
                data-target="#createNotificacionModal">Nueva notificación</button>

            <div class="table-responsive">
                <table id="table_users" class="table table-sm">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Roles</th>
                            <th>Notificaciones</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($users as $user)
                            <tr data-user-id="{{ $user->id }}">
                                <td>{{ $user->name }}</td>
                                <td>{{ $user->email }}</td>
                                <td>
                                    @foreach ($user->roles as $role)
                                        <span class="badge badge-primary">{{$role->name}}</span>
                                    @endforeach
                                </td>
                                <td class="user-notifications">
                                    @foreach($user->notifications as $notification)
                                        <span class="badge badge-primary">{{ $notification->Nombre }}</span>
                                    @endforeach
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Botones">
                                        <button class="btn btn-warning edit-btn" data-toggle="modal"
                                            data-target="#editNotificacionModal" data-token="{{csrf_token()}}"
                                            data-url="{{route('profile.getDataPermissions')}}"
                                            data-user="{{ $user }}">Editar</button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <input type="hidden" class="tokenUser" value="{{csrf_token()}}">
            </div>
        </div>
    </div>

    <!-- Modal de Edición de Notificaciones -->
    <div class="modal fade" id="editNotificacionModal" tabindex="-1" aria-labelledby="editNotificacionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form method="POST" id="editNotificacionForm" autocomplete="off">
                    <input type="hidden" id="urlEnviar" value="">
                    @csrf
                    @method('PUT')

                    <!-- Encabezado del Modal -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="editNotificacionModalLabel">Editar Notificaciones</h5>
                        <input type="hidden" id="idNotificacionEditar">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Cuerpo del Modal -->
                    <div class="modal-body">
                        <div class="form-group">
                            <!-- Títulos alineados -->
                            <div class="row text-center mb-2">
                                <div class="col-md-5">
                                    <label for="revokedNotification">Notificaciones Disponibles</label>
                                </div>
                                <div class="col-md-2"></div>
                                <div class="col-md-5">
                                    <label for="assignedNotification">Notificaciones Asignadas</label>
                                </div>
                            </div>

                            <!-- Contenedor principal -->
                            <div class="row">
                                <!-- Notificaciones Disponibles -->
                                <div class="col-md-5">
                                    <select id="revokedNotification" class="form-control" multiple size="6">
                                    </select>
                                </div>

                                <!-- Botones de acción -->
                                <div class="col-md-2 d-flex flex-column align-items-center justify-content-center">
                                    <button type="button" class="btn btn-primary w-100 mb-2"
                                        id="assignNotification">Asignar</button>
                                    <button type="button" class="btn btn-danger w-100"
                                        id="removeNotification">Quitar</button>
                                </div>

                                <!-- Notificaciones Asignadas -->
                                <div class="col-md-5">
                                    <select id="assignedNotification" class="form-control" multiple size="6">
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Pie del Modal -->
                    <div class="modal-footer justify-content-center">
                        <button type="button" id="guardarEditarNotificacion"
                            data-url="{{ route('admin.notifications.update') }}" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal de Creación de Notificación -->
    <div class="modal fade" id="createNotificacionModal" tabindex="-1" aria-labelledby="createNotificacionModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="createNotificacionForm">
                    @csrf

                    <!-- Encabezado del Modal -->
                    <div class="modal-header">
                        <h5 class="modal-title" id="createNotificacionModalLabel">Crear Notificación</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Cuerpo del Modal -->
                    <div class="modal-body">
                        <div class="form-group">
                            <label for="nombreNotificacion">Nombre</label>
                            <input type="text" class="form-control" id="nombreNotificacionCrear" name="nombre" required>
                        </div>
                    </div>

                    <!-- Pie del Modal -->
                    <div class="modal-footer">
                        <button type="button" id="guardarCrearNotificacion"
                            data-url="{{ route('admin.notifications.store') }}" class="btn btn-primary">Guardar</button>
                        <button type="button" class="btn btn-danger" data-dismiss="modal">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @section('js')
        <script>
            $(document).ready(function () {
                $('#table_users').DataTable({
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
                $(document).on('click', '.edit-btn', function () {
                    const user = $(this).data('user');
                    $('#idNotificacionEditar').val(user.id);
                    let url = "{{ route('admin.notifications.getUserNotifications') }}";
                    let token = $(this).attr('data-token');

                    $.post({
                        url: url,
                        data: {
                            _token: token,
                            id: user.id,
                        },
                        success: function (response) {
                            let asignadas = response.asignadas;
                            let disponibles = response.disponibles;

                            $('#revokedNotification').empty();
                            $('#assignedNotification').empty();

                            disponibles.forEach(element => {
                                $('#revokedNotification').append('<option value="' + element.Nombre + '">' + element.Nombre + '</option>');
                            });

                            asignadas.forEach(element => {
                                $('#assignedNotification').append('<option value="' + element.Nombre + '">' + element.Nombre + '</option>');
                            });
                        }
                    });
                });

                $(document).on("click", "#guardarEditarNotificacion", function () {
                    let id = $('#idNotificacionEditar').val();
                    let urlEditar = $(this).attr("data-url");
                    let token = $('meta[name="csrf-token"]').attr('content');

                    let assignedNotifications = [];
                    let revokedNotifications = [];

                    $('#assignedNotification option').each(function () {
                        assignedNotifications.push($(this).val());
                    });

                    $('#revokedNotification option').each(function () {
                        revokedNotifications.push($(this).val());
                    });

                    $.ajax({
                        url: urlEditar,
                        type: "POST",
                        dataType: 'json',
                        data: {
                            _token: token,
                            id: id,
                            assignedNotifications: assignedNotifications,
                            revokedNotifications: revokedNotifications,
                        },
                        success: function (response) {
                            if (response.status === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Actualización exitosa',
                                    text: response.message,
                                    didClose: () => {
                                        // Actualizar la fila de la tabla sin recargar la página
                                        let userRow = $("tr[data-user-id='" + id + "']");

                                        // Actualizar la columna de notificaciones
                                        let notificacionesHTML = assignedNotifications.map(notif =>
                                            `<span class="badge badge-primary">${notif}</span>`
                                        ).join(" ");

                                        userRow.find("td:nth-child(4)").html(notificacionesHTML);

                                        // Cerrar modal
                                        $('#editNotificacionModal').modal('hide');
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function () {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Hubo un problema al actualizar las notificaciones',
                            });
                        }
                    });
                });

                // Evento para guardar una nueva notificación
                $(document).on("click", "#guardarCrearNotificacion", function () {
                    let nombreNotificacion = $("#nombreNotificacionCrear").val().trim();
                    let urlCrear = $(this).attr("data-url");
                    let token = $('meta[name="csrf-token"]').attr('content');

                    if (nombreNotificacion === "") {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Campo vacío',
                            text: 'Debe ingresar un nombre para la notificación.',
                        });
                        return;
                    }

                    $.ajax({
                        url: urlCrear,
                        type: "POST",
                        dataType: "json",
                        data: {
                            _token: token,
                            nombre: nombreNotificacion,
                        },
                        success: function (response) {
                            console.log(response);
                            if (response.status === "success") {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Notificación creada',
                                    text: response.message,
                                    didClose: () => {
                                        // Cerrar el modal
                                        $('#createNotificacionModal').modal('hide');

                                        // Limpiar el campo de texto
                                        $("#nombreNotificacionCrear").val("");

                                        // Agregar la nueva notificación a la tabla sin recargar la página
                                        let userId = response.user_id;
                                        let nuevaNotificacionHTML = `<span class="badge badge-primary">${nombreNotificacion}</span>`;
                                        let userRow = $("tr[data-user-id='" + userId + "']");

                                        userRow.find(".user-notifications").append(nuevaNotificacionHTML);
                                    }
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: response.message,
                                });
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Hubo un problema al crear la notificación.',
                            });
                        }
                    });
                });

                $('#assignNotification').click(function () {
                    $('#revokedNotification option:selected').each(function () {
                        $(this).remove().appendTo('#assignedNotification');
                    });
                });

                $('#removeNotification').click(function () {
                    $('#assignedNotification option:selected').each(function () {
                        $(this).remove().appendTo('#revokedNotification');
                    });
                });
            });

        </script>
    @endsection
@endsection
