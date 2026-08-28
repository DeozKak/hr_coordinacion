
let hot;

document.addEventListener('DOMContentLoaded', () => {

    registrarRenderer();
    construirTabla();
    cargarDatos();
    cargarIndicadores();

    // El listener se registra UNA sola vez. Antes vivía dentro de carga_datos(),
    // que se vuelve a llamar tras cada devolución: eso acumulaba un listener por
    // devolución y disparaba la petición varias veces.
    document.getElementById('devolucion').addEventListener('click', devolverSeleccionados);

    // ------------------------------------------------------------------

    function registrarRenderer() {
        Handsontable.renderers.registerRenderer('customStylesRenderer', (hotInstance, TD, row, col, prop, value, cellProperties) => {
            Handsontable.renderers.TextRenderer(hotInstance, TD, row, col, prop, value, cellProperties);
            const columnName = hotInstance.getColHeader(col);

            if (value === 'PERIODO DE GRACIA') {
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i);
                    cellProperties.className = 'fila-gracia';
                }
            }

            if (value === '60 meses') {
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i);
                    cellProperties.className = 'fila-60-meses';
                }
            } else if (columnName === 'N° ACTA' && /^P/i.test(value)) {
                for (let i = 0; i < hotInstance.countCols(); i++) {
                    cellProperties = hotInstance.getCellMeta(row, i);
                    cellProperties.className = 'fila-acta-p';
                }
            } else if (value === 'SI' || value < '00:20' || value === 'COMERCIAL') {
                cellProperties.className = 'celda-amarilla';
            }

            return cellProperties;
        });
    }

    function construirTabla() {
        const container = document.querySelector('#tabla');

        // Si ya existe, se destruye antes de recrear: dos instancias sobre el
        // mismo contenedor dejaban la tabla duplicada.
        if (hot) hot.destroy();

        hot = new Handsontable(container, {
            language: 'es-MX',
            readOnly: true,
            manualColumnMove: false,
            rowHeaders: true,
            height: '550px',
            contextMenu: true,
            autoWrapRow: true,
            autoWrapCol: true,
            manualRowResize: true,
            stretchH: 'all',
            autoColumnSize: { useHeaders: true },
            columns: [
                {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, { renderer: 'customStylesRenderer' }, {}, {}, {}, { renderer: 'customStylesRenderer' }, { renderer: 'customStylesRenderer' }, {},
                { type: 'checkbox', checkedTemplate: true, uncheckedTemplate: false, readOnly: false, width: 90, className: 'col-seleccion' },
            ],
            filters: true,
            dropdownMenu: true,
            colHeaders: ['id', 'OBSERVACIÓN', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA', 'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA', 'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP', 'Causal rechazo', 'DEVOLVER'],
            licenseKey: 'non-commercial-and-evaluation',
            hiddenColumns: {
                columns: [0],
            },
            copyPaste: {
                copyColumnGroupHeaders: true,
                copyColumnHeaders: true,
            },
        });

        // Entra en el congelado global: mientras el menú lateral anima su ancho,
        // HOT deja de redibujarse (si no, la animación tartamudea).
        window.registrarHot?.(hot);
    }

    async function cargarDatos() {
        const urlContratos = document.querySelector('#id_bitacora').value;
        try {
            const respuesta = await window.api(urlContratos);
            console.log(respuesta);
            hot.loadData(convertirJSONaArray2D(respuesta.contratos));
        } catch (e) {
            console.error(e);
            errorDeCarga();
        }
    }

    async function cargarIndicadores() {
        const contenedor = document.querySelector('#indicadores');
        const urlIndicadores = document.querySelector('#url_indicadores').value;

        try {
            const r = await window.api(urlIndicadores);

            // Cinco cifras estáticas no necesitan una grilla de datos:
            // se pintan como tarjetas del sistema de diseño.
            const tarjetas = [
                { etiqueta: 'Certificada',              valor: r.certificadas,                       icono: 'fa-circle-check',        tinte: 'emerald' },
                { etiqueta: 'Certificada con novedades', valor: r.certificadasConNovedades,          icono: 'fa-circle-exclamation',  tinte: 'amber'   },
                { etiqueta: 'Defecto crítico',          valor: r.inspeccionadasConDefectoCritico,    icono: 'fa-triangle-exclamation', tinte: 'rose'   },
                { etiqueta: 'Defecto no crítico',       valor: r.inspeccionadasConDefectoNoCritico,  icono: 'fa-circle-info',         tinte: 'sky'     },
                { etiqueta: 'Total',                    valor: r.totalContratosOK,                   icono: 'fa-list-check',          tinte: 'blue'    },
            ];

            contenedor.innerHTML = tarjetas.map(t => `
                <div class="tw-card p-5">
                    <div class="flex items-start justify-between gap-3">
                        <span class="tw-eyebrow max-w-[9rem]">${t.etiqueta}</span>
                        <span class="tw-chip chip-${t.tinte}"><i class="fas ${t.icono}"></i></span>
                    </div>
                    <p class="tw-metric mt-4">${Number(t.valor ?? 0).toLocaleString('es-CO')}</p>
                </div>
            `).join('');
        } catch (e) {
            console.error(e);
            contenedor.innerHTML = '';
            errorDeCarga();
        }
    }

    function errorDeCarga() {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Ocurrió un error al cargar los datos de la base de datos',
        });
    }

    async function devolverSeleccionados() {
        const seleccionadas = getValuesFromSelectedRows();

        if (seleccionadas.length === 0) {
            Swal.fire({
                icon: 'info',
                title: 'Sin selección',
                text: 'No se han seleccionado contratos para devolver',
            });
            return;
        }

        const ids = seleccionadas.map(fila => fila[0]);
        const url = document.getElementById('url_devolucion').value.replace(':id', ids.join(','));

        const opciones = causales
            .map(c => `<option value="${c.nom_causal}">${c.nom_causal}</option>`)
            .join('');

        const resultado = await Swal.fire({
            title: 'Selecciona una causal de devolución',
            html: `<select id="causalSelect" class="swal2-select" style="width:100%;display:block;">${opciones}</select>`,
            showCancelButton: true,
            confirmButtonText: 'Devolver',
            cancelButtonText: 'Cancelar',
            preConfirm: () => {
                const valor = document.getElementById('causalSelect').value;
                if (!valor || valor === '--SELECCIONE CAUSAL--') {
                    Swal.showValidationMessage('Por favor, selecciona una causal válida.');
                    return false;
                }
                return valor;
            },
        });

        if (!resultado.value) return;

        try {
            await window.api(url, {
                method: 'POST',
                body: { causal: resultado.value },
            });

            await Swal.fire({
                icon: 'success',
                title: 'Devolución exitosa',
                text: 'Los contratos seleccionados han sido devueltos correctamente',
            });

            // Se recargan datos e indicadores; el listener NO se vuelve a registrar.
            construirTabla();
            await cargarDatos();
            await cargarIndicadores();
        } catch (e) {
            console.error(e);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Ocurrió un error al devolver los contratos seleccionados',
            });
        }
    }
});

// Función para obtener los valores de las filas seleccionadas
function getValuesFromSelectedRows() {
    const selectedRows = [];
    hot.getData().forEach((row, rowIndex) => {

        if (hot.getDataAtCell(rowIndex, 17) === true) {
            const newRow = [...row];

            for (let columnIndex = 0; columnIndex < row.length; columnIndex++) {
                const cellProperties = hot.getCellMeta(rowIndex, columnIndex);
                if (cellProperties.type === "dropdown") {
                    newRow[columnIndex] = hot.getDataAtCell(rowIndex, columnIndex);
                }
            }
            selectedRows.push(newRow);
        }
    });
    return selectedRows;
}

function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = ['id', 'vence', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA', 'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA', 'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', 'CAUSAL_RECHAZO'];

    return Object.keys(jsonData ?? {}).map(key => {
        const fila = jsonData[key];
        return columnasDeseadas.map(columna => fila[columna]);
    });
}
