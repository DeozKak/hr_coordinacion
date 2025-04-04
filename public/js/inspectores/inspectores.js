$(document).ready(function() {
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

    $(document).on('click', '.modalEditarInspector', function(){
        let modalEditarInspector = $('#editInspectorModal')
        modalEditarInspector.modal()

        let fila = $(this).closest('tr')
        let id = fila.find('td').eq(0).attr('data-id')
        let url = $('#urlGetData').val()
        let token = $('#tokenGetData').val()

        $.ajax({
            url:url,
            type: 'POST',
            data: {
                _token: token,
                id: id,
            },
            success:function(response){
                console.log(response);
                if(response != ""){
                    $('#idInspectorEditar').val(response.inspector.id)
                    $('#nombresEditar').val(response.inspector.nombres)
                    $('#apellidosEditar').val(response.inspector.apellidos)
                    $('#typeIdEditar').val(response.inspector.type_id)
                    $('#cedulaEditar').val(response.inspector.cedula)
                    $('#supervisorEditar').val(response.inspector.supervisor)
                    $('#aprendizEditar').val(response.inspector.aprendiz)
                }
            }
        })
    })
    $(document).on("click","#guardarEditarInspector",function(){
        let id = $('#idInspectorEditar').val()
        let nombreGuardar=$("#nombresEditar").val()
        let apellidoGuardar=$("#apellidosEditar").val()
        let supervisorGuardar=$("#supervisorEditar").val()
        let supervisorNombre = $("#supervisorEditar option:selected").text();
        let aprendizGuardar = $("#aprendizEditar").val();
        let urlEditar=$(this).attr("data-url")
        let token = $('#tokenGetData').val()

        if (nombreGuardar == "" || apellidoGuardar == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Los nombres y apellidos son obligatorios',
            })
        }else{
            $.ajax({
                url:urlEditar,
                type:"POST",
                dataType: 'json',
                data:{
                    _token: token,
                    id:id,
                    nombres:nombreGuardar,
                    apellidos:apellidoGuardar,
                    supervisor:supervisorGuardar,
                    aprendiz:aprendizGuardar
                },
                success:function(response){
                    if (response.status === 'success') {
                        let row = $('#table_users tbody tr[data-id="' + response.inspector.id + '"]');
                        if(aprendizGuardar == 1){
                            row.find('td:nth-child(6)').html('<span class="badge bg-warning text-dark">SI</span>');
                        }else{
                            row.find('td:nth-child(6)').html('<span class="badge bg-secondary">NO</span>');
                        }
                        row.find('td:nth-child(1)').text(nombreGuardar);
                        row.find('td:nth-child(2)').text(apellidoGuardar);
                        row.find('td:nth-child(5)').text(supervisorNombre);
                        $('#editInspectorModal').modal('hide');

                        Swal.fire({
                            icon: 'success',
                            title: 'Actualización exitosa',
                            text: response.message,
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message,
                        });
                    }
                }, error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al actualizar el inspector.',
                    });
                }
            })
        }
    })
    $(document).on('click', '.modalCrearInspector', function(){
        let modalCrearInspector = $('#createInspectorModal')
        modalCrearInspector.modal()
    })
    $(document).on("click","#guardarCrearInspector",function(){
        let nombreGuardar=$("#nombresCrear").val()
        let apellidoGuardar=$("#apellidosCrear").val()
        let typeIdGuardar=$("#typeIdCrear").val()
        let cedulaGuardar=$("#cedulaCrear").val()
        let supervisorGuardar=$("#supervisorCrear").val()
        let urlCrear=$(this).attr("data-url")
        let token = $('#tokenGetData').val()

        if (nombreGuardar == "" || apellidoGuardar == "" || typeIdGuardar == "" || cedulaGuardar == "" || supervisorGuardar == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'Todos los campos son obligatorios',
            })
        }else{
            $.ajax({
                url:urlCrear,
                type:"POST",
                dataType: 'json',
                data:{
                    _token: token,
                    nombres:nombreGuardar,
                    apellidos:apellidoGuardar,
                    supervisor:supervisorGuardar,
                    cedula:cedulaGuardar,
                    type_id:typeIdGuardar
                },
                success:function(response){
                    if(response.status == "success"){
                        Swal.fire({
                            icon: response.status,
                            title: 'Exito',
                            text: response.message
                        })

                        $("#nombresCrear").val("")
                        $("#apellidosCrear").val("")
                        $("#cedulaCrear").val("")

                        let inspector = response.inspector
                        let inspectorRow =
                                    "<tr data-id='" + inspector.id + "'>" +
                                        "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
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
                                        "<button class='btn btn-warning modalEditarInspector'>Editar</button>"+
                                        "<form class='d-inline'>" +
                                            "<button type='button' data-url='"+ activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-danger change_state'>Desactivar</button>" +
                                        "</form>" +
                                    "</div>" +
                                        "</td>" +
                                    "</tr>";

                                    $('#table_users').DataTable().row.add($(inspectorRow)).draw(false);
                    }else if(response.status == "errorRegistro"){
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message
                        })
                    }else if(response.status == "warning"){
                        Swal.fire({
                            icon: response.status,
                            title: 'Advertencia',
                            text: response.message
                        })
                    }else if(response.status == "error"){
                        Swal.fire({
                            icon: response.status,
                            title: 'Advertencia',
                            text: response.message
                        })
                    }
                }
            })
        }
    })


    $(document).on('click', '.modalDesactivados',function() {
        let modalDesactivados = $('#showDisabledInspectorsModal')
        modalDesactivados.modal()
        let url = $(this).attr('data-url')

        $.ajax({
            url:url,
            type:'GET',
            success:function(response){
                let inspectorArray = response.inspector
                if(inspectorArray.length > 0){
                    $("#table_desactivar").DataTable().clear().draw();
                   
                    inspectorArray.forEach(inspector => {
                        let inspectorRow = "<tr data-id='" + inspector.id + "'>" +
                                                "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
                                                "<td>" + inspector.apellidos + "</td>" +
                                                "<td>" + inspector.type_id + "</td>" +
                                                "<td>" + inspector.cedula + "</td>" +
                                                "<td>" + inspector.supervisor.name + "</td>" +
                                                "<td><span class='badge badge-danger'>Inactivo</span></td>" +
                                                "<td>" +
                                                    "<div class='btn-group' role='group' aria-label='Botones'>" +
                                                        "<form class='d-inline'>" +
                                                            "<button type='button' data-url='"+ activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-success btn-sm active_state'>Activar</button>" +
                                                        "</form>" +
                                                    "</div>" +
                                                "</td>" +
                                            "</tr>";
                        $("#table_desactivar").DataTable().row.add($(inspectorRow)).draw();
                    });
                }
            }
        })
    })
    $(document).on('click', '.change_state', function(e) {
        e.preventDefault(); // Prevenir el envío del formulario por defecto
        let url = $(this).attr('data-url')
        let tokenDesactivar = $('#tokenGetData').val()
        let idTable = $(this).closest('tr').eq(0).attr('data-id')
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Quieres cambiar el estado del inspector? Una vez desactivado, el inspector no estará disponible en Bitácoras y no podrá recibir asignaciones de órdenes.',
            icon: 'warning', // Cambiado de type a icon
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url:url,
                    type:'POST',
                    data:{
                        _token:tokenDesactivar
                    },
                    success:function(response){
                        if(response.status == 'success'){
                            let inspector=response.inspector
                            if(idTable == inspector.id){
                                let table = $('#table_users').DataTable();
                                let row = $('#table_users').find(`tr[data-id="${idTable}"]`);
                                table.row(row).remove().draw(false);
                                Swal.fire('¡Hecho!', 'El inspector se desactivó exitosamente', 'success');
                            }
                        }else{
                            Swal.fire('¡Error!', 'Ha ocurrido un error al cambiar el estado del Inspector', 'error');
                        }
                    }
                })
            }
        });
    });
    $(document).on('click','.active_state', function(e) {
        e.preventDefault(); // Prevenir el envío del formulario por defecto
        let tokenActivar = $('#tokenGetData').val()
        let url = $(this).attr('data-url')
        let idTable = $(this).closest('tr').eq(0).attr('data-id')
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Quieres cambiar el estado del inspector? Una vez activado, el inspector estará disponible en Bitácoras y podrá recibir asignaciones de órdenes.',
            icon: 'warning', // Cambiado de type a icon
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) { // Cambiado de result.value a result.isConfirmed
                $.ajax({
                    url:url,
                    type: 'POST',
                    dataType: 'json',
                    data:{
                        _token: tokenActivar
                    },
                    success:function(response){
                        if(response.status == 'success'){
                            let inspector=response.inspector
                            if(idTable == inspector.id){
                                let aprendiz;
                                let table = $('#table_desactivar').DataTable();
                                let row = $('#table_desactivar').find(`tr[data-id="${idTable}"]`);
                                table.row(row).remove().draw();
                                if(inspector.aprendiz == 1){
                                    aprendiz = "<span class='badge bg-warning text-dark'>SI</span>";
                                }else{
                                    aprendiz = "<span class='badge bg-secondary'>NO</span>";
                                }
                                let inspectorRow = "<tr data-id='" + inspector.id + "'>" +
                                                        "<td data-id='" + inspector.id + "'>" + inspector.nombres + "</td>" +
                                                        "<td>" + inspector.apellidos + "</td>" +
                                                        "<td>" + inspector.type_id + "</td>" +
                                                        "<td>" + inspector.cedula + "</td>" +
                                                        "<td>" + inspector.supervisor.name + "</td>" +
                                                        "<td>" + aprendiz + "</td>" +
                                                        "<td><span class='badge badge-success'>Activo</span></td>" +
                                                        "<td>" +
                                                            "<div class='btn-group' role='group' aria-label='Botones'>" +
                                                                "<button class='btn btn-warning modalEditarInspector'>Editar</button>"+
                                                                "<form class='d-inline'>" +
                                                                    "<button type='button' data-url='"+ activeStateUrl.replace('__ID__', inspector.id) + "' class='btn btn-danger change_state'>Desactivar</button>" +
                                                                "</form>" +
                                                            "</div>" +
                                                        "</td>" +
                                                    "</tr>";
                                        $("#table_users").DataTable().row.add($(inspectorRow)).draw(false);

                                Swal.fire('¡Hecho!', 'El inspector se activó exitosamente', 'success');
                            }
                        }else{
                            Swal.fire('¡Error!', 'Ha ocurrido un error al cambiar el estado del Inspector', 'error');
                        }
                    }
                })
            }
        });
    });

    $(document).on('input', '#cedulaCrear',function() {
        this.value = this.value.replace(/[^0-9]/g, '');
    });
});
