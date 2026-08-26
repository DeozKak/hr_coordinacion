function editarAsignacion(localidad, idsTecnicos) {
    document.getElementById('localidad_input').value = localidad;

    // 1. SOLUCIÓN: Convertimos todos los IDs del servidor a Texto
    const arrayIds = idsTecnicos.map(String);

    document.querySelectorAll('.check-tecnico').forEach(check => {
        const asignadoEn = check.getAttribute('data-asignado');
        const checkVal = check.value.toString();
        const lockSpan = document.getElementById('lock_text_' + checkVal);

        // REGLA DE ORO: Si pertenece a esta localidad que estoy editando
        if (arrayIds.includes(checkVal)) {
            check.removeAttribute('disabled'); // <-- CLAVE: Remueve el bloqueo HTML
            check.disabled = false;
            check.checked = true;
            check.closest('.custom-checkbox').classList.remove('bg-light');
            if(lockSpan) lockSpan.style.display = 'none'; // Ocultamos el candado
        }
        // Si está en OTRA localidad
        else if (asignadoEn && asignadoEn !== "") {
            check.setAttribute('disabled', 'disabled');
            check.disabled = true;
            check.checked = false;
            check.closest('.custom-checkbox').classList.add('bg-light');
            if(lockSpan) lockSpan.style.display = 'block'; // Mostramos el candado
        }
        // Si está totalmente libre
        else {
            check.removeAttribute('disabled');
            check.disabled = false;
            check.checked = false;
            check.closest('.custom-checkbox').classList.remove('bg-light');
            if(lockSpan) lockSpan.style.display = 'none';
        }
    });

    $('#modalNuevaAsignacion').modal('show');
}

// 2. Limpiar todo correctamente al cerrar el modal
$('#modalNuevaAsignacion').on('hidden.bs.modal', function () {
    document.getElementById('localidad_input').value = "";

    document.querySelectorAll('.check-tecnico').forEach(check => {
        const asignadoEn = check.getAttribute('data-asignado');
        const checkVal = check.value.toString();
        const lockSpan = document.getElementById('lock_text_' + checkVal);

        check.checked = false;

        if (asignadoEn && asignadoEn !== "") {
            check.setAttribute('disabled', 'disabled');
            check.disabled = true;
            check.closest('.custom-checkbox').classList.add('bg-light');
            if(lockSpan) lockSpan.style.display = 'block';
        } else {
            check.removeAttribute('disabled');
            check.disabled = false;
            check.closest('.custom-checkbox').classList.remove('bg-light');
            if(lockSpan) lockSpan.style.display = 'none';
        }
    });
});
document.getElementById('buscadorTecnicos').addEventListener('input', function() {
    let filtro = this.value.toLowerCase();
    let items = document.querySelectorAll('.item-tecnico');

    items.forEach(function(item) {
        let texto = item.innerText.toLowerCase();
        if(texto.includes(filtro)) {
            item.style.display = 'block';
        } else {
            item.style.display = 'none';
        }
    });
});
document.addEventListener('DOMContentLoaded', function () {
// Muestra el nombre del archivo seleccionado en los inputs personalizados de Bootstrap
    let customFileInputs = document.querySelectorAll('.custom-file-input');

    customFileInputs.forEach(function(input) {
        input.addEventListener('change', function(e) {
            // Obtenemos el nombre del archivo
            let fileName = e.target.files[0].name;
            // Seleccionamos el label asociado a este input
            let nextSibling = e.target.nextElementSibling;
            // Reemplazamos el texto por el nombre del archivo
            nextSibling.innerText = fileName;
        });
    });
    // --- 1. INICIALIZACIÓN DE DATATABLES ---
    // Configuración común para ambas tablas
    const dtOptions = {
        // Se reemplaza la URL por el objeto de traducción directo para evitar errores de red (CORS o CDN)
        language: {
            "processing": "Procesando...",
            "lengthMenu": "Mostrar _MENU_ registros",
            "zeroRecords": "No se encontraron resultados",
            "emptyTable": "Ningún dato disponible en esta tabla",
            "info": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
            "infoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
            "infoFiltered": "(filtrado de un total de _MAX_ registros)",
            "search": "Buscar:",
            "infoThousands": ",",
            "loadingRecords": "Cargando...",
            "paginate": {
                "first": "Primero",
                "last": "Último",
                "next": "Siguiente",
                "previous": "Anterior"
            },
            "aria": {
                "sortAscending": ": Activar para ordenar la columna de manera ascendente",
                "sortDescending": ": Activar para ordenar la columna de manera descendente"
            }
        },
        scrollY: "250px",      // Altura fija con scroll vertical
        scrollCollapse: true,  // Si hay pocos datos, la tabla se encoge
        paging: false,         // Quitamos la paginación para usar solo el scroll
        info: false,           // Oculta el texto "Mostrando 1 a X de X"
        order: [[1, 'desc']],  // Ordenar por la segunda columna de mayor a menor por defecto
    };

    $('#tablaResumen').DataTable(dtOptions);
    $('#tablaTecnicos').DataTable(dtOptions);



    // ============================================================
    // GRÁFICA DE BARRAS: Meses Ejecutados
    // ============================================================
    const rawMesesData = window.datosMeses || {};
    const ctxMeses = document.getElementById('chartMeses');

    if (ctxMeses && Object.keys(rawMesesData).length > 0) {
        // Ordenar las etiquetas numéricamente para la gráfica de barras
        let etiquetasMeses = Object.keys(rawMesesData);
        etiquetasMeses.sort((a, b) => {
            if (a === 'Sin mes') return 1;
            if (b === 'Sin mes') return -1;
            return parseInt(a) - parseInt(b);
        });

        let cantidadesMeses = etiquetasMeses.map(etiqueta => rawMesesData[etiqueta]);

        new Chart(ctxMeses.getContext('2d'), {
            type: 'bar', // Cambiado a Barra
            data: {
                labels: etiquetasMeses,
                datasets: [{
                    label: 'Cantidad Ejecutada',
                    data: cantidadesMeses,
                    backgroundColor: '#20c997', // Un verde agua moderno y único
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } }, // Ocultamos la leyenda
                scales: {
                    y: { beginAtZero: true, ticks: { precision: 0 } },
                    x: { grid: { display: false } }
                }
            }
        });
    }

    $('#tablaProgramacionesHoy').DataTable(dtOptions);

    // ============================================================
    // LÓGICA DEL MODAL DE DETALLES
    // ============================================================
    let tablaDetalleDT = null;

    document.querySelectorAll('.btn-ver-detalle').forEach(btn => {
        btn.addEventListener('click', function() {
            let tipo = this.getAttribute('data-tipo');
            let titulo = this.getAttribute('data-titulo');
            let data = window.datosDetalles[tipo] || [];

            // 1. Cambiar el título del Modal
            document.querySelector('#tituloModalDetalle span').innerText = titulo + ' (' + data.length + ' registros)';

            // 2. Destruir el DataTable anterior si existe
            if (tablaDetalleDT !== null) {
                $('#tablaDetalleRegistros').DataTable().clear().destroy();
            }

            // 3. Llenar el cuerpo de la tabla
            let tbody = document.getElementById('cuerpoTablaDetalles');
            tbody.innerHTML = '';

            data.forEach(fila => {

                let tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="font-weight-bold">${fila.contrato}</td>
                    <td>${fila.operario}</td>
                    <td>${fila.localidad}</td> <!-- Municipio añadido -->
                    <td>${fila.tarea}</td>
                    <td><span class="badge badge-secondary">${fila.cierre}</span></td>
                `;
                tbody.appendChild(tr);
            });

            // 4. Iniciar DataTable para tener buscador y paginación
            tablaDetalleDT = $('#tablaDetalleRegistros').DataTable({
                language: dtOptions.language, // Reutilizamos tu variable de traducción
                pageLength: 15,
                lengthMenu: [10, 15, 25, 50, 100],
                order: [[1, 'asc']] // Ordenar por operario por defecto
            });

            // 5. Mostrar el Modal
            $('#modalVerDetalles').modal('show');
        });
    });

    // ============================================================
// LÓGICA DEL MODAL DE PROGRAMACIONES (Nuevo Diseño)
// ============================================================
    let tablaProgDT = null;

    document.querySelectorAll('.btn-ver-prog').forEach(btn => {
        btn.addEventListener('click', function() {
            let tipo = this.getAttribute('data-tipo');
            let estado = this.getAttribute('data-estado'); // 'ejecutadas' o 'pendientes'

            // Extraer datos usando la variable global
            let data = window.datosProgramaciones[tipo] ? window.datosProgramaciones[tipo][estado] : [];

            let estadoTexto = estado === 'ejecutadas' ? 'Ejecutadas' : 'Pendientes';
            let colorClase = estado === 'ejecutadas' ? 'text-success' : 'text-danger';

            // Actualizar título del modal
            document.querySelector('#tituloModalProg span').innerHTML = `Detalle de Tareas <span class="${colorClase}">${estadoTexto}</span> (ID: ${tipo}) - ${data.length} registros`;

            if (tablaProgDT !== null) {
                $('#tablaDetalleProg').DataTable().clear().destroy();
            }

            let tbody = document.getElementById('cuerpoTablaProg');
            tbody.innerHTML = '';

            data.forEach(fila => {
                let tr = document.createElement('tr');
                let badge = estado === 'ejecutadas'
                    ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Ejecutada</span>'
                    : '<span class="badge badge-danger px-2 py-1"><i class="fas fa-clock mr-1"></i>Pendiente</span>';

                tr.innerHTML = `
                    <td class="font-weight-bold text-primary">${fila.contrato}</td>
                    <td>${fila.orden}</td>
                    <td>${fila.cliente}</td>
                    <td><i class="fas fa-user-circle text-secondary mr-1"></i> ${fila.tecnico}</td>
                    <td>${fila.ciudad}</td>
                    <td class="text-center">${badge}</td>
                `;
                tbody.appendChild(tr);
            });

            // Iniciar DataTable para la tabla de detalles de programaciones
            tablaProgDT = $('#tablaDetalleProg').DataTable({
                language: dtOptions.language,
                pageLength: 10,
                lengthMenu: [10, 25, 50, 100],
                order: [[2, 'asc']] // Ordenar por nombre de técnico por defecto
            });

            $('#modalVerDetallesProg').modal('show');
        });
    });
});



// Prevenir doble clic y mostrar indicador de carga
document.getElementById('formSubirArchivos').addEventListener('submit', function(e) {
    let inputAsignacion = document.getElementById('archivo_asignacion');
    let inputCerradas = document.getElementById('archivo_cerradas');

    // Validación: Comprobar si alguno de los dos está vacío
    if (inputAsignacion.files.length === 0 || inputCerradas.files.length === 0) {
        e.preventDefault(); // Detiene el envío del formulario

        Swal.fire({
            title: 'Archivos incompletos',
            text: 'Debes seleccionar tanto el archivo de Asignación como el de Cerradas para poder continuar.',
            icon: 'warning',
            confirmButtonText: 'Entendido'
        });

        return; // Salimos de la función sin deshabilitar el botón
    }

    // Si ambos archivos están presentes, procedemos a mostrar el loader
    let btn = document.getElementById('btnGuardarArchivos');
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando datos, por favor espere...';
    btn.disabled = true;
    btn.classList.replace('btn-success', 'btn-secondary');
});
