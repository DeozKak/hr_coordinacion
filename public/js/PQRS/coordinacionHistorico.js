let hotHistorico;

document.addEventListener("DOMContentLoaded", function () {
    const openHistoricoBtn = document.getElementById('openHistoricoBtn');
    const historicoModalEl = document.getElementById('historicoModal');
    const formHistorico = document.getElementById('formHistorico');
    const containerHistorico = document.getElementById('tabla_historico');

    if (openHistoricoBtn && historicoModalEl) {
        const historicoModal = new bootstrap.Modal(historicoModalEl);

        // Abrir modal
        openHistoricoBtn.addEventListener('click', function () {
            historicoModal.show();
            // Refrescar el layout de la tabla si ya estaba dibujada al abrir el modal
            setTimeout(() => {
                if (hotHistorico) hotHistorico.render();
            }, 300);
        });

        // Enviar formulario de búsqueda
        formHistorico.addEventListener('submit', function (e) {
            e.preventDefault();

            const url = document.getElementById('url_get_historico').value;

            // Recoger los datos del formulario
            const params = new URLSearchParams(new FormData(formHistorico));

            // Si todos los campos están vacíos, advertir
            let isEmpty = true;
            for (let [key, value] of params.entries()) {
                if (value.trim() !== '') isEmpty = false;
            }

            if (isEmpty) {
                if(typeof Swal !== 'undefined'){
                    Swal.fire('Atención', 'Debe llenar al menos un criterio de búsqueda', 'warning');
                } else {
                    alert('Debe llenar al menos un criterio de búsqueda');
                }
                return;
            }

            // Mostrar carga (opcional)
            containerHistorico.innerHTML = '<div class="text-center mt-5"><div class="spinner-border text-primary" role="status"></div></div>';
            if (hotHistorico) {
                hotHistorico.destroy();
                hotHistorico = null;
            }

            // Petición al servidor
            fetch(`${url}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    containerHistorico.innerHTML = ''; // Limpiar spinner

                    if (data.success && data.data.length > 0) {
                        inicializarTablaHistorico(data.data, containerHistorico);
                    } else {
                        containerHistorico.innerHTML = '<div class="alert alert-info mt-3">No se encontraron registros gestionados con los criterios ingresados.</div>';
                    }
                })
                .catch(error => {
                    console.error('Error fetching historico:', error);
                    containerHistorico.innerHTML = '<div class="alert alert-danger mt-3">Ocurrió un error al consultar el histórico.</div>';
                });
        });
    }


    // --- LÓGICA DE EXPORTACIÓN A EXCEL (.XLSX) DESDE HOT ---
    if (btnExportarHistorico) {
        btnExportarHistorico.addEventListener('click', function() {
            if (!hotHistorico) {
                alert("No hay datos cargados para exportar.");
                return;
            }

            const btn = this;
            const urlExport = document.getElementById('url_export_historico_excel').value;
            const token = document.querySelector('input[name="_token"]').value;

            // 1. Extraer los datos EXACTOS que se están viendo en la tabla (respeta filtros y orden)
            const rowCount = hotHistorico.countRows();
            const colCount = hotHistorico.countCols();
            const tableData = [];

            for (let r = 0; r < rowCount; r++) {
                const rowArray = [];
                for (let c = 0; c < colCount; c++) {
                    // getDataAtCell obtiene el dato visual actual
                    rowArray.push(hotHistorico.getDataAtCell(r, c));
                }
                tableData.push(rowArray);
            }

            if (tableData.length === 0) {
                alert("La tabla está vacía después de aplicar los filtros.");
                return;
            }

            // Bloquear botón
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generando Excel...';

            // 2. Enviar los datos al backend
            fetch(urlExport, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({ datos_tabla: tableData })
            })
                .then(async response => {
                    const data = await response.json();
                    if (!response.ok) {
                        throw new Error(data.error || 'Error al exportar');
                    }
                    return data;
                })
                .then(data => {
                    if (data.downloadUrl) {
                        const link = document.createElement('a');
                        link.href = data.downloadUrl;
                        link.download = '';
                        document.body.appendChild(link);
                        link.click();
                        document.body.removeChild(link);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("No se pudo exportar: " + error.message);
                })
                .finally(() => {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-file-excel"></i> Exportar Resultados';
                });
        });
    }

});

function inicializarTablaHistorico(datosDB, container) {

    // Mismas cabeceras base, pero añadiendo las de legalización al final
    const colHeadersHistorico = [
        'NÚMERO ORDEN', 'CONTRATO', 'CÉDULA', 'NOMBRE', 'DEPARTAMENTO',
        'LOCALIDAD', 'BARRIO', 'DIRECCIÓN', 'CATEGORÍA',
        'COD UNIDAD OPERATIVA', 'TIPO TRABAJO', 'FECHA ASIGNACIÓN',
        'OBSERVACIÓN SOLICITUD', 'FECHA CIERRE ÚLTIMA', 'OBSERVACIÓN CIERRE ÚLTIMA',
        'TIPO TRABAJO CIERRE ÚLTIMA', 'CAUSAL CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA',
        'OBSERVACIÓN ASIGNACIÓN ÚLTIMA', 'GESTIÓN ASIGNACIÓN ÚLTIMA', 'TIPO TRABAJO ASIGNACIÓN ÚLTIMA',
        'RESPONSABLE', 'ASIGNADO','SUPERVISOR' ,'FECHA ASIGNADO','RECEPCIÓN',
        'FECHA RECEPCIÓN', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
        // Columnas exclusivas del histórico
        'FECHA LEGALIZACIÓN', 'CAUSAL LEGALIZACIÓN', 'OBSERVACIÓN LEGALIZACIÓN'
    ];

    // Mapeo de datos para el histórico
    const tableDataHistorico = datosDB.map(row => [
        row.NUMERO_ORDEN, row.CONTRATO, row.CEDULA, row.NOMBRE, row.DESC_DEPART,
        row.DESC_LOCALIDAD, row.BARRIO, row.DIRECCION, row.DESC_CATEGORIA,
        row.COD_UNIDAD_OPER, row.DESC_TIPO_TRABAJO, row.FECHA_ASIGNACION,
        row.OBSERVACION_SOLICITUD, row.FECHA_CIERRE_ULTIMA, row.OBSERVACIÓN_CIERRE_ULTIMA,
        row.TIPO_TRABAJO_CIERRE_ULTIMA, row.DESC_CAUSAL_CIERRE_ULTIMA, row.FECHA_ASIGNACIÓN_ULTIMA,
        row.OBSERVACIÓN_ASIGNACIÓN_ULTIMA, row.GESTIÓN_ASIGNACIÓN_ULTIMA, row.TIPO_TRABAJO_ASIGNACIÓN_ULTIMA,
        row.RESPONSABLE, row.ASIGNADO, row.SUPERVISOR, row.FECHA_ASIGNADO, row.RECEPCION,
        row.FECHA_RECEPCION, row.OBSERVACION_GESTION, row.CODIGO_AUTORIZACION, row.FECHA_RESPUESTA,
        // Columnas exclusivas
        row.FECHA_LEGALIZACION, row.DESC_CAUSAL_LEGALIZACION, row.OBSERVACION_LEGALIZACION
    ]);

    // Todas las columnas son de solo lectura en el histórico
    const columnsConfigHistorico = colHeadersHistorico.map(() => ({ readOnly: true }));

    hotHistorico = new Handsontable(container, {
        data: tableDataHistorico,
        colHeaders: colHeadersHistorico,
        columns: columnsConfigHistorico,
        rowHeaders: true,
        width: '100%',
        height: '400px',
        filters: true,
        dropdownMenu: true,
        manualColumnResize: true,
        autoWrapRow: false,
        autoWrapCol: false,
        wordWrap: false,
        colWidths: 150,
        fixedColumnsLeft: 2,

        // --- APLICAR RENDERIZADOR PERSONALIZADO PARA TEXTOS LARGOS ---
        cells: function (row, col) {
            const headerName = colHeadersHistorico[col];

            // Excluimos columnas que normalmente no tienen textos largos
            // o donde no nos interesa que se dibuje el ojo
            const camposExcluidosIcono = [
                'CONTRATO', 'CÉDULA', 'NÚMERO ORDEN', 'FECHA ASIGNACIÓN',
                'FECHA CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA', 'FECHA ASIGNADO',
                'FECHA RECEPCIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                'FECHA LEGALIZACIÓN'
            ];

            if (!camposExcluidosIcono.includes(headerName)) {
                return { renderer: 'verMasRenderer' };
            }
            return {};
        },

        licenseKey: "non-commercial-and-evaluation",
        afterGetColHeader: function (col, TH) {
            TH.style.backgroundColor = "#8064A2"; // Diferente color para diferenciar que es histórico
            TH.style.color = "white";
            TH.style.fontWeight = "bold";
        }
    });
}
