<script>
/* Las instancias de Handsontable viven fuera del estado de Alpine: dentro, el
   Proxy rompe su identidad y el gráfico destruido se queda en el animador. */
let hotFallidas = null;
let hotFallidasDia = null;

document.addEventListener('alpine:init', () => {
    Alpine.data('detallesFallidas', ({ urls }) => ({
        urls,

        cargando: true,
        modal: null,

        fechas: [],            // [{dia, fecha}]
        totalDias: 0,

        tituloDia: '',
        sinDatosDia: false,
        filasDia: [],
        seleccion: { fecha: null, cc: null, nombreDia: '', nombreCompleto: '' },

        /* ------------------------------- Init -------------------------------- */
        async init() {
            await this.cargarDatos();

            this.$watch('$store.ui.dark', () => {
                hotFallidas?.render();
                hotFallidasDia?.render();
            });

            // Al volver de otra ventana el contenedor estuvo en display:none y HOT
            // conserva medidas viejas; hay que remedir cuando vuelve a verse.
            this.$watch('modal', (v) => {
                if (v !== 'dia' || !hotFallidasDia) return;
                const remedir = () => { hotFallidasDia?.refreshDimensions(); hotFallidasDia?.render(); };
                this.$nextTick(remedir);
                setTimeout(remedir, 260);
            });
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Clave semántica de la celda; el color sale de la paleta según el tema.
           Se separa el significado del color para que la paleta pueda cambiar. */
        claveCelda(instancia, row, col) {
            if (col === 0 || col === 1) return null;

            const columna = instancia.getColHeader(col);
            const ultima = instancia.countRows() - 1;

            if (row === ultima) return 'total';          // fila de TOTAL al pie
            if (columna === 'TOTAL') return 'total';
            if (columna === 'META POR INSPECTOR' || columna === 'DIAS LABORADOS') return null;
            return 'dia';
        },

        paletaCeldas() {
            const oscuro = Alpine.store('ui').dark;
            return oscuro
                ? { dia: ['#1e3a5f', '#dbeafe'], total: ['#4a3a1a', '#fde68a'] }
                : { dia: ['rgb(215, 232, 255)', '#1e293b'], total: ['rgb(250, 243, 152)', '#1e293b'] };
        },

        registrarRenderers() {
            const self = this;

            /* handsontable.css marca toda celda readOnly con .htDimmed y le impone
               fondo y color con !important: por eso el color va con prioridad. */
            Handsontable.renderers.registerRenderer('fallidasRenderer',
                function (instancia, TD, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer(instancia, TD, row, col, prop, value, cellProperties);

                    // Al no partirse, un nombre largo se recorta: se deja el
                    // completo en el tooltip.
                    if (col === 1) TD.title = value ?? '';

                    TD.style.removeProperty('background-color');
                    const clave = self.claveCelda(instancia, row, col);
                    if (!clave) return;

                    const [fondo, texto] = self.paletaCeldas()[clave];
                    TD.style.setProperty('background-color', fondo, 'important');
                    TD.style.setProperty('color', texto, 'important');
                });

            Handsontable.validators.registerValidator('custom.numeric',
                (v, cb) => cb(!isNaN(Number(v)) && v !== null && v !== ''));
            Handsontable.validators.registerValidator('custom.text',
                (v, cb) => cb(v !== '' && (v === 'NO' || !isNaN(Number(v)))));
        },

        /* --------------------------- Carga principal -------------------------- */
        async cargarDatos() {
            this.cargando = true;
            try {
                const r = await window.api(this.urls.datos);
                if (r.error) { window.Swal.fire({ icon: 'warning', text: r.error }); return; }

                this.fechas = r.diasIntermedios.map((item, i) => ({
                    dia: `${item.nombreDia} ${item.dias}`,
                    fecha: r.fechasIntermedias[i],
                }));

                this.registrarRenderers();
                this.construirTabla(r);
            } catch (e) {
                console.error('Error fetching data:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Ocurrió un error al cargar los datos de la base de datos' });
            } finally {
                this.cargando = false;
            }
        },

        /* Cabeceras agrupadas por mes + CC/INSPECTOR delante y TOTAL al final. */
        armarCabeceras(r) {
            const meses = r.diasIntermedios.map(i => i.nombreMes);
            const unicos = [...new Set(meses)];
            const conteo = r.diasIntermedios.reduce((acc, i) => {
                acc[i.nombreMes] = (acc[i.nombreMes] || 0) + 1; return acc;
            }, {});
            const grupos = unicos.map(m => ({ label: m, colspan: conteo[m] }));

            this.totalDias = grupos.reduce((a, g) => a + g.colspan, 0);

            return {
                superior: [{ label: '', colspan: 2 }, ...grupos],
                inferior: ['CC', 'INSPECTORES CONTRATO CALI',
                    ...r.diasIntermedios.map(i => `${i.nombreDia} ${i.dias}`), 'TOTAL'],
            };
        },

        construirTabla(r) {
            const contenedor = document.getElementById('detalles');
            if (!contenedor) return;

            const { superior, inferior } = this.armarCabeceras(r);

            hotFallidas?.destroy();
            hotFallidas = new Handsontable(contenedor, {
                readOnly: true,
                manualColumnMove: false,
                rowHeaders: true,
                nestedHeaders: [superior, inferior],
                data: r.produccionInspector,
                autoWrapRow: true,
                autoWrapCol: true,
                /* Sin esto los nombres largos parten en dos líneas y esa fila crece:
                   las columnas congeladas y el cuerpo se calculan por separado y
                   dejaban de cuadrar. Una línea por fila y alto uniforme. */
                wordWrap: false,
                rowHeights: 24,
                fixedColumnsStart: 2,
                dropdownMenu: true,
                filters: true,
                /* +40px sobre el alto útil para que la barra horizontal no se
                   coma la última fila (la de TOTAL). */
                height: '660px',
                colWidths: (i) => (i === 0 ? 110 : i === 1 ? 320 : undefined),
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                cells: () => ({ renderer: 'fallidasRenderer' }),
                afterOnCellCornerDblClick: () => this.abrirDia(),
            });
            window.registrarHot?.(hotFallidas);

            this.agregarTotales();
        },

        /* Fila de sumas al pie, igual que antes. */
        agregarTotales() {
            hotFallidas.setDataAtCell(hotFallidas.countRows(), 1, 'TOTAL');

            const columnas = this.totalDias + 1;      // los días más la columna TOTAL
            const sumas = [];
            for (let i = 0; i < columnas; i++) {
                sumas.push({
                    destinationRow: hotFallidas.countRows() - 1,
                    destinationColumn: i + 2, sourceColumn: i + 2, type: 'sum',
                });
            }
            hotFallidas.updateSettings({ columnSummary: sumas });
        },

        exportar() {
            hotFallidas?.getPlugin('exportFile').downloadFile('csv', { filename: 'fallidas' });
        },

        /* ---------------------------- Modal del día --------------------------- */
        async abrirDia() {
            const sel = hotFallidas.getSelectedLast();
            if (!sel) return;
            const [row, col] = sel;
            const columna = hotFallidas.getColHeader(col);
            const fecha = this.fechas.find(f => f.dia === columna);
            if (!fecha) return;                       // solo las columnas de día abren detalle

            this.seleccion = {
                fecha: fecha.fecha,
                cc: hotFallidas.getDataAtCell(row, 0),
                nombreDia: columna,
                nombreCompleto: hotFallidas.getDataAtCell(row, 1),
            };
            this.tituloDia = `Fallidas del día ${columna} — ${this.seleccion.nombreCompleto}`;
            this.modal = 'dia';

            await this.$nextTick();
            await this.cargarDia();
        },

        cerrarDia() {
            hotFallidasDia?.destroy();
            hotFallidasDia = null;
            this.modal = null;
        },

        async cargarDia() {
            const { fecha, cc } = this.seleccion;
            this.cargando = true;
            try {
                // El servidor devuelve primero la URL con la que se piden las filas.
                const urlDetalles = await (await fetch(
                    `${this.urls.obtenerDetalles}?fecha=${fecha}&cc_inspector=${cc}`)).text();
                const r = await window.api(urlDetalles);

                this.filasDia = this.aFilas(r);
                this.sinDatosDia = this.filasDia.length === 0;
                this.construirTablaDia();
            } catch (e) {
                console.error('Error fetching data:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Ocurrió un error al cargar las fallidas del día' });
            } finally {
                this.cargando = false;
            }
        },

        /* Las 12 columnas que devuelve detallesDiario, en su mismo orden. */
        aFilas(json) {
            const columnas = ['id', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA',
                'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT',
                'CATEGORIA', 'RESULTADO_CIERRE'];
            return Object.keys(json ?? {}).map(k => columnas.map(c => json[k][c]));
        },

        construirTablaDia() {
            const contenedor = document.getElementById('contratos_dia');
            if (!contenedor) return;

            hotFallidasDia?.destroy();
            hotFallidasDia = new Handsontable(contenedor, {
                data: this.filasDia,
                readOnly: true,
                manualColumnMove: false,
                rowHeaders: false,
                colHeaders: ['ID', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA',
                    'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA',
                    'RESULTADO CIERRE'],
                columns: [
                    { type: 'numeric' },
                    { type: 'text' },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'text' },
                    { type: 'text' },
                    { type: 'text' },
                    { editor: 'select', selectOptions: ['RP 10444', 'RP 12161', 'RN 12162', 'SA 12164', 'SA 12163'] },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'numeric', correctFormat: true },
                    { editor: 'select', selectOptions: ['RESIDENCIAL', 'COMERCIAL'] },
                    { editor: 'select', selectOptions: ['CERTIFICADA', 'CERTIFICADA CON NOVEDADES',
                        'INSPECCIONADA CON DEFECTO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'] },
                ],
                manualRowResize: true,
                autoWrapRow: true,
                autoWrapCol: true,
                licenseKey: 'non-commercial-and-evaluation',
                height: '460px',
                rowHeights: 30,
                hiddenColumns: { columns: [0], indicators: false },

                /* Resaltado por fila en vez de mutar el meta durante el render.
                   Las clases son las del layout, con su variante oscura. */
                cells: (row) => {
                    const f = this.filasDia[row];
                    if (f && /^P/i.test(String(f[5] ?? ''))) return { className: 'fila-acta-p' };
                    return {};
                },
            });
            window.registrarHot?.(hotFallidasDia);
        },
    }));
});
</script>
