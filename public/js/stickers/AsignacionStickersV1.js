let modal;
async function actualizarInventariosModal() {
    const url = document.getElementById('urlGetInventario').value;
    try {
        const response = await fetch(url);
        const data = await response.json();
        data.forEach(item => {
            let input = document.querySelector(`.cantidad-sticker[data-id="${item.id}"]`);
            let saldo = document.getElementById('saldo-' + item.id);

            // Actualiza también el saldo de ACTAS
            if (!input && item.nombre.toLowerCase().includes('actas') && saldo) {
                saldo.textContent = item.inventario;
                return; // Salta al siguiente
            }

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

    // Limpia campos de cantidad
    document.querySelectorAll('.cantidad-sticker').forEach(input => {
        input.value = '';
        let id = input.dataset.id;
        let inventario = input.dataset.inventario;
        document.getElementById('saldo-' + id).textContent = inventario;
    });

    // *** NUEVO: Limpia campos de seriales de actas ***
    if (document.getElementById('acta_serial_inicio')) {
        document.getElementById('acta_serial_inicio').value = '';
        document.getElementById('acta_serial_fin').value = '';
    }

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
        const errorDiv = document.getElementById('errorAsignar');
        errorDiv.classList.add('d-none');

        // *** MODIFICADO: Construir payload híbrido ***

        // 1. Objeto para stickers cuantitativos
        let stickersCuantitativos = {};
        document.querySelectorAll('#stickerTypeRows .cantidad-sticker').forEach(input => {
            let val = parseInt(input.value, 10) || 0;
            if (val > 0) { // Solo envía los mayores a 0
                stickersCuantitativos[input.name.match(/\[(\d+)\]/)[1]] = val;
            }
        });

        // 2. Objeto para stickers serializados (ACTA)
        let serialesActa = null;
        const serialInicioEl = document.getElementById('acta_serial_inicio');
        const serialFinEl = document.getElementById('acta_serial_fin');

        if (serialInicioEl && serialFinEl) {
            const serialInicio = serialInicioEl.value;
            const serialFin = serialFinEl.value;

            if (serialInicio && serialFin) {
                if (parseInt(serialFin) < parseInt(serialInicio)) {
                    errorDiv.textContent = 'El serial final de ACTA debe ser mayor o igual al inicial.';
                    errorDiv.classList.remove('d-none');
                    return;
                }
                serialesActa = {
                    serial_inicio: serialInicio,
                    serial_fin: serialFin
                };
            } else if (serialInicio || serialFin) {
                // Si solo llenó uno de los dos campos
                errorDiv.textContent = 'Debe llenar tanto el serial inicial como el final para ACTAS, o dejar ambos vacíos.';
                errorDiv.classList.remove('d-none');
                return;
            }
        }

        // 3. Validar que se asignó algo
        if (!idInspector || (Object.keys(stickersCuantitativos).length === 0 && !serialesActa)) {
            errorDiv.textContent = "Debes asignar al menos un sticker o un rango de actas.";
            errorDiv.classList.remove('d-none');
            return;
        }

        // 4. Construir body final para AJAX
        const payload = {
            idInspector: idInspector,
            stickers: stickersCuantitativos,
            seriales_acta: serialesActa
        };

        // AJAX
        try {
            const res = await fetch(url_asignar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.getElementById('token').value
                },
                body: JSON.stringify(payload) // *** MODIFICADO ***
            });
            const data = await res.json();

            if (res.ok && data.success) {
                modal.hide();
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: data.success,
                    showConfirmButton: false,
                    toast: true,
                    timer: 3000
                })
                setTimeout(() => {window.location.reload();},3100)
            } else {
                throw new Error(data.error || 'Error al asignar');
            }
        } catch (err) {
            console.log(err);
            errorDiv.textContent = err.message;
            errorDiv.classList.remove('d-none');
        }
    });
});
