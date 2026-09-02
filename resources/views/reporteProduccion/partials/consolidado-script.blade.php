<script>
/* Las instancias de Handsontable viven fuera del estado de Alpine: envolverlas
   en el proxy reactivo rompe la identidad que la propia librería usa. */
let hotConsolidado = null;
let hotPrevias = null;
let hotZona = null;

const DIAS_HABILES = 24;   // divisor de PROMEDIO DIARIO UND
const DIAS_MES = 30;       // divisor de PROMEDIO DIARIO $

const ZONAS = [
    'RESIDENCIAL METROPOLITANA', 'RESIDENCIAL NORTE DEL VALLE', 'RESIDENCIAL CAUCA/BUENAVENTURA',
    'COMERCIAL METROPOLITANA', 'COMERCIAL NORTE DEL VALLE', 'COMERCIAL CAUCA/BUENAVENTURA',
];

document.addEventListener('alpine:init', () => {
    Alpine.data('reporteConsolidado', ({ urls, sufijos }) => ({
        urls, sufijos,

        anio: '',
        mes: '',
        cargando: false,
        hayDatos: false,
        hayMes: false,

        moneda: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0,
        }),

        cabecerasConsolidado: [
            'Meses',
            'INSPECCION RP/AS/NV<br>RESIDENCIAL', 'INSPECCION RP/AS/NV<br>COMERCIAL',
            'INSPECCION<br>INDUSTRIAL', 'TOTAL', 'N° INSPECTORES', 'VALOR',
            'PROMEDIO<br>DIARIO UND', 'PROMEDIO<br>POR INSPECTOR UND',
            'PROMEDIO<br>DIARIO $', 'PROMEDIO<br>POR INSPECTOR $',
            'META E&C', '% CUMPL', 'META GDO', '% CUMPL',
        ],
        cabecerasPrevias: ['Meses', 'RP', 'PREVIAS', 'TOTAL', '% RP', '% PREVIAS'],
        cabecerasZona: ['ZONA', 'N° INSPECCIONES', '% INSPECCIONES', 'INSPECCIONES $', '% INSPECCIONES $'],

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.construirTablas();
            this.$watch('$store.ui.dark', () => {
                hotConsolidado?.render();
                hotPrevias?.render();
                hotZona?.render();
            });
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Antes los colores vivían en un CSS aparte con !important y sin
           equivalente oscuro. Ahora son tintes de la paleta de la aplicación:
           claros sobre el tema claro y profundos sobre el oscuro, donde además
           han de separarse del fondo de la rejilla (#1e293b) y del secundario. */
        paleta() {
            return Alpine.store('ui').dark ? {
                mes: '#475569', metaEyc: '#c2410c', metaGdo: '#1d4ed8',
                rp: '#0369a1', previas: '#a16207',
                residencial: '#1d4ed8', comercial: '#0f766e', totales: '#64748b',
            } : {
                mes: '#cbd5e1', metaEyc: '#fed7aa', metaGdo: '#bfdbfe',
                rp: '#bae6fd', previas: '#fef08a',
                residencial: '#bfdbfe', comercial: '#99f6e4', totales: '#334155',
            };
        },

        /* El color del texto se elige por contraste en lugar de fijarlo. El CSS
           anterior escribía blanco o negro a mano por color, y así cualquier
           cambio de tono obligaba a revisarlo; aquí se comparan las dos razones
           WCAG y gana la mayor, en los dos temas. */
        luminancia(hex) {
            const c = [1, 3, 5].map(i => parseInt(hex.slice(i, i + 2), 16) / 255)
                .map(v => (v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)));
            return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        },
        razon(a, b) {
            const [x, y] = [this.luminancia(a), this.luminancia(b)].sort((p, q) => q - p);
            return (x + 0.05) / (y + 0.05);
        },
        contraste(fondo) {
            return this.razon('#ffffff', fondo) >= this.razon('#0f172a', fondo)
                ? '#ffffff' : '#0f172a';
        },

        pintar(td, clave) {
            const fondo = this.paleta()[clave];
            if (!fondo) return;
            // Con prioridad: las rejillas son readOnly y .htDimmed impone fondo
            // y color desde la hoja de estilos de Handsontable.
            td.style.setProperty('background-color', fondo, 'important');
            td.style.setProperty('color', this.contraste(fondo), 'important');
        },

        /* Clave semántica de cada celda; un solo sitio por tabla. */
        claveConsolidado(row, col, ultima) {
            if (row === ultima) return 'totales';
            if (col === 0) return 'mes';
            if (col === 11) return 'metaEyc';
            if (col === 13) return 'metaGdo';
            return null;
        },
        clavePrevias(row, col) {
            if (col === 0) return 'mes';
            if (col === 1) return 'rp';
            if (col === 2) return 'previas';
            return null;
        },
        claveZona(row, col, ultima) {
            if (row === ultima) return 'totales';
            if (col !== 0) return null;
            return row < 3 ? 'residencial' : 'comercial';
        },

        /* ------------------------------ Tablas ------------------------------- */
        construirTablas() {
            const self = this;

            // Renderizador común: limpia el fondo heredado y aplica la clave.
            const renderizador = (clave) => function (instancia, td, r, c, prop, value, cellProperties) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);
                td.style.removeProperty('background-color');
                self.pintar(td, clave(instancia, r, c));
            };

            const comunes = {
                height: 'auto',
                rowHeights: 24,
                wordWrap: false,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                readOnly: true,
                // Sin virtualización vertical: con 'auto' Handsontable decide
                // cuántas filas pinta según el viewport, y estas tarjetas nacen
                // ocultas y quedan por debajo del pliegue, así que la cuenta
                // salía corta. Son 13, 12 y 7 filas: pintarlas todas no cuesta.
                renderAllRows: true,
            };

            hotConsolidado = new Handsontable(document.getElementById('reporteConsolidado'), {
                ...comunes,
                data: [],
                colHeaders: this.cabecerasConsolidado,
                cells(row, col) {
                    const ultima = this.instance.countRows() - 1;
                    return {
                        // Solo las dos metas son editables, y no en la fila TOTAL.
                        readOnly: !((col === 11 || col === 13) && row !== ultima),
                        renderer: renderizador((instancia, r, c) =>
                            self.claveConsolidado(r, c, instancia.countRows() - 1)),
                    };
                },
                afterChange: (cambios, origen) => this.alEditarMeta(cambios, origen),
            });
            window.registrarHot?.(hotConsolidado);

            hotPrevias = new Handsontable(document.getElementById('tablaPrevias'), {
                ...comunes,
                data: [],
                colHeaders: this.cabecerasPrevias,
                cells: () => ({ renderer: renderizador((instancia, r, c) => self.clavePrevias(r, c)) }),
            });
            window.registrarHot?.(hotPrevias);

            hotZona = new Handsontable(document.getElementById('reportePorMes'), {
                ...comunes,
                data: [],
                colHeaders: this.cabecerasZona,
                cells: () => ({
                    renderer: renderizador((instancia, r, c) =>
                        self.claveZona(r, c, instancia.countRows() - 1)),
                }),
            });
            window.registrarHot?.(hotZona);
        },

        /* HOT necesita recalcular cuando su contenedor pasa de oculto a visible. */
        remedir() {
            const hacerlo = () => {
                for (const hot of [hotConsolidado, hotPrevias, hotZona]) {
                    if (!hot) continue;
                    hot.refreshDimensions();
                    // Centra la rejilla si le sobra ancho en la tarjeta.
                    window.centrarHot?.(hot);
                    hot.render();
                }
            };
            this.$nextTick(hacerlo);
            setTimeout(hacerlo, 120);
        },

        /* ----------------------------- Utilidades ---------------------------- */
        /* Trunca a dos decimales, como el original (no redondea). */
        dosDecimales(n) {
            if (!Number.isFinite(Number(n))) return 0;
            const [entero, decimales] = String(n).split('.');
            return decimales ? `${entero}.${decimales.slice(0, 2)}` : entero;
        },

        porcentaje(parte, total) {
            return this.dosDecimales((parte / total) * 100);
        },

        /* Parte entera, o 0 si la división no da un número (meses sin
           inspectores daban "∞" en las columnas de promedio). */
        entero(n) { return Number.isFinite(n) ? Math.trunc(n) : 0; },

        aNumero(texto) {
            // "$ 1.234.567" -> 1234567
            const n = parseInt(String(texto ?? '').replace(/[^\d]/g, ''), 10);
            return isNaN(n) ? 0 : n;
        },

        soloDigitos(v) { return /^\d+$/.test(String(v)); },

        /* ---------------------------- Carga de datos -------------------------- */
        async cargar() {
            if (!this.anio) return;
            this.cargando = true;
            this.mes = '';
            this.hayMes = false;
            try {
                const data = await window.api(this.urls.consolidado, {
                    method: 'POST', body: { anio: this.anio },
                });

                // Visible ANTES de cargar: si no, las tablas se miden a cero.
                this.hayDatos = true;
                await this.$nextTick();

                this.procesar(data);
                this.remedir();
            } catch (e) {
                console.error('Error:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Hubo un problema al generar el consolidado.' });
            } finally {
                this.cargando = false;
            }
        },

        procesar(data) {
            const f = (v) => this.moneda.format(v);

            /* ---- Tabla consolidada ---- */
            let totalGeneral = 0;
            const filas = data.map(item => {
                const total = (item.total_residencial + item.total_comercial + item.total_inspecciones) || 0;
                totalGeneral += total;
                return [
                    item.nombre_mes,
                    item.total_residencial || 0,
                    item.total_comercial || 0,
                    item.total_inspecciones || 0,
                    total,
                    item.total_inspectores,
                    f(item.total),
                    0, 0, 0, 0,          // promedios: se derivan abajo
                    item.metaGyc, '0 %',
                    item.metaGdo, '0 %',
                ];
            });
            filas.push(['TOTAL', '', '', '', totalGeneral, '', '', '', '', '', '', '', '', '', '']);

            hotConsolidado.loadData(filas);
            this.derivar();

            /* ---- Tabla por tipo de trabajo ---- */
            hotPrevias.loadData(data.map(item => {
                const rp = item.total_rp || 0;
                const previas = item.total_previas || 0;
                const total = rp + previas;
                return [item.nombre_mes, rp, previas, total,
                        `${this.porcentaje(rp, total)} %`, `${this.porcentaje(previas, total)} %`];
            }));
        },

        /* Promedios y porcentajes de cumplimiento de todos los meses.
           El original paraba en `countRows() - 2`, así que diciembre se quedaba
           siempre con los promedios en cero. */
        derivar() {
            const ultima = hotConsolidado.countRows() - 1;   // fila TOTAL
            const cambios = [];

            for (let i = 0; i < ultima; i++) {
                const residencial = hotConsolidado.getDataAtCell(i, 1);
                const comercial = hotConsolidado.getDataAtCell(i, 2);
                const inspectores = hotConsolidado.getDataAtCell(i, 5);
                const total = hotConsolidado.getDataAtCell(i, 4);
                const valor = this.aNumero(hotConsolidado.getDataAtCell(i, 6));

                const diarioUnd = this.dosDecimales((residencial + comercial) / DIAS_HABILES);
                const diarioPesos = valor / DIAS_MES;

                cambios.push(
                    [i, 7, diarioUnd],
                    [i, 8, this.dosDecimales(diarioUnd / inspectores)],
                    [i, 9, this.moneda.format(this.entero(diarioPesos))],
                    [i, 10, this.moneda.format(this.entero(diarioPesos / inspectores))],
                    [i, 12, `${this.porcentaje(total, hotConsolidado.getDataAtCell(i, 11))} %`],
                    [i, 14, `${this.porcentaje(total, hotConsolidado.getDataAtCell(i, 13))} %`],
                );
            }

            hotConsolidado.batch(() => hotConsolidado.setDataAtCell(cambios, 'programmatic'));
        },

        /* ------------------------------ Metas -------------------------------- */
        /* Antes esto solo funcionaba en la penúltima fila (diciembre): el
           manejador comparaba `row === countRows() - 2`. En los demás meses se
           podía escribir la meta, pero ni se recalculaba el % ni se guardaba. */
        alEditarMeta(cambios, origen) {
            if (origen !== 'edit' || !cambios) return;
            const ultima = hotConsolidado.countRows() - 1;

            for (const [row, col, viejo, nuevo] of cambios) {
                if ((col !== 11 && col !== 13) || row === ultima) continue;

                const valor = nuevo === '' || nuevo === null ? '0' : String(nuevo);
                if (!this.soloDigitos(valor)) {
                    window.Swal.fire({ icon: 'warning', title: 'Meta',
                                       text: 'Por favor, ingrese solo números.' });
                    hotConsolidado.setDataAtCell(row, col, viejo, 'programmatic');
                    continue;
                }

                const meta = parseInt(valor, 10);
                const total = hotConsolidado.getDataAtCell(row, 4);

                // El % se pinta ya y el guardado va detrás: antes se esperaba la
                // respuesta del servidor para actualizarlo.
                hotConsolidado.setDataAtCell(row, col === 11 ? 12 : 14,
                                             `${this.porcentaje(total, meta)} %`, 'programmatic');
                this.guardarMeta(row, col, meta);
            }
        },

        async guardarMeta(row, col, meta) {
            const nombreMes = hotConsolidado.getDataAtCell(row, 0);
            const cuerpo = { anioMes: this.anio + (this.sufijos[nombreMes] ?? '') };
            // Se envía solo la meta editada: el controlador conserva la otra
            // cuando llega nula.
            if (col === 11) cuerpo.metagyc = meta;
            else cuerpo.metagdo = meta;

            try {
                const r = await window.api(this.urls.guardarMetas, { method: 'POST', body: cuerpo });
                if (String(r).trim() === '2') throw new Error('rechazado');
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo guardar la meta.' });
            }
        },

        /* --------------------------- Reporte por zona ------------------------- */
        async cargarMes() {
            if (!this.mes || !this.anio) return;
            this.cargando = true;
            try {
                const data = await window.api(this.urls.porMes, {
                    method: 'POST', body: { data: this.anio + this.mes },
                });

                this.hayMes = true;
                await this.$nextTick();

                this.procesarMes(data);
                this.remedir();
            } catch (e) {
                console.error('Error:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Hubo un problema al generar el reporte por zona.' });
            } finally {
                this.cargando = false;
            }
        },

        procesarMes(data) {
            const p = data.fechas[0];
            const cantidades = [
                data.residencial.zona_1, data.residencial.zona_2, data.residencial.zona_3,
                data.comercial.zona_1, data.comercial.zona_2, data.comercial.zona_3,
            ];
            const precios = [p.res_metro, p.res_norte, p.res_cauca,
                             p.com_metro, p.com_norte, p.com_cauca];

            const valores = cantidades.map((c, i) => c * precios[i]);
            const totalCantidad = cantidades.reduce((a, b) => a + b, 0);
            const totalValor = valores.reduce((a, b) => a + b, 0);

            const pctCantidad = cantidades.map(c => this.porcentaje(c, totalCantidad));
            const pctValor = valores.map(v => this.porcentaje(v, totalValor));
            // La fila TOTAL suma los porcentajes ya truncados, como el original.
            const suma = (lista) => lista.reduce((a, b) => a + parseFloat(b), 0).toFixed(2);

            const filas = ZONAS.map((zona, i) => [
                zona, cantidades[i], `${pctCantidad[i]}%`,
                this.moneda.format(valores[i]), `${pctValor[i]}%`,
            ]);
            filas.push(['TOTAL', totalCantidad, `${suma(pctCantidad)}%`,
                        this.moneda.format(totalValor), `${suma(pctValor)}%`]);

            hotZona.loadData(filas);
        },

        /* -------------------------------- Excel ------------------------------- */
        exportar() {
            const consolidado = hotConsolidado?.getData() ?? [];
            const previas = hotPrevias?.getData() ?? [];

            if (!this.hayDatos || consolidado.length === 0 || previas.length === 0) {
                window.Swal.fire({ icon: 'warning', title: 'Advertencia',
                                   text: 'Por favor seleccione una opción antes de exportar' });
                return;
            }

            const limpiar = (cabeceras) => cabeceras.map(h => String(h).replace(/<br>/g, ' '));

            const bloque1 = [limpiar(hotConsolidado.getColHeader()), ...consolidado];

            // Tipo de trabajo y zona van lado a lado, separados por dos columnas.
            const izquierda = [limpiar(hotPrevias.getColHeader()), ...previas];
            const derecha = this.hayMes
                ? [limpiar(hotZona.getColHeader()), ...hotZona.getData()]
                : [];

            const filas = Math.max(izquierda.length, derecha.length);
            const bloque2 = [];
            for (let i = 0; i < filas; i++) {
                bloque2.push([...(izquierda[i] ?? []), '', '', ...(derecha[i] ?? [])]);
            }

            const hoja = XLSX.utils.aoa_to_sheet([...bloque1, [], [], ...bloque2]);
            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Reporte Consolidado');
            XLSX.writeFile(libro, 'ReporteConsolidado.xlsx');
        },
    }));
});
</script>
