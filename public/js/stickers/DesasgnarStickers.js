let modalDesasignar;

// Función para obtener stickers asignados a un inspector
async function obtenerStickersAsignados(idInspector) {
    const url = document.getElementById('getStickersAsignados').value;
    let url_def = url.replace(':id', idInspector);
    try {
        const response = await fetch(url_def);
        const data = await response.json();
        return data;
    } catch (e) {
        console.error("No se pudieron obtener los stickers asignados", e);
        return [];
    }
}

// Función para abrir modal de desasignar
async function desasignarSticker(idInspector, nombreInspector = '') {
    // Obtener stickers asignados al inspector
    const stickersAsignados = await obtenerStickersAsignados(idInspector);

    // Si no se proporcionó el nombre, intentar obtenerlo del DOM
    if (!nombreInspector) {
        const row = document.querySelector(`button[onclick*="${idInspector}"]`).closest('tr');
        nombreInspector = row.cells[0].textContent.trim();
    }

    document.getElementById('idInspectorDesasignar').value = idInspector;
    document.getElementById('nombreInspectorDesasignar').textContent = nombreInspector;

    // Limpiar campos y configurar máximos
    document.querySelectorAll('.cantidad-sticker-desasignar').forEach(input => {
        input.value = '';
        const id = input.dataset.id;
        const stickerAsignado = stickersAsignados.find(s => s.id_sticker_tipo == id);
        const cantidadAsignada = stickerAsignado ? stickerAsignado.cantidad_asignada : 0;

        input.setAttribute('data-asignado', cantidadAsignada);
        input.setAttribute('max', cantidadAsignada);
        document.getElementById('asignado-' + id).textContent = cantidadAsignada;

        // Validación para no exceder lo asignado
        input.addEventListener('input', function (e) {
            let valorNumerico = this.value.replace(/[^0-9]/g, '');
            let max = parseInt(this.getAttribute('data-asignado'), 10) || 0;
            if (valorNumerico !== '' && parseInt(valorNumerico, 10) > max) {
                valorNumerico = String(max);
            }
            this.value = valorNumerico;
        });

        input.addEventListener('paste', function (e) {
            const textoPegado = (e.clipboardData || window.clipboardData).getData('text');
            if (!/^\d+$/.test(textoPegado)) {
                e.preventDefault();
            } else {
                let max = parseInt(this.getAttribute('data-asignado'), 10) || 0;
                if (parseInt(textoPegado, 10) > max) {
                    e.preventDefault();
                    this.value = max;
                }
            }
        });
    });

    document.getElementById('errorDesasignar').classList.add('d-none');
    modalDesasignar = new bootstrap.Modal(document.getElementById('modalDesasignarSticker'));
    modalDesasignar.show();

    document.getElementById('btn_cerrarDesasignar').addEventListener('click', function () {
        modalDesasignar.hide();
    });
}

// Evento para el formulario de desasignar
document.addEventListener('DOMContentLoaded', function () {
    document.getElementById('formDesasignarSticker').addEventListener('submit', async function (e) {
        const url_desasignar = document.getElementById('urlDesasignarSticker').value;
        e.preventDefault();

        const idInspector = document.getElementById('idInspectorDesasignar').value;
        const stickerInputs = document.querySelectorAll('#stickerTypeRowsDesasignar input');

        let stickers = {};
        stickerInputs.forEach(input => {
            let val = parseInt(input.value, 10) || 0;
            if(val > 0) {
                stickers[input.name.match(/\[(\d+)\]/)[1]] = val;
            }
        });

        const errorDiv = document.getElementById('errorDesasignar');
        if (!idInspector || Object.keys(stickers).length === 0) {
            errorDiv.textContent = "Debes desasignar al menos un sticker.";
            errorDiv.classList.remove('d-none');
            return;
        }

        try {
            const res = await fetch(url_desasignar, {
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
                modalDesasignar.hide();

                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: data.success,
                    showConfirmButton: false,
                    toast: true,
                    timer: 3000
                });

                setTimeout(() => {window.location.reload();}, 3100);
            } else {
                throw new Error(data.error || 'Error al desasignar');
            }
        } catch (err) {
            console.log(err);
            errorDiv.textContent = err.message;
            errorDiv.classList.remove('d-none');
        }
    });
});
