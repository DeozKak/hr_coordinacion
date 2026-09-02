<script>
/* Las instancias de Handsontable viven fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotNomina = null;
let hotCostos = null;

/* Columnas de cada tabla, por nombre, para no razonar con índices sueltos. */
const NOM = ['cc', 'nombre', 'produccion', 'bonificacion', 'copas',
             'totalBonificacion', 'multas', 'bonoComercial', 'total'];
const COS = ['cc', 'nombre', 'aprendiz', 'salario', 'auxTransporte', 'salud', 'pension',
             'arl', 'caja', 'prima', 'cesantias', 'intCesantias', 'vacaciones', 'total'];
const cn = (nombre) => NOM.indexOf(nombre);
const cc = (nombre) => COS.indexOf(nombre);

/* Columnas que se muestran como dinero. Van por nombre a propósito: la cédula
   es un número que NO lleva separadores ni símbolo, y la producción es una
   cantidad de unidades, no un valor. El formato es solo de presentación; los
   datos de la rejilla siguen siendo números para poder sumarlos y exportarlos. */
const DINERO_NOM = new Set(['bonificacion', 'copas', 'totalBonificacion',
                            'multas', 'bonoComercial', 'total'].map(cn));
const DINERO_COS = new Set(['salario', 'auxTransporte', 'salud', 'pension', 'arl', 'caja',
                            'prima', 'cesantias', 'intCesantias', 'vacaciones', 'total'].map(cc));

/* Escalones de la bonificación, tal y como estaban escritos a mano. */
const COPAS = [
    { desde: 300, valor: 500000 },
    { desde: 250, valor: 330000 },
    { desde: 200, valor: 180000 },
];
const BASE_BONIFICACION = 180;
const VALOR_UNIDAD = 13000;
const EXTRA_NO_APRENDIZ = 150000;

document.addEventListener('alpine:init', () => {
    Alpine.data('reporteNomina', ({ urls }) => ({
        urls,

        mesAnio: '',
        cargando: false,
        guardandoMulta: false,
        hayDatos: false,
        titulo: 'Reporte de nómina',

        parametros: {},
        inspectores: [],

        moneda: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0,
        }),
        entero: new Intl.NumberFormat('es-CO', { maximumFractionDigits: 0 }),

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.construirTablas();
            this.$watch('$store.ui.dark', () => {
                hotNomina?.render();
                hotCostos?.render();
            });
        },

        /* ------------------------------ Formato ------------------------------ */
        /* Devuelve el número de una celda, o null si está vacía o no es numérica
           (los rótulos 'TOTAL', 'SI'/'NO' y los huecos de la fila de totales). */
        aNumero(valor) {
            if (valor === null || valor === undefined || valor === '') return null;
            const n = Number(valor);
            return Number.isFinite(n) ? n : null;
        },

        /* 'dinero' → $ 1.750.905   ·   'cantidad' → 1.750   ·   null → tal cual. */
        formatear(valor, tipo) {
            const n = this.aNumero(valor);
            if (n === null || !tipo) return valor;
            return tipo === 'dinero' ? this.moneda.format(n) : this.entero.format(n);
        },

        tipoNomina(columna) {
            if (DINERO_NOM.has(columna)) return 'dinero';
            return columna === cn('produccion') ? 'cantidad' : null;
        },

        tipoCostos(columna) {
            return DINERO_COS.has(columna) ? 'dinero' : null;
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Los tonos originales eran puros (amarillo y rojo a tope, verde 00B050):
           aquí son tintes de la paleta de la aplicación, claros sobre el tema
           claro y profundos sobre el oscuro. En oscuro ninguno puede acercarse
           al fondo de la rejilla (#1e293b) ni al secundario (#0f172a). */
        paleta() {
            return Alpine.store('ui').dark ? {
                copas: '#1d4ed8', bonificacion: '#a16207', multas: '#b91c1c',
                bonoComercial: '#15803d', aprendiz: '#9a3412', costoTotal: '#0369a1',
                totales: '#64748b',
            } : {
                copas: '#bfdbfe', bonificacion: '#fef08a', multas: '#fecaca',
                bonoComercial: '#bbf7d0', aprendiz: '#fed7aa', costoTotal: '#bae6fd',
                totales: '#334155',
            };
        },

        /* El color del texto se elige por contraste en vez de fijarlo a negro:
           el CSS anterior escribía `color: black` sobre el rojo puro. */
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
            /* Con prioridad: las rejillas son de solo lectura y .htDimmed impone
               fondo y color desde la hoja de estilos de Handsontable. */
            td.style.setProperty('background-color', fondo, 'important');
            td.style.setProperty('color', this.contraste(fondo), 'important');
        },

        claveNomina(fila, columna, ultima) {
            if (fila === ultima) return 'totales';
            return { [cn('copas')]: 'copas',
                     [cn('totalBonificacion')]: 'bonificacion',
                     [cn('multas')]: 'multas',
                     [cn('bonoComercial')]: 'bonoComercial' }[columna] ?? null;
        },

        claveCostos(fila, columna, ultima, esAprendiz) {
            if (fila === ultima) return 'totales';
            if (columna === cc('total')) return 'costoTotal';
            if (columna === cc('aprendiz') && esAprendiz) return 'aprendiz';
            return null;
        },

        /* ------------------------------ Tablas ------------------------------- */
        construirTablas() {
            const self = this;

            /* El formato se aplica aquí y no sobre los datos: la rejilla guarda
               números para poder sumar los totales, exportar a Excel sin texto y
               abrir el editor de MULTAS con el valor limpio. */
            const renderizador = (clave, tipo) => function (instancia, td, fila, columna, prop, valor, meta) {
                const formato = tipo(columna);
                const args = [...arguments];
                args[5] = self.formatear(valor, formato);
                Handsontable.renderers.TextRenderer.apply(this, args);
                // Las cifras se alinean a la derecha para poder compararlas de un vistazo.
                td.style.textAlign = formato ? 'right' : '';
                td.style.removeProperty('background-color');
                self.pintar(td, clave(instancia, fila, columna));
            };

            const comunes = {
                data: [],
                rowHeaders: true,
                height: '60vh',
                rowHeights: 26,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                readOnly: true,
            };

            hotNomina = new Handsontable(document.getElementById('tablaNomina'), {
                ...comunes,
                colHeaders: ['CC', 'INSPECTOR', 'PRODUCCIÓN', 'BONIFICACIÓN > 180', 'COPAS',
                             'TOTAL BONIFICACIÓN', 'MULTAS', 'BONO COMERCIAL', 'TOTAL'],
                cells(fila, columna) {
                    const ultima = this.instance.countRows() - 1;
                    return {
                        // Solo MULTAS es editable, y no en la fila de totales.
                        readOnly: !(columna === cn('multas') && fila !== ultima),
                        renderer: renderizador((instancia, f, c) =>
                            self.claveNomina(f, c, instancia.countRows() - 1),
                            (c) => self.tipoNomina(c)),
                    };
                },
                afterChange: (cambios, origen) => this.alEditarMulta(cambios, origen),
            });
            window.registrarHot?.(hotNomina);

            hotCostos = new Handsontable(document.getElementById('tablaCostosProyecto'), {
                ...comunes,
                colHeaders: this.cabecerasCostos(),
                cells(fila, columna) {
                    const ultima = this.instance.countRows() - 1;
                    const esAprendiz = this.instance.getDataAtCell(fila, cc('aprendiz')) === 'SI';
                    return {
                        renderer: renderizador((instancia, f, c) =>
                            self.claveCostos(f, c, instancia.countRows() - 1, esAprendiz),
                            (c) => self.tipoCostos(c)),
                    };
                },
            });
            window.registrarHot?.(hotCostos);
        },

        /* Las cabeceras llevan el parámetro vigente, como en el original. */
        cabecerasCostos() {
            const p = this.parametros;
            const pct = (v) => `${v ?? 0} %`;
            return [
                'CÓDIGO', 'NOMBRE DEL EMPLEADO', 'APRENDIZ',
                `SALARIO\n${this.moneda.format(p.salarioMinimo ?? 0)}`,
                `AUX. TRANSPORTE\n${this.moneda.format(p.auxilioTransporte ?? 0)}`,
                `SALUD\n${pct(p.salud)}`, `PENSIÓN\n${pct(p.pension)}`, `ARL\n${pct(p.arl)}`,
                `CAJA\n${pct(p.caja)}`, `PRIMA\n${pct(p.prima)}`,
                `CESANTÍAS\n${pct(p.cesantias)}`, `INT. CESANTÍAS\n${pct(p.intCesantias)}`,
                `VACACIONES\n${pct(p.vacaciones)}`, 'TOTAL',
            ];
        },

        remedir() {
            const hacerlo = () => {
                for (const hot of [hotNomina, hotCostos]) {
                    if (!hot) continue;
                    hot.refreshDimensions();
                    window.centrarHot?.(hot);
                    hot.render();
                }
            };
            this.$nextTick(hacerlo);
            setTimeout(hacerlo, 120);
        },

        /* ---------------------------- Carga de datos -------------------------- */
        get corte() {
            if (!this.mesAnio) return '';
            const [anio, mes] = this.mesAnio.split('-');
            const fecha = new Date(anio, Number(mes) - 1);
            const actual = fecha.toLocaleString('es-ES', { month: 'long' });
            fecha.setMonth(fecha.getMonth() - 1);
            const anterior = fecha.toLocaleString('es-ES', { month: 'long' });
            return `${anterior}-${actual} ${anio}`;
        },

        async generar() {
            if (!this.mesAnio) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la fecha',
                                   text: 'Selecciona el mes del corte.' });
                return;
            }

            this.cargando = true;
            try {
                const r = await window.api(this.urls.generar,
                                           { method: 'POST', body: { mesAnio: this.mesAnio } });

                if (!Array.isArray(r) || r.length === 0) {
                    this.hayDatos = false;
                    window.Swal.fire({ icon: 'info', title: 'Sin datos',
                                       text: 'No hay datos para ese corte.' });
                    return;
                }

                this.parametros = r[0].salariosAux ?? {};
                this.inspectores = r[0].inspectores ?? [];
                this.titulo = `Reporte de nómina · corte ${this.corte}`;

                // Visible ANTES de cargar: si no, las tablas se miden a cero.
                this.hayDatos = true;
                await this.$nextTick();

                this.procesar(r[0].data.produccionInspector ?? [], r[0].multas ?? []);
                this.remedir();
            } catch (e) {
                console.error('Error:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo generar el reporte.' });
            } finally {
                this.cargando = false;
            }
        },

        /* --------------------------- Cálculo de nómina ------------------------ */
        copasDe(produccion) {
            return COPAS.find(e => produccion >= e.desde)?.valor ?? 0;
        },

        bonificacionDe(produccion) {
            return produccion > BASE_BONIFICACION
                ? (produccion - BASE_BONIFICACION) * VALOR_UNIDAD : 0;
        },

        multaDe(multas, cedula) {
            return multas.find(m => String(m.cc_operario) === String(cedula))?.multa ?? 0;
        },

        filaNomina(item, multas) {
            const bonificacion = this.bonificacionDe(item.total);
            const copas = this.copasDe(item.total);
            const totalBonificacion = Math.trunc(bonificacion + copas);
            const multa = Number(this.multaDe(multas, item.cedula)) || 0;
            const bonoComercial = Math.trunc(totalBonificacion - multa);

            return [item.cedula, item.nombres, item.total,
                    bonificacion, copas, totalBonificacion, multa, bonoComercial, bonoComercial];
        },

        /* Salario y aportes de un inspector a partir del total de su nómina. */
        filaCostos(cedula, nombre, totalNomina) {
            const p = this.parametros;
            const inspector = this.inspectores.find(i => String(i.cedula) === String(cedula));
            const esAprendiz = inspector?.aprendiz === 1;

            const salario = totalNomina + (p.salarioMinimo ?? 0)
                          + (esAprendiz ? 0 : EXTRA_NO_APRENDIZ);
            const auxilio = salario > (p.salarioMinimo ?? 0) * 2 ? 0 : (p.auxilioTransporte ?? 0);

            const aporte = (pct) => Math.trunc(salario * (pct ?? 0) / 100);
            const partes = [aporte(p.salud), aporte(p.pension), aporte(p.arl), aporte(p.caja),
                            aporte(p.prima), aporte(p.cesantias), aporte(p.intCesantias),
                            aporte(p.vacaciones)];

            const total = salario + auxilio + partes.reduce((a, b) => a + b, 0);

            return [cedula, nombre, esAprendiz ? 'SI' : 'NO', salario, auxilio, ...partes, total];
        },

        /* Fila de totales: se suman las columnas numéricas y el resto va vacío. */
        filaTotales(filas, desde, ancho) {
            const total = new Array(ancho).fill('');
            total[0] = 'TOTAL';
            for (let c = desde; c < ancho; c++) {
                total[c] = filas.reduce((suma, f) => suma + (Number(f[c]) || 0), 0);
            }
            return total;
        },

        procesar(produccion, multas) {
            const filasNomina = produccion.map(item => this.filaNomina(item, multas));
            filasNomina.push(this.filaTotales(filasNomina, cn('totalBonificacion'), NOM.length));
            hotNomina.loadData(filasNomina);

            const filasCostos = filasNomina
                .slice(0, -1)
                .map(f => this.filaCostos(f[cn('cc')], f[cn('nombre')], f[cn('total')]));
            filasCostos.push(this.filaTotales(filasCostos, cc('salario'), COS.length));

            hotCostos.updateSettings({ colHeaders: this.cabecerasCostos() });
            hotCostos.loadData(filasCostos);
        },

        /* ------------------------------- Multas ------------------------------- */
        async alEditarMulta(cambios, origen) {
            if (origen !== 'edit' || !cambios) return;
            const ultima = hotNomina.countRows() - 1;

            for (const [fila, columna, viejo, nuevo] of cambios) {
                if (columna !== cn('multas') || fila === ultima) continue;

                if (!/^\d*$/.test(String(nuevo ?? ''))) {
                    window.Swal.fire({ icon: 'warning', title: 'Multa',
                                       text: 'Ingrese solo números en el campo de multas.' });
                    hotNomina.setDataAtCell(fila, columna, viejo, 'programmatic');
                    continue;
                }

                const multa = parseInt(nuevo, 10) || 0;
                const cedula = hotNomina.getDataAtCell(fila, cn('cc'));

                /* Se recalcula al momento y el guardado va detrás: antes se
                   atenuaban las dos tablas y se esperaba al servidor para
                   volver a pintar. */
                this.recalcularFila(fila, multa);
                this.guardarMulta(cedula, multa);
            }
        },

        /* Recalcula la fila y las dos filas de totales tras cambiar una multa. */
        recalcularFila(fila, multa) {
            const ultima = hotNomina.countRows() - 1;
            const totalBonificacion = Number(hotNomina.getDataAtCell(fila, cn('totalBonificacion'))) || 0;
            const bonoComercial = totalBonificacion - multa;

            hotNomina.batch(() => hotNomina.setDataAtCell([
                [fila, cn('multas'), multa],
                [fila, cn('bonoComercial'), bonoComercial],
                [fila, cn('total'), bonoComercial],
            ], 'programmatic'));

            const filas = hotNomina.getData().slice(0, ultima);
            const totales = this.filaTotales(filas, cn('totalBonificacion'), NOM.length);
            hotNomina.batch(() => hotNomina.setDataAtCell(
                totales.map((v, c) => [ultima, c, v]).filter(([, c]) => c >= cn('totalBonificacion')),
                'programmatic'));

            // Y la fila equivalente de costos, que depende del total de nómina.
            const cedula = hotNomina.getDataAtCell(fila, cn('cc'));
            const nombre = hotNomina.getDataAtCell(fila, cn('nombre'));
            const nueva = this.filaCostos(cedula, nombre, bonoComercial);
            hotCostos.batch(() => hotCostos.setDataAtCell(
                nueva.map((v, c) => [fila, c, v]), 'programmatic'));

            const ultimaCostos = hotCostos.countRows() - 1;
            const totalesCostos = this.filaTotales(
                hotCostos.getData().slice(0, ultimaCostos), cc('salario'), COS.length);
            hotCostos.batch(() => hotCostos.setDataAtCell(
                totalesCostos.map((v, c) => [ultimaCostos, c, v]).filter(([, c]) => c >= cc('salario')),
                'programmatic'));
        },

        async guardarMulta(cedula, multa) {
            this.guardandoMulta = true;
            try {
                const r = await window.api(this.urls.multa, {
                    method: 'POST',
                    body: { ccOperario: cedula, multa, fecha: this.mesAnio },
                });
                if (String(r).trim() === '2') throw new Error('rechazado');
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo guardar la multa.' });
            } finally {
                this.guardandoMulta = false;
            }
        },

        /* -------------------------------- Excel ------------------------------- */
        exportar() {
            if (!hotNomina || !hotCostos) return;

            const limpiar = (cabeceras) => cabeceras.map(h => String(h).replace(/\n/g, ' '));
            const bloque1 = [limpiar(hotNomina.getColHeader()), ...hotNomina.getData()];
            const bloque2 = [limpiar(hotCostos.getColHeader()), ...hotCostos.getData()];

            const hoja = XLSX.utils.aoa_to_sheet([...bloque1, [], [], ...bloque2]);
            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Reporte_Completo');
            XLSX.writeFile(libro, 'Reporte_nomina_costos.xlsx');
        },
    }));
});
</script>
