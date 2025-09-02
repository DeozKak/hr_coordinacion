

document.addEventListener('DOMContentLoaded', function () {

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

   ManejoModal();


});

 function ManejoModal(){
    const openStickerBtn = document.getElementById('btnAgregarSticker');
    const modalSticker = document.getElementById('agregarStickerModal');
    const agregarStickerModal = new bootstrap.Modal(modalSticker);

    openStickerBtn.addEventListener('click', function () {
        agregarStickerModal.show();
    });

    document.getElementById('btnCancelarSticker').addEventListener('click', function () {
        agregarStickerModal.hide();
    });

    const cantidadInput = document.getElementById('cantidad');

    // Permitir solo números al escribir
    cantidadInput.addEventListener('input', function (e) {
        // Quitamos todo lo que no sea número
        let valorNumerico = this.value.replace(/[^0-9]/g, '');
        // Limitar el valor a 10000
        if (valorNumerico !== '' && parseInt(valorNumerico, 10) > 10000) {
            valorNumerico = '10000';
        }
        this.value = valorNumerico;
    });

    // Opcional: prevenir que se peguen letras
    cantidadInput.addEventListener('paste', function (e) {
        const textoPegado = (e.clipboardData || window.clipboardData).getData('text');
        if (!/^\d+$/.test(textoPegado)) {
            e.preventDefault();
        }
    });


    // Manejo del formulario agregar stickers
    document.getElementById('formAgregarSticker').addEventListener('submit', async function (e) {
        e.preventDefault();
        const tipo = document.getElementById('tipoSticker').value;
        const cantidad = parseInt(document.getElementById('cantidad').value, 10);
        const errorDiv = document.getElementById('errorAgregar');
        errorDiv.classList.add('d-none');
        errorDiv.textContent = '';

        if (!tipo || isNaN(cantidad) || cantidad < 1) {
            errorDiv.textContent = "Debe completar correctamente los campos.";
            errorDiv.classList.remove('d-none');
            return;
        }
        console.log(tipo, cantidad);
        // Aquí realizar AJAX para guardar en backend. Demo: actualizar en pantalla

        const resultado = await actualizarInventario(tipo, cantidad);

        if (resultado !== 1) {
            errorDiv.textContent = resultado;
            errorDiv.classList.remove('d-none');
            return;
        }


        // Cierra el modal y limpia
        agregarStickerModal.hide();
        this.reset();

    });
}

function actualizarInventario(id_tipo, cantidad) {
    return new Promise((resolve, reject) => {
        let url = document.getElementById('url_ActualizarInventario').value;
        let url_def = url.replace(':id', id_tipo);
        const token = document.getElementById('token').value;
        $.ajax({
            type: 'POST',
            url: url_def,
            data: {cantidad: cantidad, _token: token},
            success: function (response) {
                if (response.success) {
                    // Actualiza la celda con el nuevo valor
                    document.getElementById('inventario_' + id_tipo).textContent = response.value;
                    resolve(1);  // Todo bien
                } else {
                    resolve('Error: No se pudo actualizar el inventario');
                }
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: response.success,
                    showConfirmButton: false,
                    toast: true,
                    timer: 3000
                })
            },
            error: function (xhr) {
                // Muestra el error recibido del backend si existe
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    resolve(xhr.responseJSON.error);
                } else {
                    resolve('Error en el servidor');
                }
            }
        });
    });
}
