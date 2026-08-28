<script>
/* Las instancias de Chart.js viven FUERA del estado de Alpine: al guardarlas
   dentro, el Proxy rompe la identidad y `animator.remove(chart)` deja el
   gráfico destruido dentro del bucle de animación. */
let graficaPrincipal = null;
let graficaComparacion = null;

document.addEventListener('alpine:init', () => {
    Alpine.data('verProduccion', ({ corteId, meta, inspectores, urls }) => ({
        corteId, meta, urls,

        /* Producción del corte actual: [{nombres, contratos, cedula}] */
        base: inspectores,

        tab: 'principal',
        cortesSel: [],        // cortes superpuestos en la gráfica principal
        inspectoresSel: [],   // filtro de inspectores
        cortesComp: [],       // cortes de la pestaña de comparación
        cargando: false,
        cargandoComp: false,

        titulo: 'Inspecciones totales por inspector',
        visibles: [],         // lo que se está dibujando ahora

        /* Totales del corte actual, para la pestaña de comparación. */
        get totalCorteActual() {
            return this.base.reduce((a, i) => a + (Number(i.contratos) || 0), 0);
        },
        get inspectoresVisibles() { return this.visibles.length; },
        get totalVisible() {
            return this.visibles.reduce((a, i) => a + (Number(i.contratos) || 0), 0);
        },

        /* ------------------------------- Init -------------------------------- */
        init() {
            if (typeof Chart === 'undefined') return;
            Chart.register(ChartDataLabels);

            this.visibles = this.base;
            this.dibujarPrincipal();
            this.dibujarComparacion();

            // El tope de cortes depende de si hay inspectores filtrados: 1 cuando
            // se ven todos, 6 al filtrar. Era lo que el original hacía destruyendo
            // y recreando el TomSelect.
            this.$watch('inspectoresSel', () => {
                // Al bajar el tope hay que recortar lo que ya estaba seleccionado.
                if (this.cortesSel.length > this.topeCortes) {
                    this.cortesSel = this.cortesSel.slice(0, this.topeCortes);
                }
            });

            this.$watch('$store.ui.dark', () => {
                this.dibujarPrincipal();
                this.dibujarComparacion();
            });
        },

        cambiarTab(t) {
            this.tab = t;
            // El lienzo estaba oculto: Chart.js necesita recalcular al mostrarlo.
            this.$nextTick(() => {
                if (t === 'comparacion') graficaComparacion?.resize();
                else graficaPrincipal?.resize();
            });
        },

        /* Un solo corte mientras se ven todos los inspectores; hasta 7 al filtrar. */
        get topeCortes() { return this.inspectoresSel.length === 0 ? 1 : 7; },

        /* ------------------------------ Paleta ------------------------------- */
        paleta() {
            const oscuro = Alpine.store('ui').dark;
            return {
                texto:   oscuro ? '#94a3b8' : '#64748b',
                rejilla: oscuro ? 'rgba(148,163,184,0.15)' : 'rgba(100,116,139,0.12)',
                etiqueta: oscuro ? '#cbd5e1' : '#334155',
                /* En oscuro el azul de marca queda en 2.58:1 contra el fondo del
                   panel: casi no se separa. Se sube a #60a5fa (7.02:1), que iguala
                   el contraste que tiene #1f47e0 en modo claro (6.92:1). */
                barra:      oscuro ? '#60a5fa' : '#1f47e0',
                barraHover: oscuro ? '#93c5fd' : '#1a37b5',
                meta:       oscuro ? '#34d399' : '#10b981',
                series: oscuro
                    ? ['#60a5fa', '#34d399', '#fbbf24', '#c084fc', '#f472b6', '#38bdf8', '#fb7185', '#2dd4bf']
                    : ['#1f47e0', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#0ea5e9', '#f43f5e', '#14b8a6'],
            };
        },

        tooltip() {
            return { backgroundColor: '#1a1622', padding: 12, cornerRadius: 10, displayColors: false };
        },

        /* -------------------------- Gráfica principal ------------------------ */
        dibujarPrincipal() {
            const el = document.getElementById('inspeccionesDiarias');
            if (!el) return;

            const c = this.paleta();
            const datasetsPrevios = graficaPrincipal
                ? graficaPrincipal.data.datasets.slice(1)   // los cortes superpuestos
                : [];

            graficaPrincipal?.destroy();
            Chart.getChart(el)?.destroy();

            graficaPrincipal = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: this.visibles.map(i => i.nombres),
                    datasets: [
                        {
                            label: 'Corte actual',
                            data: this.visibles.map(i => Number(i.contratos) || 0),
                            backgroundColor: c.barra,
                            hoverBackgroundColor: c.barraHover,
                            borderRadius: 6,
                            maxBarThickness: 44,
                        },
                        ...datasetsPrevios,
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 24 } },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: c.texto, usePointStyle: true, pointStyle: 'circle',
                                      boxWidth: 8, boxHeight: 8, padding: 16 },
                        },
                        tooltip: {
                            ...this.tooltip(),
                            callbacks: {
                                title: (items) => items[0].dataset.label
                                    ? `${items[0].label} · ${items[0].dataset.label}`
                                    : items[0].label,
                                label: (item) => `Total inspecciones: ${item.raw}`,
                            },
                        },
                        datalabels: {
                            anchor: 'end', align: 'top',
                            formatter: (v) => (v ? Number(v).toLocaleString('es-CO') : ''),
                            font: { size: 10, weight: '600' },
                            color: c.etiqueta,
                            display: (ctx) => ctx.chart.data.labels.length <= 40,
                        },
                        annotation: this.meta > 0 ? {
                            annotations: {
                                meta: {
                                    type: 'line', scaleID: 'y', value: this.meta,
                                    borderColor: c.meta, borderWidth: 2, borderDash: [6, 6],
                                    label: { content: 'META', display: true, position: 'end',
                                             backgroundColor: c.meta, font: { size: 10, weight: 'bold' } },
                                },
                            },
                        } : {},
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            border: { display: false },
                            ticks: { color: c.texto, font: { size: 10 }, autoSkip: false,
                                     maxRotation: 90, minRotation: 45 },
                        },
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: c.rejilla },
                            title: { display: true, text: 'Cantidad de inspecciones', color: c.texto },
                            ticks: { color: c.texto, precision: 0, padding: 8 },
                        },
                    },
                },
            });
        },

        /* -------------------------- Acciones del usuario ---------------------- */
        async comparar() {
            // Mismas cuatro ramas que el botón original.
            if (this.inspectoresSel.length === 0 && this.cortesSel.length === 0) return;

            this.cargando = true;
            try {
                if (this.inspectoresSel.length > 0) {
                    this.filtrarInspectores();
                } else {
                    this.visibles = this.base;
                    this.titulo = 'Inspecciones totales por inspector';
                }

                // Se redibuja la base antes de superponer los cortes.
                graficaPrincipal?.destroy();
                graficaPrincipal = null;
                this.dibujarPrincipal();

                if (this.cortesSel.length > 0) {
                    await this.superponerCortes();
                }
            } catch (e) {
                console.error(e);
                window.Swal.fire('Error', 'Ocurrió un problema al cargar los datos.', 'error');
            } finally {
                this.cargando = false;
            }
        },

        filtrarInspectores() {
            const sel = this.inspectoresSel
                .map(cc => this.base.find(i => String(i.cedula) === String(cc)))
                .filter(Boolean);

            if (sel.length === 0) {
                window.Swal.fire({
                    title: 'Sin inspecciones',
                    text: 'El inspector seleccionado no tiene contratos asociados en uno o más cortes.',
                    icon: 'warning', confirmButtonText: 'Aceptar',
                });
                return;
            }

            this.visibles = sel;
            this.titulo = 'Inspecciones totales de ' + sel.map(i => i.nombres).join(', ');
        },

        /* Trae cada corte y lo añade como una serie más sobre la gráfica actual. */
        async superponerCortes() {
            const c = this.paleta();
            const sinDatos = [];

            const series = await Promise.all(this.cortesSel.map(async (id, indice) => {
                if (String(id) === String(this.corteId)) return null;   // no se compara consigo mismo

                try {
                    const data = await window.api(this.urls.corteData, {
                        method: 'POST',
                        body: { id, inspector_cc: this.inspectoresSel.length ? this.inspectoresSel : null },
                    });

                    if (data.error || data.message) { sinDatos.push(data.nombreCorte ?? id); return null; }

                    const porCedula = {};
                    for (const i of data.produccionInspector) porCedula[String(i.cedula)] = Number(i.contratos) || 0;

                    // Se alinea con las barras que se están mostrando, en su mismo orden.
                    const valores = this.visibles.map(i => porCedula[String(i.cedula)] ?? 0);

                    return {
                        label: data.nombreCorte,
                        data: valores,
                        backgroundColor: c.series[(indice + 1) % c.series.length],
                        borderRadius: 6,
                        maxBarThickness: 44,
                    };
                } catch (e) {
                    console.error(`Error al procesar el corte ${id}:`, e);
                    sinDatos.push(id);
                    return null;
                }
            }));

            const validas = series.filter(Boolean);
            graficaPrincipal.data.datasets = [graficaPrincipal.data.datasets[0], ...validas];
            graficaPrincipal.update();

            if (sinDatos.length > 0) {
                await window.Swal.fire({
                    title: 'Advertencia',
                    html: `Los siguientes cortes no tienen datos disponibles:<br><strong>${sinDatos.join(', ')}</strong>`,
                    icon: 'warning', confirmButtonText: 'Aceptar',
                });
            }
        },

        restaurar() {
            this.cortesSel = [];
            this.inspectoresSel = [];
            this.visibles = this.base;
            this.titulo = 'Inspecciones totales por inspector';
            graficaPrincipal?.destroy();
            graficaPrincipal = null;
            this.dibujarPrincipal();
        },

        /* ------------------------ Comparación de cortes ----------------------- */
        async compararCortes() {
            this.cargandoComp = true;
            try {
                let totales = {};
                if (this.cortesComp.length > 0) {
                    const data = await window.api(this.urls.totalData, {
                        method: 'POST',
                        body: { cortes: this.cortesComp },
                    });
                    if (data.error) throw new Error(data.error);

                    for (const id of this.cortesComp) {
                        const corte = data.find(c => Number(c.id) === Number(id));
                        if (corte) totales[corte.nombreCorte] = corte.totalContratos;
                    }
                }
                this.dibujarComparacion(totales);
            } catch (e) {
                console.error(e);
                window.Swal.fire('Error', 'No se pudo completar la solicitud.', 'error');
            } finally {
                this.cargandoComp = false;
            }
        },

        dibujarComparacion(totales = null) {
            const el = document.getElementById('comparacionInspecciones');
            if (!el) return;

            const c = this.paleta();
            // Sin argumento se conserva lo que ya estaba (repintado por cambio de tema).
            const datos = totales ?? this._totalesComparacion ?? {};
            this._totalesComparacion = datos;

            const nombres = Object.keys(datos);

            graficaComparacion?.destroy();
            Chart.getChart(el)?.destroy();

            graficaComparacion = new Chart(el.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: ['Corte actual', ...nombres],
                    datasets: [
                        {
                            label: 'Corte actual',
                            data: [this.totalCorteActual, ...new Array(nombres.length).fill(null)],
                            backgroundColor: c.series[0],
                            borderRadius: 6, maxBarThickness: 64,
                        },
                        ...nombres.map((n, i) => ({
                            label: n,
                            data: [null, ...nombres.map((_, j) => (j === i ? datos[n] : null))],
                            backgroundColor: c.series[(i + 1) % c.series.length],
                            borderRadius: 6, maxBarThickness: 64,
                        })),
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    layout: { padding: { top: 24 } },
                    plugins: {
                        legend: {
                            position: 'top',
                            labels: { color: c.texto, usePointStyle: true, pointStyle: 'circle',
                                      boxWidth: 8, boxHeight: 8, padding: 16 },
                        },
                        tooltip: {
                            ...this.tooltip(),
                            callbacks: {
                                title: (items) => items[0].dataset.label,
                                label: (item) => `Total inspecciones: ${Number(item.raw).toLocaleString('es-CO')}`,
                            },
                        },
                        datalabels: {
                            anchor: 'end', align: 'top',
                            formatter: (v) => (v ? Number(v).toLocaleString('es-CO') : ''),
                            font: { size: 12, weight: '600' },
                            color: c.etiqueta,
                        },
                    },
                    scales: {
                        x: { stacked: true, grid: { display: false }, border: { display: false },
                             ticks: { color: c.texto } },
                        y: { beginAtZero: true, border: { display: false },
                             grid: { color: c.rejilla },
                             title: { display: true, text: 'Cantidad de inspecciones', color: c.texto },
                             ticks: { color: c.texto, precision: 0, padding: 8 } },
                    },
                },
            });
        },
    }));
});
</script>
