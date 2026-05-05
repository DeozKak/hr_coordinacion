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


    // --- 2. CONFIGURACIÓN DE LA GRÁFICA (Estilo Moderno) ---
    Chart.defaults.font.family = "'Nunito', 'Segoe UI', 'Arial', sans-serif";
    Chart.defaults.color = '#6c757d';

    const rawData = rowDaraV;
    const labelsX = labelsXV;

    function calcularDatosGrafica(localidadSeleccionada) {
        let datosCalculados = new Array(labelsX.length).fill(0);
        if (localidadSeleccionada === 'todas') {
            Object.keys(rawData).forEach(loc => {
                rawData[loc].forEach(item => {
                    let indice = labelsX.indexOf(item.criterio.toString());
                    if (indice !== -1) datosCalculados[indice] += item.cantidad;
                });
            });
        } else {
            if (rawData[localidadSeleccionada]) {
                rawData[localidadSeleccionada].forEach(item => {
                    let indice = labelsX.indexOf(item.criterio.toString());
                    if (indice !== -1) datosCalculados[indice] += item.cantidad;
                });
            }
        }
        return datosCalculados;
    }

    const ctx = document.getElementById('pendientesChart').getContext('2d');
    let chartPendientes = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labelsX,
            datasets: [{
                label: 'Trabajos Pendientes',
                data: calcularDatosGrafica('todas'),
                backgroundColor: 'rgba(23, 162, 184, 0.8)', // Color moderno similar al de estadísticas
                borderRadius: 6 // Barras redondeadas
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } }, // Ocultamos leyenda por estética
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [5, 5] } },
                x: { grid: { display: false } }
            }
        }
    });

    document.getElementById('localidad-chart-select').addEventListener('change', function() {
        chartPendientes.data.datasets[0].data = calcularDatosGrafica(this.value);
        chartPendientes.update();
    });

    $('#tablaProgramacionesHoy').DataTable(dtOptions);
});
