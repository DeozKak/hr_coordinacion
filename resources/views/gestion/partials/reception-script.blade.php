<script>
/* La instancia de Handsontable vive fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotRecepcion = null;

const POR_PAGINA = 100;   // lo que devuelve el servidor por página

document.addEventListener('alpine:init', () => {
    Alpine.data('recepcion', ({ urls }) => ({
        urls,

        filtrosAbiertos: true,
        cargando: false,
        pagina: 1,
        total: 0,

        /* Cada campo con el nombre que espera el servidor: filterData() usa las
           claves de este objeto como columnas de la consulta. */
        filtros: {
            ordenTrabajo: [],
            ordenExterna: [],
            numeroSolicitud: [],
            contrato: [],
            numActa: [],
            direccion: '',
            ccOperario: [],
            tipo: '',
            estadoRecepcion: '',
            created_at: '',
        },

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.construirTabla();
            this.$watch('$store.ui.dark', () => hotRecepcion?.render());
            this.cargar();
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.total / POR_PAGINA)); },

        get hayFiltro() {
            return Object.values(this.filtros)
                .some(v => Array.isArray(v) ? v.length > 0 : String(v).trim() !== '');
        },

        /* ------------------------------- Tabla ------------------------------- */
        construirTabla() {
            hotRecepcion = new Handsontable(document.getElementById('tablaRecepcion'), {
                data: [],
                colHeaders: ['#', 'ORDEN PRINCIPAL', 'ORDEN SECUNDARIA', 'NÚMERO SOLICITUD', 'CONTRATO',
                             'DIRECCIÓN', 'CÓDIGO TÉCNICO', 'TIPO', 'ESTADO RECEPCIÓN',
                             'FECHA RECEPCIÓN', 'ACTA'],
                rowHeaders: false,
                readOnly: true,
                height: '60vh',
                rowHeights: 26,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                stretchH: 'all',
                manualColumnResize: true,
                manualRowResize: true,
                licenseKey: 'non-commercial-and-evaluation',
            });
            window.registrarHot?.(hotRecepcion);
        },

        /* ------------------------------ Consulta ----------------------------- */
        /* El servidor distingue por tipo: los campos de varios números viajan
           como una cadena separada por comas (los parte con explode), el técnico
           como arreglo (lo recorre con foreach) y el resto como texto suelto. */
        parametros() {
            const p = new URLSearchParams();
            p.set('pagina', this.pagina);

            for (const [clave, valor] of Object.entries(this.filtros)) {
                if (Array.isArray(valor)) {
                    if (!valor.length) continue;
                    if (clave === 'ccOperario') {
                        for (const v of valor) p.append(`datosFormulario[${clave}][]`, v);
                    } else {
                        p.set(`datosFormulario[${clave}]`, valor.join(','));
                    }
                } else if (String(valor).trim() !== '') {
                    p.set(`datosFormulario[${clave}]`, String(valor).trim());
                }
            }
            return p;
        },

        async cargar() {
            this.cargando = true;
            try {
                // Sin filtros se pide el listado completo, como hacía la versión anterior.
                const base = this.hayFiltro ? this.urls.filtrar : this.urls.todo;
                const r = await window.api(`${base}?${this.parametros().toString()}`);

                const filas = r.data ?? [];
                this.total = Number(r.totalResults) || 0;
                hotRecepcion.loadData(filas);
                hotRecepcion.render();

                if (this.hayFiltro && filas.length === 0) {
                    window.Swal.fire({ icon: 'warning', title: 'Advertencia',
                                       text: 'No se encontraron datos con los filtros seleccionados' });
                }
            } catch (e) {
                console.error('Error al consultar recepción:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudieron obtener los datos de recepción.' });
            } finally {
                this.cargando = false;
            }
        },

        buscar() {
            // Un filtro nuevo empieza por la primera página, no por donde se quedó.
            this.pagina = 1;
            return this.cargar();
        },

        irA(pagina) {
            if (pagina < 1 || pagina > this.totalPaginas || this.cargando) return;
            this.pagina = pagina;
            return this.cargar();
        },

        limpiar() {
            this.filtros = {
                ordenTrabajo: [], ordenExterna: [], numeroSolicitud: [], contrato: [], numActa: [],
                direccion: '', ccOperario: [], tipo: '', estadoRecepcion: '', created_at: '',
            };
            return this.buscar();
        },
    }));
});
</script>
