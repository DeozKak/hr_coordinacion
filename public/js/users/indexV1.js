let token;
let table;
function toggleHiddenContainer(event) {
    const hiddenContainer = event.target.parentElement.querySelector('.hidden-permissions');
    if (hiddenContainer) {
        const isHidden = hiddenContainer.style.display === 'none' || hiddenContainer.style.display === '';
        hiddenContainer.style.display = isHidden ? 'flex' : 'none';
    }
}

function AsingEvent(){
    // Busca todos los botones "+X más"
    document.querySelectorAll('.badge-more').forEach(badge => {
            badge.addEventListener('click', toggleHiddenContainer);
    });
}
function DestroyEvent(){
    // Eliminar todos los listeners
    document.querySelectorAll('.badge-more').forEach(badge => {
        badge.removeEventListener('click', toggleHiddenContainer);
    });

}

function InitDatatable(){
    table = $('#table_users').DataTable({
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

}
$(document).ready(function() {

    AsingEvent();

    InitDatatable();

    $(document).on('click', '.edit-btn', function() {
        const user = $(this).data('user')
        $('#idUsuarioEditar').val(user.id)
        $('#nombresEditar').val(user.name)
        $('#emailEditar').val(user.email)
        $('#typeidEditar').val(user.type_id)
        $('#cedulaEditar').val(user.identification)
        console.log(user);
        // Asegúrate de que este valor coincide con el `value` de tus opciones
        $('#rolesEditar').val(user.roles[0].name)

        let divClave = $('.div-clave')
        if(divClave.length == 2){
            divClave.remove()
            $('#cancelarClave').remove()
            $('#cambiarClave').show()
        }

        let id = user.id
        let url = $(this).attr('data-url')

        token = $(this).attr('data-token')

        let url2 = $('#urlEnviar').val()

        $.post({
            url:url,
            data:{
                _token: token,
                id: id,
            },success:function(response){
                let asignadas = response.asignadas
                let disponibles = response.disponibles

                $('#revokedPermissions').empty()
                $('#assignedPermissions').empty()

                disponibles.forEach(element => {
                    // console.log(element.name)
                    $('#revokedPermissions').append('<option value="'+element.name+'">'+element.name+'</option>');
                });

                asignadas.forEach(element => {
                    // console.log(element.name)
                    $('#assignedPermissions').append('<option value="'+element.name+'">'+element.name+'</option>');
                });

            }
        })
    });

    $(document).on("click","#guardarEditarUsuario",function(){
        let id = $('#idUsuarioEditar').val()
        let nombreGuardar=$("#nombresEditar").val()
        let emailGuardar=$("#emailEditar").val()
        let rolGuardar = $("#rolesEditar").val()
        let urlEditar=$(this).attr("data-url")
       // let token = $('.tokenUser').val()
        let claveNueva = $('#claveNueva').val()
        let claveConfirmar = $('#claveConfirmar').val()

        let assignedPermissions =[]
        let revokedPermissions = []
        $('#assignedPermissions option').each(function() {
            let value = $(this).val()
            assignedPermissions.push(value)
        });

        $('#revokedPermissions option').each(function() {
            let value = $(this).val()
            revokedPermissions.push(value)
        });

        if((claveNueva != undefined && claveNueva.trim() == "") ||
            (claveConfirmar != undefined && claveConfirmar.trim() == "")){
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'El campo de contraseña es obligatorio',
            })
            return
        }

        if (nombreGuardar == "" || emailGuardar == "") {
            Swal.fire({
                icon: 'warning',
                title: 'Advertencia',
                text: 'El campo de nombre y email son obligatorios',
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
                    email:emailGuardar,
                    roles:rolGuardar,
                    assignedPermissions:assignedPermissions,
                    revokedPermissions:revokedPermissions,
                    claveNueva:claveNueva,
                    claveConfirmar:claveConfirmar
                },
                success:function(response){
                    switch (response.status) {
                        case 'success' :
                            let row = $('#table_users tbody tr[data-id="' + response.user.id + '"]');
                            row.find('td:nth-child(1)').text(response.user.name);
                            row.find('td:nth-child(2)').text(response.user.email);
                            row.find('td:nth-child(5)').html('<span class="badge-modern badge-primary-modern">' + rolGuardar + '</span>');
                            // --- LÓGICA PARA ACTUALIZAR LOS PERMISOS ---

                            // 1. Selecciona y vacía la celda de permisos
                            let permissionsCell = row.find('td:nth-child(6)');
                            permissionsCell.empty();

                            // 2. Define el límite y obtén los permisos de la respuesta
                            const limit = 3;
                            //let permissions = response.user.permissions; // El array de permisos

                            // 3. Prepara el HTML que se va a insertar
                            let visiblePermissionsHTML = '';
                            let hiddenPermissionsHTML = '';

                            // 4. Recorre los permisos para construir los badges
                            assignedPermissions.forEach((permission, index) => {

                                const badge = '<span class="badge-modern badge-primary-modern">' + permission + '</span>';
                                if (index < limit) {
                                    visiblePermissionsHTML += badge;
                                } else {
                                    hiddenPermissionsHTML += badge;
                                }
                            });

                            // 5. Construye el contenedor final
                            let finalHTML = '<div class="permission-tags">';
                            finalHTML += visiblePermissionsHTML; // Añade los visibles

                            // Si hay más permisos que el límite, añade el botón "+X más" y los ocultos
                            if (assignedPermissions.length > limit) {
                                const remainingCount = assignedPermissions.length - limit;
                                finalHTML += '<span class="badge-modern badge-more">+' + remainingCount + ' más</span>';
                                finalHTML += '<div class="hidden-permissions">' + hiddenPermissionsHTML + '</div>';
                            }

                            finalHTML += '</div>';

                            // 6. Inserta el HTML completo en la celda
                            permissionsCell.html(finalHTML);
                            $('#editUserModal').modal('hide');
                            Swal.fire({
                                icon: 'success',
                                title: 'Actualización exitosa',
                                text: response.message,
                            });
                            break;
                        case 'error' :
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: response.message,
                            });
                            break;
                        case 'passwordDiff' :
                            Swal.fire({
                                icon: 'warning',
                                title: 'Advertencia',
                                text: response.message,
                            });
                        case 'passowordLength' :
                            Swal.fire({
                                icon: 'warning',
                                title: 'Advertencia',
                                text: response.message,
                            });
                            break;
                    }
                    table.destroy();
                    DestroyEvent();
                    AsingEvent();
                    InitDatatable();
                }, error: function(xhr) {

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Hubo un problema al actualizar el Usuario',
                    });
                }
            })
        }
    })

    $(document).on('click','#desactive_user, #active_user', function(){
        let token = $('#token').val()

        let url = $(this).attr('data-url')
     /*   let changeStateUrl = "{{ route('admin.changeStatus', ['user' => '__ID__']) }}";*/
        Swal.fire({
            title: '¿Estás seguro?',
            text: '¿Quieres cambiar el estado del usuario?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, cambiar estado',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            console.log(token);
            if (result.isConfirmed) {
                $.ajax({
                    url:url,
                    type:'POST',
                    data:{
                        _token:token
                    },
                    success:function(response){
                        let user = response.user
                        console.log(user)
                        let row = $('#table_users tbody tr[data-id="' + user.id + '"]');
                        if(user.state == 0){
                            row.find('td:nth-child(7)').html('<span class="badge-modern badge-danger-modern">Inactivo</span>');
                            row.find('td:nth-child(8) #desactive_user').remove()
                            let newButton = "<button type='button' id='active_user' data-url='"+ changeStateUrl.replace('__ID__', user.id) + "' class='btn-gradient btn-gradient-success btn-sm'>Activar</button>"
                            row.find('td:nth-child(8) .changeForm').append(newButton);
                        }else{
                            row.find('td:nth-child(7)').html('<span class="badge-modern badge-success-modern">Activo</span>');
                            row.find('td:nth-child(8) #active_user').remove()
                            let newButton = "<button type='button' id='desactive_user' data-url='"+ changeStateUrl.replace('__ID__', user.id) + "' class='btn-gradient btn-gradient-danger btn-sm'>Desactivar</button>"
                            row.find('td:nth-child(8) .changeForm').append(newButton);
                        }
                    }
                })
            }
        });
    });

    $(document).on('click','#cambiarClave',function(){
        let newBlock =  "<div class='form-group mt-3 div-clave'>"+
            "<label for='claveNueva'>Nueva contraseña</label>"+
            "<input type='password' id='claveNueva' class='form-control'>"+
            "<i class='fa fa-eye' id='togglePassword' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>"+
            "</div>"+
            "<div class='form-group div-clave'>"+
            "<label for='claveConfirmar'>Confirmar contraseña</label>"+
            "<input type='password' id='claveConfirmar' class='form-control'>"+
            "<i class='fa fa-eye' id='togglePasswordConfirm' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>"+
            "</div>";

        $('.permissions').append(newBlock)

        $('#cambiarClave').hide()

        let newButton = "<button type='button' id='cancelarClave' class='btn btn-danger'>Cancelar cambio</button>"

        $('.modal-footer').append(newButton)
    })

    $(document).on('click', '#togglePassword',function(){
        const passwordField = $('#claveNueva');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        // Cambia el icono entre ojo abierto y cerrado
        $(this).toggleClass('fa-eye fa-eye-slash');
    })

    $(document).on('click', '#togglePasswordConfirm',function(){
        const passwordField = $('#claveConfirmar');
        const type = passwordField.attr('type') === 'password' ? 'text' : 'password';
        passwordField.attr('type', type);

        // Cambia el icono entre ojo abierto y cerrado
        $(this).toggleClass('fa-eye fa-eye-slash');
    })

    $(document).on('click', '#cancelarClave',function(){
        $('.div-clave').remove()
        $('#cambiarClave').show()
        $('#cancelarClave').remove()
    })

    // Asignar y remover permisos con botones
    $('#assignPermission').click(function() {
        $('#revokedPermissions option:selected').each(function() {
            $(this).remove().appendTo('#assignedPermissions');
        });
    });

    $('#removePermission').click(function() {
        $('#assignedPermissions option:selected').each(function() {
            $(this).remove().appendTo('#revokedPermissions');
        });
    });
});

