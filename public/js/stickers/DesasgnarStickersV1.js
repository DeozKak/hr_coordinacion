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
        if(input.classList.contains('acta')){
            return;
        }
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
            // ... (lógica de pegado existente) ...
        });
    });

    // *** NUEVO: Limpiar campos de seriales de actas y actualizar su total asignado ***
    const actaInputInicio = document.getElementById('desasignar_acta_serial_inicio');
    const actaInputFin = document.getElementById('desasignar_acta_serial_fin');
    const actaAsignadoSpan = document.getElementById("asignado-"+id_acta); // Necesitas el ID de actas aquí

    if (actaInputInicio && actaInputFin) {
        actaInputInicio.value = '';
        actaInputFin.value = '';
        // Busca el total de actas asignadas
        const actasAsignadas = stickersAsignados.find(s => s.id_sticker_tipo == actaInputInicio.closest('tr').querySelector('.cantidad-sticker-desasignar, [id*="desasignar_acta"]').dataset.id);
        const cantidadActas = actasAsignadas ? actasAsignadas.cantidad_asignada : 0;
        console.log(cantidadActas);
        if(actaAsignadoSpan) actaAsignadoSpan.textContent = cantidadActas;
    }


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
        const errorDiv = document.getElementById('errorDesasignar');
        errorDiv.classList.add('d-none');

        // *** MODIFICADO: Construir payload híbrido ***

        // 1. Objeto para stickers cuantitativos
        let stickersCuantitativos = {};
        document.querySelectorAll('#stickerTypeRowsDesasignar .cantidad-sticker-desasignar').forEach(input => {
            if(input.classList.contains('acta')){
                return;
            }
            let val = parseInt(input.value, 10) || 0;
            if(val > 0) {
                stickersCuantitativos[input.name.match(/\[(\d+)\]/)[1]] = val;
            }
        });

        // 2. Objeto para stickers serializados (ACTA)
        let serialesActa = null;
        const serialInicioEl = document.getElementById('desasignar_acta_serial_inicio');
        const serialFinEl = document.getElementById('desasignar_acta_serial_fin');

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
                errorDiv.textContent = 'Debe llenar tanto el serial inicial como el final para ACTAS, o dejar ambos vacíos.';
                errorDiv.classList.remove('d-none');
                return;
            }
        }

        // 3. Validar que se desasignó algo
        if (!idInspector || (Object.keys(stickersCuantitativos).length === 0 && !serialesActa)) {
            errorDiv.textContent = "Debes desasignar al menos un sticker o un rango de actas.";
            errorDiv.classList.remove('d-none');
            return;
        }

        // 4. Construir body final para AJAX
        const payload = {
            idInspector: idInspector,
            stickers: stickersCuantitativos,
            seriales_acta: serialesActa
        };

        try {
            const res = await fetch(url_desasignar, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.getElementById('token').value
                },
                body: JSON.stringify(payload) // *** MODIFICADO ***
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
