let modal;
async function actualizarInventariosModal() {
    const url = document.getElementById('urlGetInventario').value;
    try {
        const response = await fetch(url);
        const data = await response.json();
        data.forEach(item => {
            let input = document.querySelector(`.cantidad-sticker[data-id="${item.id}"]`);
            let saldo = document.getElementById('saldo-' + item.id);

            if (input && saldo) {
                const actual = input.value ? parseInt(input.value, 10) : 0;
                // Actualiza inventario "en vivo"
                input.setAttribute('data-inventario', item.inventario);
                saldo.textContent = item.inventario - actual >= 0 ? item.inventario - actual : 0;

                // Elimina listeners anteriores para evitar duplicidad
                input.oninput = null;
                input.onpaste = null;

                // Validar solo números y máximo inventario
                input.addEventListener('input', function (e) {
                    let valorNumerico = this.value.replace(/[^0-9]/g, '');
                    let max = parseInt(this.getAttribute('data-inventario'), 10) || 0;
                    if (valorNumerico !== '' && parseInt(valorNumerico, 10) > max) {
                        valorNumerico = String(max);
                    }
                    this.value = valorNumerico;
                });

                // Prevenir pegar texto no numérico y limitar al máximo
                input.addEventListener('paste', function (e) {
                    const textoPegado = (e.clipboardData || window.clipboardData).getData('text');
                    if (!/^\d+$/.test(textoPegado)) {
                        e.preventDefault();
                    } else {
                        let max = parseInt(this.getAttribute('data-inventario'), 10) || 0;
                        if (parseInt(textoPegado, 10) > max) {
                            e.preventDefault();
                            this.value = max;
                        }
                    }
                });
            }
        });
    } catch (e) {
        console.error("No se pudo actualizar inventario", e);
    }
}



// Cuando se abra el modal, limpia y pone los saldos en inventario
async function asignarSticker(idInspector, nombreInspector) {
    // Actualizar inventario dinámicamente antes de mostrar el modal
    await actualizarInventariosModal();

    document.getElementById('idInspector').value = idInspector;
    document.getElementById('nombreInspector').textContent = nombreInspector;
    document.querySelectorAll('.cantidad-sticker').forEach(input => {
        input.value = '';
        let id = input.dataset.id;
        let inventario = input.dataset.inventario;
        document.getElementById('saldo-' + id).textContent = inventario;
    });

    document.getElementById('errorAsignar').classList.add('d-none');
     modal = new bootstrap.Modal(document.getElementById('modalAsignarSticker'));
    modal.show();

    document.getElementById('btn_cerrarAsignar').addEventListener('click', function () {
        modal.hide();
    })
    // Cuando cambias la cantidad, actualiza el saldo mostrado
    document.addEventListener('input', function (e) {
        if (e.target.classList.contains('cantidad-sticker')) {
            let id = e.target.dataset.id;
            let inventario = parseInt(e.target.dataset.inventario, 10);
            let cantidad = parseInt(e.target.value, 10) || 0;
            let saldo = inventario - cantidad;
            // Nunca muestres negativo
            saldo = saldo < 0 ? 0 : saldo;
            document.getElementById('saldo-' + id).textContent = saldo;
        }
    });

}


document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('formAsignarSticker').addEventListener('submit', async function (e) {
        const url_asignar = document.getElementById('urlAsignarSticker').value;
        e.preventDefault();
        const idInspector = document.getElementById('idInspector').value;
        // Lee todos los inputs de tipo sticker con su cantidad
        const stickerInputs = document.querySelectorAll('#stickerTypeRows input');
        // Construye objeto { id_sticker_tipo: cantidad }
        let stickers = {};
        stickerInputs.forEach(input => {
            let val = parseInt(input.value, 10) || 0;
            if(val > 0) { // Solo envía los mayores a 0
                stickers[input.name.match(/\[(\d+)\]/)[1]] = val;
            }
        });

        const errorDiv = document.getElementById('errorAsignar');
        if (!idInspector || Object.keys(stickers).length === 0) {
            errorDiv.textContent = "Debes asignar al menos un sticker.";
            errorDiv.classList.remove('d-none');
            return;
        }

        // AJAX (ajusta según tu endpoint)
        try {
            const res = await fetch(url_asignar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.getElementById('token').value
                },
                body: JSON.stringify({
                    idInspector,
                    stickers
                })
            });
            const data = await res.json();
            if (res.ok && data.success) {
                modal.hide();

                // Actualiza la tabla aquí si lo necesitas
            } else {
                throw new Error(data.error || 'Error al asignar');
            }
            Swal.fire({
                position: "top-end",
                icon: "success",
                title: data.success,
                showConfirmButton: false,
                toast: true,
                timer: 3000
            })
            setTimeout(() => {window.location.reload();},3100)

        } catch (err) {
            console.log(err);
            errorDiv.textContent = err.message;
            errorDiv.classList.remove('d-none');
        }
    });
});
