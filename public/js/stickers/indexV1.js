let modalVerAsignados;
document.addEventListener('DOMContentLoaded', function () {
    ManejoModal();
    ManejoModalVerSeriales();
    $('#semanas').DataTable({
        "responsive": true,
        "lengthChange": false,
        "autoWidth": false,
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
        },
        "order": [[1, 'desc']]
    });



});

function ManejoModal(){
    const openStickerBtn = document.getElementById('btnAgregarSticker');
    const modalSticker = document.getElementById('agregarStickerModal');
    const agregarStickerModal = new bootstrap.Modal(modalSticker);

    openStickerBtn.addEventListener('click', function () {
        // Limpia el formulario antes de mostrar
        document.getElementById('formAgregarSticker').reset();
        document.getElementById('campo_cantidad').classList.remove('d-none');
        document.getElementById('campo_seriales').classList.add('d-none');
        document.getElementById('errorAgregar').classList.add('d-none');
        agregarStickerModal.show();
    });

    document.getElementById('btnCancelarSticker').addEventListener('click', function () {
        agregarStickerModal.hide();
    });

    // *** NUEVO: Evento para mostrar/ocultar campos según el tipo de sticker ***
    document.getElementById('tipoSticker').addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const nombreSticker = selectedOption.dataset.nombre || ''; // Usamos data-nombre

        const campoCantidad = document.getElementById('campo_cantidad');
        const campoSeriales = document.getElementById('campo_seriales');

        if (nombreSticker.includes('actas')) {
            campoCantidad.classList.add('d-none');
            campoSeriales.classList.remove('d-none');
            document.getElementById('cantidad').value = ''; // Limpia campo
        } else {
            campoCantidad.classList.remove('d-none');
            campoSeriales.classList.add('d-none');
            document.getElementById('serial_inicio').value = ''; // Limpia campos
            document.getElementById('serial_fin').value = ''; // Limpia campos
        }
    });


    // Manejo del formulario agregar stickers
    document.getElementById('formAgregarSticker').addEventListener('submit', async function (e) {
        e.preventDefault();

        // 1. Referencia al botón y deshabilitación inmediata
        const btnSubmit = this.querySelector('button[type="submit"]');
        const originalHTML = btnSubmit.innerHTML; // Guardamos el texto original (ej: "Agregar")

        btnSubmit.disabled = true;
        btnSubmit.innerHTML = '<i class="fa fa-spinner fa-spin"></i> Procesando...';

        const tipoSelect = document.getElementById('tipoSticker');
        const tipo = tipoSelect.value;
        const optionText = (tipoSelect.options[tipoSelect.selectedIndex].dataset.nombre || '').toLowerCase();

        const errorDiv = document.getElementById('errorAgregar');
        errorDiv.classList.add('d-none');
        errorDiv.textContent = '';

        // Validación básica
        if (!tipo) {
            errorDiv.textContent = "Debe seleccionar un tipo de sticker.";
            errorDiv.classList.remove('d-none');
            // Re-habilitamos si hay error de validación
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHTML;
            return;
        }

        let payload = {}; // Payload que se enviará

        // *** MODIFICADO: Construir payload dinámicamente ***
        if (optionText.includes('actas')) {
            // Es ACTA, enviamos seriales
            const serial_inicio = document.getElementById('serial_inicio').value;
            const serial_fin = document.getElementById('serial_fin').value;

            if (!serial_inicio || !serial_fin || parseInt(serial_fin) < parseInt(serial_inicio)) {
                errorDiv.textContent = "Debe ingresar un rango de seriales válido (el final debe ser mayor o igual al inicial).";
                errorDiv.classList.remove('d-none');
                return;
            }
            payload = {
                serial_inicio: serial_inicio,
                serial_fin: serial_fin
            };

        } else {
            // Es sticker normal, enviamos cantidad
            const cantidad = parseInt(document.getElementById('cantidad').value, 10);
            if (isNaN(cantidad) || cantidad < 1) {
                errorDiv.textContent = "Debe ingresar una cantidad válida (mayor a 0).";
                errorDiv.classList.remove('d-none');
                return;
            }
            payload = {
                cantidad: cantidad
            };
        }

        // Añadir token al payload
        payload._token = document.getElementById('token').value;

        // Llamamos a la función AJAX actualizada
        const resultado = await actualizarInventario(tipo, payload);

        if (resultado !== 1) {
            errorDiv.textContent = resultado;
            errorDiv.classList.remove('d-none');
            // Re-habilitamos si el servidor devolvió un error
            btnSubmit.disabled = false;
            btnSubmit.innerHTML = originalHTML;
            return;
        }

        // Si tuvo éxito, el reload refrescará la página,
        // pero es buena práctica resetear por si acaso
        agregarStickerModal.hide();
        this.reset();
        window.location.reload();

        // Cierra el modal y limpia
        agregarStickerModal.hide();
        this.reset();
        document.getElementById('campo_cantidad').classList.remove('d-none');
        document.getElementById('campo_seriales').classList.add('d-none');

        // Recargamos para ver los cambios (opcional, pero simple)
        window.location.reload();
    });
}

/**
 * Función AJAX actualizada para enviar un payload (cantidad O seriales)
 * @param {string} id_tipo
 * @param {object} payload
 * @returns
 */
function actualizarInventario(id_tipo, payload) {
    return new Promise((resolve, reject) => {
        let url = document.getElementById('url_ActualizarInventario').value;
        let url_def = url.replace(':id', id_tipo);

        $.ajax({
            type: 'POST',
            url: url_def,
            data: payload, // *** MODIFICADO: Enviamos el payload completo ***
            success: function (response) {
                if (response.success) {
                    // Actualiza la celda (si la encontramos)
                    const invElement = document.getElementById('inventario_' + id_tipo);
                    if(invElement) {
                        invElement.textContent = response.value;
                    }

                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: response.success,
                        showConfirmButton: false,
                        toast: true,
                        timer: 3000
                    });
                    resolve(1);  // Todo bien
                } else {
                    resolve(response.error || 'No se pudo actualizar el inventario');
                }
            },
            error: function (xhr) {
                // Muestra el error recibido del backend si existe
                let errorMsg = 'Error en el servidor';
                if (xhr.responseJSON) {
                    if (xhr.responseJSON.error) {
                        // Si es un string
                        if (typeof xhr.responseJSON.error === 'string') {
                            errorMsg = xhr.responseJSON.error;
                        }
                        // Si es un objeto de validación
                        else if (typeof xhr.responseJSON.error === 'object') {
                            errorMsg = Object.values(xhr.responseJSON.error).join(' ');
                        }
                    } else if (xhr.responseJSON.message) {
                        errorMsg = xhr.responseJSON.message;
                    }
                }
                resolve(errorMsg);
            }
        });
    });
}
/**
 * NUEVA FUNCIÓN para manejar el modal "Ver Seriales de Actas"
 */
function ManejoModalVerSeriales() {
    const btnVerSeriales = document.getElementById('btnVerSerialesActa');
    const modalElement = document.getElementById('modalVerSerialesActa');

    // Si el botón no existe (ej. un usuario sin permisos), no hagas nada
    if (!btnVerSeriales || !modalElement) {
        return;
    }

    const modalVer = new bootstrap.Modal(modalElement);
    const urlSeriales = document.getElementById('urlGetSerialesActas').value;
    const modalBody = document.getElementById('listaSerialesBody');
    const loaderHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>';

    btnVerSeriales.addEventListener('click', async function () {
        // 1. Mostrar modal y loader
        modalBody.innerHTML = loaderHTML;
        modalVer.show();

        try {
            // 2. Hacer la llamada AJAX
            const response = await fetch(urlSeriales);
            if (!response.ok) {
                throw new Error('Error al conectar con el servidor');
            }

            const data = await response.json();

            // 3. Procesar la respuesta
            if (data.rangos && data.rangos.length > 0) {
                let html = '<ul class="list-group">';
                data.rangos.forEach(rango => {
                    html += `<li class="list-group-item">${rango}</li>`;
                });
                html += '</ul>';
                modalBody.innerHTML = html;
            } else if (data.rangos) {
                modalBody.innerHTML = '<p class="text-info">No hay seriales de Actas en inventario.</p>';
            } else {
                throw new Error(data.error || 'No se recibieron datos válidos');
            }

        } catch (error) {
            console.error("Error al cargar seriales:", error);
            modalBody.innerHTML = `<p class="text-danger">Error: ${error.message}</p>`;
        }
    });
    document.getElementById('cerrarModal_actasInventario').addEventListener('click', function () {
        modalVer.hide();
    })
}

async function verSerialesAsignados(idInspector, nombreInspector) {

    const modalElement = document.getElementById('modalVerSerialesAsignados');
    if (!modalVerAsignados) {
        modalVerAsignados = new bootstrap.Modal(modalElement);
    }

    const urlBase = document.getElementById('urlGetSerialesAsignados').value;
    const urlSeriales = urlBase.replace(':id', idInspector);
    const modalBody = document.getElementById('listaSerialesAsignadosBody');
    const loaderHTML = '<div class="text-center"><i class="fa fa-spinner fa-spin"></i> Cargando...</div>';

    // 1. Poner nombre y loader
    document.getElementById('nombreInspectorSeriales').textContent = nombreInspector;
    modalBody.innerHTML = loaderHTML;
    modalVerAsignados.show();
    document.getElementById('cerrarModal_actasInspector').addEventListener('click', function () {
        modalVerAsignados.hide();
    })

    try {
        // 2. Hacer la llamada AJAX
        const response = await fetch(urlSeriales);
        if (!response.ok) {
            throw new Error('Error al conectar con el servidor');
        }

        const data = await response.json();

        // 3. Procesar la respuesta
        if (data.rangos && data.rangos.length > 0) {
            let html = '<ul class="list-group">';
            data.rangos.forEach(rango => {
                html += `<li class="list-group-item">${rango}</li>`;
            });
            html += '</ul>';
            modalBody.innerHTML = html;
        } else if (data.rangos) {
            modalBody.innerHTML = '<p class="text-info">Este inspector no tiene seriales de Actas asignados.</p>';
        } else {
            throw new Error(data.error || 'No se recibieron datos válidos');
        }

    } catch (error) {
        console.error("Error al cargar seriales asignados:", error);
        modalBody.innerHTML = `<p class="text-danger">Error: ${error.message}</p>`;
    }
}
