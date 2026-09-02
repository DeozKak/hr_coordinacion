<script>
/* Las instancias de Handsontable viven fuera del estado de Alpine. */
const PRECIO_PROYECCION = 38990;   // precio unitario usado para proyectar
let hotDiario = null;
let hotResumen = null;

document.addEventListener('alpine:init', () => {
    Alpine.data('reporteDiario', ({ urls, meses }) => ({
        urls, meses,

        anio: '',
        mes: '',
        cargando: false,
        hayDatos: false,

        festivos: [],
        sabados: [],
        fechasFila: [],        // fecha de cada fila, para colorear festivos y sábados
        valoresPrevios: [],
        valorInspeccionIndustrial: '',

        moneda: new Intl.NumberFormat('es-CO', {
            style: 'currency', currency: 'COP', minimumFractionDigits: 0,
        }),

        cabeceras: [
            'Fechas',
            'RP/ AS / NV METRO <br> RES', 'RP/ AS / NV NORTE <br> RES', 'RP/ AS / NV CAUCA <br> RES',
            'RP/ AS / NV METRO <br> COM', 'RP/ AS / NV NORTE <br> COM', 'RP/ AS / NV CAUCA <br> COM',
            'FACTURACION RP/ AS / NV <br> METRO RES', 'FACTURACION RP/ AS / NV<br>NORTE RES',
            'FACTURACION RP/ AS / NV<br>CAUCA RES', 'FACTURACION RP/ AS / NV<br>METRO COM',
            'FACTURACION RP/ AS / NV<br>NORTE COM', 'FACTURACION RP/ AS / NV<br>CAUCA COM',
            'FACTURACION <br> VALLE DEL CAUCA', 'INSPECTORES', 'PROMEDIO',
            'CANTIDAD <br> EJECUTADA', 'DIFERENCIA', 'CANTIDAD <br> PROYECTADA',
            'VALOR <br> PROYECTADO', '%', 'VALOR <br> EJECUTADO', '%',
        ],

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.construirTablas();
            this.$watch('$store.ui.dark', () => {
                hotDiario?.render();
                hotResumen?.render();
            });
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Un solo sitio para el código de color. Antes cada regla repetía su
           renderizador con el color escrito a mano en seis lugares distintos. */
        /* Un solo sitio para el código de color.
           Los tonos originales (verde puro, cian, amarillo saturado) chillaban
           sobre el diseño: ahora se usan tintes de la propia paleta de la
           aplicación, claros en el tema claro y profundos en el oscuro. Ojo con
           el oscuro: el fondo de la rejilla es #1e293b y el secundario #0f172a,
           así que ningún tono puede acercarse a esos dos o la celda se pierde. */
        paleta() {
            const oscuro = Alpine.store('ui').dark;
            return oscuro ? {
                totales: '#64748b', festivo: '#86198f', sabado: '#a16207',
                residencial: '#1d4ed8', comercial: '#0f766e', valle: '#475569',
                difNegativa: '#b91c1c', difPositiva: '#15803d', difCero: '#0369a1',
                industrial: '#c2410c',
            } : {
                totales: '#334155', festivo: '#f5d0fe', sabado: '#fef08a',
                residencial: '#bfdbfe', comercial: '#99f6e4', valle: '#cbd5e1',
                difNegativa: '#fecaca', difPositiva: '#bbf7d0', difCero: '#bae6fd',
                industrial: '#fed7aa',
            };
        },

        /* El texto se elige por contraste en vez de fijarlo a blanco: con el
           blanco de antes, "sábado" quedaba en 1.37:1. Se comparan los dos
           candidatos y gana el de mayor razón; un umbral de luminancia se
           equivocaba con los tonos medios, como el verde comercial. */
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
            // Con prioridad: la grilla es readOnly y .htDimmed impone fondo y color.
            td.style.setProperty('background-color', fondo, 'important');
            td.style.setProperty('color', this.contraste(fondo), 'important');
        },

        /* Clave semántica de cada celda de la tabla diaria. */
        claveDiario(row, col, ultimaFila) {
            if (row === ultimaFila) return 'totales';

            if (col === 17) {                                  // DIFERENCIA
                const ejecutada = parseFloat(hotDiario.getDataAtCell(row, 16));
                const proyectada = parseFloat(hotDiario.getDataAtCell(row, 18));
                if (!isNaN(ejecutada) && !isNaN(proyectada)) {
                    const dif = ejecutada - proyectada;
                    if (dif < 0) return 'difNegativa';
                    if (dif > 0) return 'difPositiva';
                    return 'difCero';
                }
            }

            const fecha = this.fechasFila[row];
            if (this.festivos.includes(fecha)) return 'festivo';
            if (this.sabados.includes(fecha)) return 'sabado';

            if ([1, 2, 3, 7, 8, 9].includes(col)) return 'residencial';
            if ([4, 5, 6, 10, 11, 12].includes(col)) return 'comercial';
            if ([13, 14].includes(col)) return 'valle';
            return null;
        },

        /* Clave de cada celda del resumen. */
        claveResumen(row, col, penultima) {
            if (col === 2 && row === 6) return 'industrial';           // inspección industrial
            if (row === penultima && [1, 2, 3].includes(col)) return 'industrial';
            if ([0, 1, 2].includes(row) && [1, 2, 3].includes(col)) return 'residencial';
            if ([3, 4, 5].includes(row) && [1, 2, 3].includes(col)) return 'comercial';
            return null;
        },

        /* ------------------------------ Tablas ------------------------------- */
        construirTablas() {
            const self = this;

            hotDiario = new Handsontable(document.getElementById('example'), {
                data: [],
                colHeaders: this.cabeceras,
                rowHeaders: true,
                height: 'auto',
                // Sin virtualización vertical. Con 'auto' HOT calcula cuántas
                // filas pinta a partir del viewport de la ventana, y como la
                // tarjeta nace oculta y queda por debajo del pliegue, esa cuenta
                // salía corta y faltaban filas. Son ~32 filas: pintarlas todas
                // no cuesta nada y elimina el problema de raíz.
                renderAllRows: true,
                autoWrapRow: true,
                autoWrapCol: true,
                wordWrap: false,
                rowHeights: 24,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                readOnly: true,
                cells(row, col) {
                    const ultima = this.instance.countRows() - 1;
                    return {
                        // Solo CANTIDAD PROYECTADA es editable, y no en la fila de totales.
                        readOnly: !(col === 18 && row !== ultima),
                        renderer(instancia, td, r, c, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.removeProperty('background-color');
                            self.pintar(td, self.claveDiario(r, c, instancia.countRows() - 1));
                        },
                    };
                },
                afterChange: (cambios, origen) => this.alEditarProyeccion(cambios, origen),
            });
            window.registrarHot?.(hotDiario);

            hotResumen = new Handsontable(document.getElementById('tablaResumen'), {
                data: this.filasResumenVacias(),
                height: 'auto',
                renderAllRows: true,        // ver nota en la tabla diaria
                licenseKey: 'non-commercial-and-evaluation',
                readOnly: true,
                rowHeights: 24,
                colHeaders: ['CONCEPTO', 'PRECIO', 'CANTIDAD', 'FACTURACIÓN', '%'],
                cells(row, col) {
                    return {
                        readOnly: !(col === 2 && row === 6),
                        renderer(instancia, td, r, c, prop, value, cellProperties) {
                            Handsontable.renderers.TextRenderer.apply(this, arguments);
                            td.style.removeProperty('background-color');
                            self.pintar(td, self.claveResumen(r, c, instancia.countRows() - 2));
                        },
                    };
                },
                afterChange: (cambios, origen) => this.alEditarIndustrial(cambios, origen),
            });
            window.registrarHot?.(hotResumen);
        },

        filasResumenVacias() {
            const f = (v) => this.moneda.format(v);
            return [
                ['PREVIA/PERIODICA (metropolitana residencial)', f(38990), '', '', ''],
                ['PREVIA/PERIODICA (Zona norte residencial)', f(42451), '', '', ''],
                ['PREVIA/PERIODICA (Zona Buenaventura y Cauca residencial)', f(55479), '', '', ''],
                ['PREVIA/PERIODICA (Zona metropolitana comercial)', f(70810), '', '', ''],
                ['PREVIA/PERIÓDICA (Zona norte comercial)', f(71240), '', '', ''],
                ['PREVIA /PERIÓDICA (Zona Buenaventura y Cauca comercial)', f(76300), '', '', ''],
                ['INSPECCION INDUSTRIAL', f(680000), '', '', ''],
                ['', '', '', '', ''],
            ];
        },

        /* ---------------------------- Carga de datos -------------------------- */
        async cargar() {
            if (!this.mes || !this.anio) return;
            this.cargando = true;
            try {
                const data = await window.api(`${this.mes}?anio=${this.anio}`);

                // Las tarjetas nacen ocultas (x-show), así que las tablas se
                // construyeron con el contenedor en display:none y HOT se quedó
                // con medidas cero: mostraba solo las primeras filas. Hay que
                // hacerlas visibles ANTES de cargar los datos y remedir después.
                this.hayDatos = true;
                await this.$nextTick();

                this.procesar(data);
                this.remedir();
            } catch (e) {
                console.error('Error:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Hubo un problema al cargar los datos.' });
            } finally {
                this.cargando = false;
            }
        },

        /* HOT necesita recalcular cuando su contenedor pasa de oculto a visible. */
        remedir() {
            const hacerlo = () => {
                for (const hot of [hotDiario, hotResumen]) {
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

        procesar(data) {
            const precios = data.preciosParametros[0];
            const f = (v) => this.moneda.format(v);

            this.festivos = [...(data.diasFestivos ?? [])];
            this.sabados = [...(data.diasSabados ?? [])];
            this.valoresPrevios = (data.nomina ?? []).map(n => n.proyeccion);
            this.valorInspeccionIndustrial = data.inspeccionIndustrial?.length
                ? data.inspeccionIndustrial[0].cantidad : '';

            /* ---- Filas del reporte: conteos y facturación por zona ---- */
            const filas = [];
            const total = { res1: 0, res2: 0, res3: 0, com1: 0, com2: 0, com3: 0 };
            const suma = { res1: 0, res2: 0, res3: 0, com1: 0, com2: 0, com3: 0 };
            let sumaGeneral = 0, ejecutadaAcum = 0, valorEjecutadoAcum = 0;

            for (const dia of data.conteos) {
                const c = { res1: 0, res2: 0, res3: 0, com1: 0, com2: 0, com3: 0 };
                const p = { res1: 0, res2: 0, res3: 0, com1: 0, com2: 0, com3: 0 };
                let unidades = 0;

                for (const conteo of dia.conteos) {
                    const zonas = [
                        ['res1', conteo.count_residencial_zona_1, precios.res_metro],
                        ['res2', conteo.count_residencial_zona_2, precios.res_norte],
                        ['res3', conteo.count_residencial_zona_3, precios.res_cauca],
                        ['com1', conteo.count_comercial_zona_1, precios.com_metro],
                        ['com2', conteo.count_comercial_zona_2, precios.com_norte],
                        ['com3', conteo.count_comercial_zona_3, precios.com_cauca],
                    ];
                    for (const [clave, cantidad, precio] of zonas) {
                        if (cantidad > 0) { c[clave] += cantidad; p[clave] += cantidad * precio; total[clave] += cantidad; }
                        unidades += cantidad;
                    }
                }

                const totalFila = p.res1 + p.res2 + p.res3 + p.com1 + p.com2 + p.com3;
                sumaGeneral += totalFila;
                for (const k of Object.keys(suma)) suma[k] += p[k];

                ejecutadaAcum += unidades;
                valorEjecutadoAcum += totalFila;

                filas.push([
                    dia.fecha,
                    c.res1, c.res2, c.res3, c.com1, c.com2, c.com3,
                    f(p.res1), f(p.res2), f(p.res3), f(p.com1), f(p.com2), f(p.com3),
                    f(totalFila),
                    dia.conteos.length,
                    this.promedio(unidades, dia.conteos.length),
                    ejecutadaAcum,
                    '', '',
                    f(0), '% 0', f(valorEjecutadoAcum), '% 0',
                ]);
            }

            filas.push(['TOTAL',
                total.res1, total.res2, total.res3, total.com1, total.com2, total.com3,
                f(suma.res1), f(suma.res2), f(suma.res3), f(suma.com1), f(suma.com2), f(suma.com3),
                f(sumaGeneral), '', '', ejecutadaAcum, '', '',
                f(0), '% 0', f(valorEjecutadoAcum), '% 0']);

            this.fechasFila = filas.map(r => r[0]);
            hotDiario.loadData(filas);

            // Proyecciones ya guardadas para este mes, en un solo lote.
            const guardadas = [];
            for (const item of data.nomina ?? []) {
                const i = this.fechasFila.indexOf(item.fechaNomina);
                if (i !== -1) guardadas.push([i, 18, item.proyeccion]);
            }
            if (guardadas.length) {
                hotDiario.batch(() => hotDiario.setDataAtCell(guardadas, 'programmatic'));
                // Y se derivan DIFERENCIA, VALOR PROYECTADO y porcentajes. El
                // original escribía estas proyecciones con origen 'edit', así que
                // el propio afterChange las recalculaba (y de paso reenviaba cada
                // una al servidor). Aquí se escriben como 'programmatic', así que
                // hay que recalcular a mano o la columna DIFERENCIA queda vacía
                // al recargar la página.
                this.recalcular();
            }

            /* ---- Resumen ---- */
            hotResumen.loadData([
                ['PREVIA/PERIODICA (metropolitana residencial)', f(precios.res_metro), total.res1, f(total.res1 * precios.res_metro), '0 %'],
                ['PREVIA/PERIODICA (Zona norte residencial)', f(precios.res_norte), total.res2, f(total.res2 * precios.res_norte), '0 %'],
                ['PREVIA/PERIODICA (Zona Buenaventura y Cauca residencial)', f(precios.res_cauca), total.res3, f(total.res3 * precios.res_cauca), '0 %'],
                ['PREVIA/PERIODICA (Zona metropolitana comercial)', f(precios.com_metro), total.com1, f(total.com1 * precios.com_metro), '0 %'],
                ['PREVIA/PERIÓDICA (Zona norte comercial)', f(precios.com_norte), total.com2, f(total.com2 * precios.com_norte), '0 %'],
                ['PREVIA /PERIÓDICA (Zona Buenaventura y Cauca comercial)', f(precios.com_cauca), total.com3, f(total.com3 * precios.com_cauca), '0 %'],
                ['INSPECCION INDUSTRIAL', f(precios.inspeccion_industrial), this.valorInspeccionIndustrial, '', ''],
                ['', '', '', '', ''],
            ]);

            this.recalcularResumen();
        },

        /* Porcentaje de participación y fila de totales del resumen. Antes solo
           se calculaban al editar la inspección industrial, así que al cargar la
           tabla salía con ceros y sin la fila final. */
        recalcularResumen() {
            const industrial = hotResumen.countRows() - 2;   // penúltima fila
            const totales = industrial + 1;

            const precioInd = this.aNumero(hotResumen.getDataAtCell(industrial, 1));
            const cantInd = parseInt(hotResumen.getDataAtCell(industrial, 2), 10) || 0;
            const totalInd = precioInd * cantInd;

            const facturacion = [];
            let totalFinal = 0;
            let cantidadTotal = 0;

            for (let i = 0; i <= industrial; i++) {
                const valor = i === industrial ? totalInd : this.aNumero(hotResumen.getDataAtCell(i, 3));
                facturacion.push(valor);
                totalFinal += valor;
                const c = parseInt(hotResumen.getDataAtCell(i, 2), 10);
                if (!isNaN(c)) cantidadTotal += c;
            }

            const cambios = [[industrial, 3, this.moneda.format(totalInd)]];
            let sumaPorcentajes = 0;

            for (let i = 0; i <= industrial; i++) {
                const pct = totalFinal ? (facturacion[i] / totalFinal) * 100 : 0;
                sumaPorcentajes += pct;
                cambios.push([i, 4, `${this.unDecimal(pct)} %`]);
            }

            cambios.push([totales, 2, cantidadTotal]);
            cambios.push([totales, 3, this.moneda.format(totalFinal)]);
            cambios.push([totales, 4, `${this.unDecimal(sumaPorcentajes)} %`]);

            hotResumen.batch(() => hotResumen.setDataAtCell(cambios, 'programmatic'));
        },

        /* Trunca a un decimal, como el original (no redondea). */
        unDecimal(n) {
            if (isNaN(n)) return '0';
            const [entero, decimales] = n.toString().split('.');
            return decimales ? `${entero}.${decimales.charAt(0)}` : entero;
        },

        /* Promedio por inspector con dos decimales, como el original. */
        promedio(unidades, inspectores) {
            if (!inspectores) return '0.0';
            const v = (unidades / inspectores).toString().split('.');
            const r = v[1] ? `${v[0]}.${v[1].slice(0, 2)}` : v[0];
            return isNaN(r) ? '0.0' : r;
        },

        /* --------------------- Edición de la proyección ----------------------- */
        soloDigitos(v) { return /^\d*$/.test(v); },

        aNumero(texto) {
            // "$ 1.234.567" -> 1234567
            const n = parseInt(String(texto ?? '').replace(/[^\d]/g, ''), 10);
            return isNaN(n) ? 0 : n;
        },

        alEditarProyeccion(cambios, origen) {
            if (origen !== 'edit' || !cambios) return;
            const ultima = hotDiario.countRows() - 1;

            for (const [row, col, viejo, nuevo] of cambios) {
                if (col !== 18 || row === ultima) continue;

                if (nuevo === '' || nuevo === 0 || nuevo === '0' || !this.soloDigitos(nuevo)) {
                    window.Swal.fire({ icon: 'warning', title: 'Cantidad proyectada',
                        text: 'Ingrese un número mayor a 0 en el campo CANTIDAD PROYECTADA.' });
                    // null = esta fila no aporta proyección; así los totales y
                    // los porcentajes también se corrigen, no solo la fila.
                    this.recalcular(row, null);
                    continue;
                }

                const cantidad = parseInt(nuevo, 10);

                // La tabla se actualiza YA y el guardado va detrás: antes se
                // esperaba la respuesta del servidor antes de repintar y la
                // página se quedaba trabada durante ese viaje.
                this.recalcular(row, cantidad);

                if (this.valoresPrevios[row] !== cantidad) {
                    this.valoresPrevios[row] = cantidad;
                    this.guardarProyeccion(row, cantidad);
                }
            }
        },

        async guardarProyeccion(row, cantidad) {
            try {
                const r = await window.api(this.urls.guardarNomina, {
                    method: 'POST',
                    body: { nuevaCant: cantidad, fechaFila: hotDiario.getDataAtCell(row, 0) },
                });
                if (r === 2 || r?.resultado === 2) throw new Error('rechazado');
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo cambiar la cantidad' });
            }
        },

        /* Diferencia, valor proyectado, totales y porcentajes de TODA la tabla,
           derivados de la columna CANTIDAD PROYECTADA.
           Sirve igual al editar una celda que al cargar un mes con proyecciones
           ya guardadas. Todo va en UN solo lote: antes eran ~66 escrituras
           sueltas y cada una forzaba un repintado completo de la rejilla. */
        recalcular(filaEditada, cantidad) {
            const ultima = hotDiario.countRows() - 1;
            const cambios = [];

            // El valor recién tecleado se pasa aparte para no depender de si el
            // dataset ya lo tiene incorporado cuando corre afterChange.
            const proyectadaDe = (i) => {
                if (i === filaEditada) return cantidad;
                const c = parseInt(hotDiario.getDataAtCell(i, 18), 10);
                // Sin proyección, o con un valor que la validación rechaza.
                return isNaN(c) || c <= 0 ? null : c;
            };

            const proyectado = [];
            let sumaProyectado = 0;
            let sumaProyectada = 0;

            for (let i = 0; i < ultima; i++) {
                const p = proyectadaDe(i);
                const valor = p === null ? 0 : p * PRECIO_PROYECCION;
                proyectado.push(valor);
                sumaProyectado += valor;
                if (p !== null) sumaProyectada += p;

                const ejecutada = parseFloat(hotDiario.getDataAtCell(i, 16));
                cambios.push([i, 17, p === null || isNaN(ejecutada) ? '' : ejecutada - p]);
                cambios.push([i, 19, this.moneda.format(valor)]);
            }

            cambios.push([ultima, 18, sumaProyectada || '']);
            cambios.push([ultima, 19, this.moneda.format(sumaProyectado)]);

            const pct = (parte) => (sumaProyectado ? `% ${Math.trunc((parte / sumaProyectado) * 100)}` : '% 0');

            for (let i = 0; i < ultima; i++) {
                cambios.push([i, 20, pct(proyectado[i])]);
                cambios.push([i, 22, pct(this.aNumero(hotDiario.getDataAtCell(i, 21)))]);
            }
            cambios.push([ultima, 20, pct(sumaProyectado)]);
            cambios.push([ultima, 22, pct(this.aNumero(hotDiario.getDataAtCell(ultima, 21)))]);

            hotDiario.batch(() => hotDiario.setDataAtCell(cambios, 'programmatic'));
        },

        /* ------------------ Edición de la inspección industrial --------------- */
        async guardarInspeccion(cantidad, totalFinal) {
            try {
                await window.api(this.urls.guardarInspeccion, {
                    method: 'POST',
                    body: { totalFinal, fechaFila: hotDiario.getDataAtCell(0, 0), valor: cantidad },
                });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo guardar la inspección industrial' });
            }
        },

        alEditarIndustrial(cambios, origen) {
            if (origen !== 'edit' || !cambios) return;

            for (const [row, col, viejo, nuevo] of cambios) {
                if (col !== 2 || row !== 6) continue;

                if (!this.soloDigitos(nuevo)) {
                    window.Swal.fire({ icon: 'warning', title: 'Inspección industrial',
                                       text: 'Por favor, ingrese solo números.' });
                    hotResumen.setDataAtCell(row, col, viejo, 'programmatic');
                    continue;
                }

                const cantidad = parseInt(nuevo, 10) || 0;
                const precio = this.aNumero(hotResumen.getDataAtCell(row, 1));

                // Se repinta al momento y el guardado va detrás.
                this.recalcularResumen();
                this.guardarInspeccion(cantidad, cantidad * precio);
            }
        },

        /* -------------------------------- Excel ------------------------------- */
        exportar() {
            const filas = hotDiario?.getData() ?? [];
            const resumen = hotResumen?.getData() ?? [];

            if (filas.length === 0 || !this.hayDatos) {
                window.Swal.fire({ icon: 'warning', title: 'Advertencia',
                                   text: 'Por favor seleccione una opción antes de exportar' });
                return;
            }

            const cabeceras = hotDiario.getColHeader().map(h => String(h).replace(/<br>/g, ' '));
            const combinado = [cabeceras, ...filas, [], ...resumen];

            const hoja = XLSX.utils.aoa_to_sheet(combinado);
            const libro = XLSX.utils.book_new();
            XLSX.utils.book_append_sheet(libro, hoja, 'Reporte');
            XLSX.writeFile(libro, 'Reporte_de_produccion_diario.xlsx');
        },
    }));
});
</script>
