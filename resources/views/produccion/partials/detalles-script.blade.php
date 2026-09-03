<script>
/* Aplica min/max al input del editor de fecha.
   Hay que hacerlo ANTES de que el editor abra: Handsontable 18 usa un
   <input type="date"> nativo y su open() llama a showPicker(), que corre antes
   del hook afterBeginEditing. El reintento cubre que el input aún no exista. */
function ponerLimitesFecha(hot, limites, intentos = 12) {
    const editor = hot?.getActiveEditor?.();
    const input = editor?.TEXTAREA
        ?? hot?.rootElement?.querySelector('.handsontableInput[type="date"]');

    if (input) {
        for (const [nombre, valor] of Object.entries(limites)) input[nombre] = valor;
        return;
    }
    if (intentos > 0) requestAnimationFrame(() => ponerLimitesFecha(hot, limites, intentos - 1));
}

/* Las instancias de Handsontable viven fuera del estado de Alpine. */
let hotDetalles = null;
let hotDia = null;

document.addEventListener('alpine:init', () => {
    Alpine.data('detallesProduccion', ({ permiso, fechaInicioCorte, urls }) => ({
        permiso, fechaInicioCorte, urls,

        cargando: true,
        modal: null,

        /* Datos del corte que alimentan los colores. */
        diasFestivos: [],
        sabadoDobles: [],          // [{nombreDia, ccInspector}]
        fechas: [],                // [{dia, fecha}]
        totalDias: 0,
        idCorteDetalles: null,

        /* Modal del día */
        tituloDia: '',
        cantidadDobles: '',
        sinDatosDia: false,
        puedeAgregar: false,
        filasDia: [],
        datosDia: [],
        botonesDobles: [],
        seleccion: { row: null, col: null, fecha: null, cc: null, nombreDia: '',
                     nombreCompleto: '', tipoCelda: 'dia', cantInspecciones: 0 },

        contarSabado: '',
        errorSabado: '',
        guardandoSabado: false,

        agregando: false,
        errorAgregar: '',
        errores: {},
        municipios: { busqueda: '', lista: [], abierto: false },
        form: {},

        /* ------------------------------- Init -------------------------------- */
        async init() {
            this.resetForm();
            await this.cargarDatos();

            this.$watch('$store.ui.dark', () => {
                hotDetalles?.render();
                hotDia?.render();
            });

            // Al abrir "agregar" se oculta el modal del día y su contenedor pasa a
            // display:none; HOT conserva las medidas viejas y al volver queda
            // descuadrado. $nextTick no basta: la transición de entrada todavía
            // está corriendo y el contenedor aún no tiene su tamaño final.
            this.$watch('modal', (v) => {
                if (v !== 'dia' || !hotDia) return;
                const remedir = () => { hotDia?.refreshDimensions(); hotDia?.render(); };
                this.$nextTick(remedir);
                setTimeout(remedir, 260);   // después de la transición del modal
            });
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Devuelve la CLAVE semántica de la celda. El original comparaba cadenas
           de color con getComputedStyle para decidir qué botones mostrar; al
           separar el significado del color, la paleta puede cambiar (modo oscuro)
           sin romper la lógica de dobles. */
        claveCelda(instancia, row, col, value) {
            if (col === 0 || col === 1) return null;

            const columna = instancia.getColHeader(col);
            const cc = instancia.getDataAtCell(row, 0);
            let clave = null;

            if (columna !== 'META POR INSPECTOR' && columna !== 'DIAS LABORADOS') clave = 'dia';

            if (['MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES',
                 '4 O MAS RECINTOS', 'COMERCIALES'].includes(columna)) clave = 'resumen';

            if (columna === 'TOTAL' || columna === 'SUB TOTAL') clave = 'total';
            if (columna === 'SUB TOTAL' && value < 180) clave = 'malo';

            if (columna === 'PROMEDIO INDIVIDUAL') clave = value >= 8 ? 'bueno' : 'malo';

            if (this.diasFestivos.includes(columna)) clave = 'festivo';

            if (this.sabadoDobles.some(s => s.nombreDia === columna && s.ccInspector === cc)) {
                clave = 'sabado';
            }
            return clave;
        },

        paletaCeldas() {
            const oscuro = Alpine.store('ui').dark;
            return oscuro ? {
                dia:     ['#1e3a5f', '#dbeafe'],
                resumen: ['#4a3a1a', '#fde68a'],
                total:   ['#2a3170', '#c7d2fe'],
                malo:    ['#5c2020', '#fecaca'],
                bueno:   ['#1e4620', '#bbf7d0'],
                festivo: ['#1e4620', '#bbf7d0'],
                sabado:  ['#57431a', '#fef08a'],
            } : {
                dia:     ['rgb(215, 232, 255)', '#1e293b'],
                resumen: ['rgb(253, 234, 185)', '#1e293b'],
                total:   ['rgb(185, 196, 255)', '#1e293b'],
                malo:    ['rgb(255, 185, 185)', '#1e293b'],
                bueno:   ['rgb(147, 255, 134)', '#1e293b'],
                festivo: ['rgb(147, 255, 134)', '#1e293b'],
                sabado:  ['rgb(255, 240, 142)', '#1e293b'],
            };
        },

        registrarRenderer() {
            const self = this;
            /* handsontable.css marca toda celda readOnly con .htDimmed y le impone
               fondo y color con !important. Por eso el color va con prioridad. */
            Handsontable.renderers.registerRenderer('customStylesRenderer',
                function (instancia, TD, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer(instancia, TD, row, col, prop, value, cellProperties);

                    // Al no partirse, un nombre largo se recorta: se deja el
                    // completo en el tooltip.
                    if (col === 1) TD.title = value ?? '';

                    TD.style.removeProperty('background-color');
                    const clave = self.claveCelda(instancia, row, col, value);
                    if (!clave) return;

                    const [fondo, texto] = self.paletaCeldas()[clave];
                    TD.style.setProperty('background-color', fondo, 'important');
                    TD.style.setProperty('color', texto, 'important');
                });
        },

        /* --------------------------- Carga principal -------------------------- */
        /* `silencioso` refresca sin mostrar el velo: se usa tras guardar, para que
           la tabla se actualice sin tapar la pantalla ni parecer que se congela. */
        async cargarDatos(idCorte = null, { silencioso = false } = {}) {
            if (idCorte === 'detalles') idCorte = null;
            if (!silencioso) this.cargando = true;
            try {
                const r = await window.api(this.urls.datos + (idCorte ? `?idCorteDetalles=${idCorte}` : ''));
                if (r.error) { window.Swal.fire({ icon: 'warning', text: r.error }); return; }

                this.procesarRespuesta(r);
                this.registrarRenderer();

                // En los refrescos tras guardar se recargan los datos en sitio en
                // vez de reconstruir la tabla: reconstruirla devolvía el scroll al
                // principio y tiraba filtros y orden. Mismo remedio que en PQRS.
                if (silencioso && hotDetalles) this.refrescarEnSitio(r);
                else this.construirTabla(r);
            } catch (e) {
                console.error('Error fetching data:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Ocurrió un error al cargar los datos de la base de datos' });
            } finally {
                if (!silencioso) this.cargando = false;
            }
        },

        /* Traduce fechas a "Lunes 05" para poder cruzarlas con los encabezados. */
        nombreDia(fecha) {
            const d = new Date(fecha + 'T00:00:00');
            const s = d.toLocaleDateString('es-ES', { weekday: 'long', day: '2-digit' });
            return s.charAt(0).toUpperCase() + s.slice(1);
        },

        procesarRespuesta(r) {
            this.fechas = r.diasIntermedios.map((item, i) => ({
                dia: `${item.nombreDia} ${item.dias}`,
                fecha: r.fechasIntermedias[i],
            }));

            this.diasFestivos = (r.diasFestivos ?? []).map(f => this.nombreDia(f));
            this.idCorteDetalles = r.corte;

            const dobles = [];
            for (const entrada of r.sabadodobles ?? []) {
                for (const reg of entrada.datos ?? []) {
                    dobles.push({ nombreDia: this.nombreDia(reg.fecha), ccInspector: reg.cc_inspector });
                }
            }

            // Los dobles manuales llegan anidados un nivel más.
            let manuales;
            (r.sabadosDoblesManuales ?? []).forEach(item => { manuales = item; });
            for (const it of manuales ?? []) {
                for (const insp of it?.datos?.totalInspecciones ?? []) {
                    if (insp?.fecha) {
                        dobles.push({ nombreDia: this.nombreDia(insp.fecha),
                                      ccInspector: it.datos.cc_inspector });
                    }
                }
            }
            this.sabadoDobles = dobles;
        },

        /* Encabezados agrupados por mes + bloques fijos, como el original. */
        armarCabeceras(r) {
            const meses = r.diasIntermedios.map(i => i.nombreMes);
            const unicos = [...new Set(meses)];
            const conteo = r.diasIntermedios.reduce((acc, i) => {
                acc[i.nombreMes] = (acc[i.nombreMes] || 0) + 1; return acc;
            }, {});
            const grupos = unicos.map(m => ({ label: m, colspan: conteo[m] }));

            const columnasFinales = ['SUB TOTAL', 'MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES',
                '4 O MAS RECINTOS', 'COMERCIALES', 'TOTAL', 'RN TOTAL', 'DIAS LABORADOS',
                'PROMEDIO INDIVIDUAL', 'META POR INSPECTOR', '% CUMPLIMIENTO META'];

            const superior = [
                { label: '', colspan: 2 },
                ...grupos,
                { label: '', colspan: 7 },
                { label: '', colspan: 5 },
            ];
            const inferior = ['CC', 'INSPECTORES CONTRATO CALI',
                ...r.diasIntermedios.map(i => `${i.nombreDia} ${i.dias}`),
                ...columnasFinales];

            this.totalDias = grupos.reduce((a, g) => a + g.colspan, 0);
            return { superior, inferior, totalDias: this.totalDias };
        },

        construirTabla(r) {
            const contenedor = document.getElementById('detalles');
            if (!contenedor) return;

            const { superior, inferior, totalDias } = this.armarCabeceras(r);

            hotDetalles?.destroy();
            hotDetalles = new Handsontable(contenedor, {
                readOnly: true,
                manualColumnMove: false,
                rowHeaders: true,
                nestedHeaders: [superior, inferior],
                data: r.produccionInspector,
                dropdownMenu: true,
                filters: true,
                autoWrapRow: true,
                autoWrapCol: true,
                /* Sin esto los nombres largos parten en dos líneas y esa fila crece:
                   las columnas congeladas y el cuerpo se calculan por separado y
                   dejaban de cuadrar. Una línea por fila y alto uniforme. */
                wordWrap: false,
                rowHeights: 24,
                fixedColumnsStart: 2,
                /* +40px sobre el alto útil: con alto exacto la barra horizontal
                   se comía la última fila (PROMEDIO). */
                height: '660px',
                // Las dos primeras llevan ancho propio; el resto, el de su encabezado.
                colWidths: (i) => (i === 0 ? 110 : i === 1 ? 320 : window.anchoDeCabecera(inferior[i], contenedor)),
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                cells: () => ({ renderer: 'customStylesRenderer' }),
                afterOnCellCornerDblClick: () => this.abrirDia(),
            });
            window.registrarHot?.(hotDetalles);

            this.agregarResumen(totalDias);
        },

        /* Fila de sumas y fila de promedios al pie, igual que antes. */
        agregarResumen(totalDias) {
            const columnas = totalDias + 10;
            hotDetalles.setDataAtCell(hotDetalles.countRows(), 1, 'TOTAL');
            hotDetalles.setDataAtCell(hotDetalles.countRows(), 1, 'PROMEDIO');

            const sumas = [];
            const promedios = [];
            for (let i = 0; i < columnas; i++) {
                sumas.push({
                    destinationRow: hotDetalles.countRows() - 2,
                    destinationColumn: i + 2, sourceColumn: i + 2, type: 'sum',
                });
                promedios.push({
                    destinationRow: hotDetalles.countRows() - 1,
                    destinationColumn: i + 2, sourceColumn: i + 2, type: 'custom',
                    customFunction(endpoint) {
                        let suma = 0, cuenta = 0;
                        const [desde, hasta] = endpoint.ranges[0];
                        for (let j = desde; j <= hasta; j++) {
                            const v = hotDetalles.getDataAtCell(j, i + 2);
                            if (!isNaN(v) && v !== null && v !== '') { suma += parseFloat(v); cuenta++; }
                        }
                        return cuenta > 0 ? (suma / cuenta).toFixed(2) : '';
                    },
                });
            }
            hotDetalles.updateSettings({ columnSummary: [...sumas, ...promedios] });
        },

        refrescarEnSitio(r) {
            const plugOrden   = hotDetalles.getPlugin('columnSorting');
            const plugFiltros = hotDetalles.getPlugin('filters');

            const orden     = plugOrden?.getSortConfig();
            const filtros   = plugFiltros?.conditionCollection.exportAllConditions();
            const seleccion = hotDetalles.getSelected();
            const caja      = hotDetalles.rootElement.querySelector('.ht_master .wtHolder');
            const arriba    = caja ? caja.scrollTop : 0;
            const izquierda = caja ? caja.scrollLeft : 0;

            hotDetalles.loadData(r.produccionInspector);
            this.agregarResumen(this.totalDias);

            if (filtros && filtros.length) {
                plugFiltros.conditionCollection.importAllConditions(filtros);
                plugFiltros.filter();
            }
            if (orden && orden.length) plugOrden.sort(orden);

            if (seleccion && seleccion.length) {
                const [f1, c1, f2, c2] = seleccion[0];
                hotDetalles.selectCell(f1, c1, f2, c2, false, false);
            }
            if (caja) {
                // Un respiro para que HOT termine de dibujar antes de mover la barra.
                setTimeout(() => { caja.scrollTop = arriba; caja.scrollLeft = izquierda; }, 10);
            }
        },

        exportar() {
            hotDetalles?.getPlugin('exportFile').downloadFile('csv', { filename: 'produccion' });
        },

        /* ---------------------------- Modal del día --------------------------- */
        async abrirDia() {
            const sel = hotDetalles.getSelectedLast();
            if (!sel) return;
            const [row, col] = sel;
            const columna = hotDetalles.getColHeader(col);
            const fecha = this.fechas.find(f => f.dia === columna);
            if (!fecha) return;      // solo las columnas de día abren el detalle

            this.seleccion = {
                row, col,
                fecha: fecha.fecha,
                cc: hotDetalles.getDataAtCell(row, 0),
                nombreDia: columna,
                nombreCompleto: hotDetalles.getDataAtCell(row, 1),
                tipoCelda: this.claveCelda(hotDetalles, row, col,
                                           hotDetalles.getDataAtCell(row, col)) ?? 'dia',
                cantInspecciones: hotDetalles.getDataAtCell(row, col) || 0,
            };

            this.tituloDia = `Inspecciones del día ${columna} — ${this.seleccion.nombreCompleto}`;
            this.cantidadDobles = '';
            this.botonesDobles = [];
            this.modal = 'dia';

            await this.$nextTick();
            await this.cargarDia();
        },

        cerrarDia() {
            hotDia?.destroy();
            hotDia = null;
            this.modal = null;
        },

        async cargarDia() {
            const { fecha, cc } = this.seleccion;
            this.cargando = true;
            try {
                const urlDetalles = await (await fetch(
                    `${this.urls.obtenerDetalles}?fecha=${fecha}&cc_inspector=${cc}`)).text();
                const r = await window.api(urlDetalles);

                this.datosDia = r[0];
                this.filasDia = this.aFilas(r[0]);
                this.sinDatosDia = this.filasDia.length === 0;
                this.calcularBotonesDobles(r);
                this.construirTablaDia();

                // ¿Existe bitácora para ese día? Si no, no se puede agregar.
                const urlBitacoras = await (await fetch(
                    `${this.urls.obtenerBitacoras}?fecha=${this.aDdMmAaaa(fecha)}&cc_inspector=${cc}`)).text();
                const rb = await window.api(urlBitacoras).catch(() => ({ error: true }));
                this.puedeAgregar = !rb?.error;
            } catch (e) {
                console.error(e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Ocurrió un error al cargar los datos de la base de datos' });
            } finally {
                this.cargando = false;
            }
        },

        aDdMmAaaa(f) { const [a, m, d] = f.split('-'); return `${d}-${m}-${a}`; },

        aFilas(json) {
            const columnas = ['id', 'vence', 'nombre_completo', 'CC_OPERARIO', 'MUNICIPIO', 'FECHA',
                'No_ACTA', 'TIPO_TRABAJO', 'CONTRATO', 'ORDEN_TRABAJO', 'ORDEN_EXT', 'CATEGORIA',
                'RESULTADO_CIERRE', 'HORA_INICIO', 'HORA_FINAL', 'DURACION_INSP', '4_RECINTOS',
                'state', 'diseno_especial'];
            return Object.keys(json ?? {}).map(k => columnas.map(c => json[k][c]));
        },

        /* Antes se decidía leyendo el color de fondo de la celda; ahora se usa la
           clave semántica y las mismas condiciones sobre la respuesta. */
        calcularBotonesDobles(r) {
            const botones = [];
            const tipo = this.seleccion.tipoCelda;
            const esSabado = this.seleccion.nombreDia.split(' ')[0] === 'Sábado';
            const hayDato = this.seleccion.cantInspecciones !== '' &&
                            this.seleccion.cantInspecciones !== null;

            if (hayDato) {
                if (tipo === 'festivo') {
                    botones.push(r[2]
                        ? { id: 'contarDoblesFestivo', texto: 'Contar dobles',
                            icono: 'fa-plus', url: this.urls.contarDoblesFestivo }
                        : { id: 'noContarDoblesFestivo', texto: 'No contar dobles',
                            icono: 'fa-minus', url: this.urls.noContarDoblesFestivo });
                }

                if (esSabado) {
                    const sinManuales = (r[3]?.length ?? 0) === 0;
                    if (sinManuales && !r[2] && !r[1] && tipo === 'sabado') {
                        botones.push({ id: 'noContar', texto: 'No contar dobles',
                                       icono: 'fa-minus', url: this.urls.noContarDobles });
                    } else if (sinManuales && !r[2] && !r[1] && tipo !== 'sabado') {
                        botones.push({ id: 'abrirModalContarSabado', texto: 'Contar dobles',
                                       icono: 'fa-plus', modal: true });
                    } else if (r[1] && !r[2] && sinManuales && tipo === 'dia') {
                        botones.push({ id: 'contarDobles', texto: 'Contar dobles',
                                       icono: 'fa-plus', url: this.urls.contarDobles });
                    } else if (!sinManuales && !r[1] && !r[2] && tipo === 'sabado') {
                        this.cantidadDobles = `Inspecciones dobles: ${r[3][0][0]}`;
                        botones.push({ id: 'noContarDoblesSabado', texto: 'No contar dobles',
                                       icono: 'fa-minus', url: this.urls.noContarDoblesSabado });
                    }
                }

                if (r[4] !== 0) this.cantidadDobles = `Inspecciones dobles: ${r[4]}`;
                if (tipo === 'festivo' && !botones.some(b => b.id === 'contarDoblesFestivo')
                    && this.seleccion.cantInspecciones != 0) {
                    this.cantidadDobles = `Inspecciones dobles: ${this.seleccion.cantInspecciones}`;
                }
            }
            this.botonesDobles = botones;
        },

        async accionDobles(boton) {
            if (boton.modal) { this.contarSabado = ''; this.errorSabado = ''; this.modal = 'contarSabado'; return; }

            const { cc, fecha, nombreCompleto } = this.seleccion;
            try {
                await window.api(boton.url, { method: 'POST', body: { ccInspector: cc, fecha } });
                const dia = this.nombreDia(fecha);

                if (boton.id === 'noContar' || boton.id === 'noContarDoblesSabado') {
                    this.sabadoDobles = this.sabadoDobles.filter(
                        s => !(s.nombreDia === dia && s.ccInspector === cc));
                }

                const suma = boton.texto.startsWith('Contar');
                window.Swal.fire({
                    position: 'top-end', icon: 'success', toast: true, timer: 4000,
                    showConfirmButton: false,
                    title: `Día doble ${suma ? 'contado' : 'descontado'} para el inspector: ${nombreCompleto} día: ${dia}`,
                });

                this.cerrarDia();
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                console.error(e);
                window.Swal.fire('Error', 'No se pudo completar la solicitud.', 'error');
            }
        },

        async guardarContarSabado() {
            const cantidad = parseInt(this.contarSabado, 10);
            this.errorSabado = '';

            if (!cantidad) {
                this.errorSabado = 'Coloque al menos una inspección doble para contar';
                return;
            }
            if (cantidad > Number(this.seleccion.cantInspecciones)) {
                this.errorSabado = 'La cantidad de inspecciones a contar no puede ser mayor a la cantidad de inspecciones totales';
                return;
            }

            const { cc, fecha, nombreCompleto } = this.seleccion;
            this.guardandoSabado = true;
            try {
                await window.api(this.urls.contarDobles, {
                    method: 'POST',
                    body: { ccInspector: cc, fecha, diasContados: cantidad },
                });
                const dia = this.nombreDia(fecha);
                this.sabadoDobles.push({ nombreDia: dia, ccInspector: cc });

                window.Swal.fire({
                    position: 'top-end', icon: 'success', toast: true, timer: 4000,
                    showConfirmButton: false,
                    title: `Día doble contado para el inspector: ${nombreCompleto} día: ${dia}`,
                });

                this.cerrarDia();
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                console.error(e);
                this.errorSabado = 'No se pudo guardar.';
            } finally {
                this.guardandoSabado = false;
            }
        },

        /* --------------------------- Tabla del día ---------------------------- */
        construirTablaDia() {
            const contenedor = document.getElementById('contratos_dia');
            if (!contenedor) return;

            hotDia?.destroy();
            const self = this;

            hotDia = new Handsontable(contenedor, {
                data: this.filasDia,
                readOnly: true,
                manualColumnMove: false,
                rowHeaders: false,
                colHeaders: ['ID', 'VENCE', 'OPERARIO', 'CC OPERARIO', 'MUNICIPIO', 'FECHA', 'N° ACTA',
                    'TIPO TRABAJO', 'CONTRATO', 'ORDEN TRABAJO', 'ORDEN EXT', 'CATEGORIA',
                    'RESULTADO CIERRE', 'HORA INICIO', 'HORA FINAL', 'DURACION INSP',
                    '4 RECINTOS O MAS', 'ESTADO', 'ACCIONES'],
                columns: [
                    { type: 'numeric', readOnly: true },
                    {}, { type: 'text' },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'text' },
                    /* El rango va en los atributos min/max del input y no en la
                       opción datePickerConfig, que dejó de tener efecto cuando
                       Handsontable 18 cambió Pikaday por un input date nativo. */
                    { type: 'date', dateFormat: 'YYYY-MM-DD' },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { editor: 'select', selectOptions: ['RP 10444', 'RP 12161', 'RN 12162', 'SA 12164', 'SA 12163'] },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'numeric', validator: 'custom.numeric' },
                    { type: 'numeric', correctFormat: true },
                    { editor: 'select', selectOptions: ['RESIDENCIAL', 'COMERCIAL'] },
                    { editor: 'select', selectOptions: ['CERTIFICADA', 'CERTIFICADA CON NOVEDADES',
                        'INSPECCIONADA CON DEFECTO CRITICO VALLE', 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE'] },
                    /* Texto y no `time`: el tipo time reformatea a 12 h y convertía
                       la DURACIÓN (00:26) en una hora del día (12:26 AM). Las tres
                       columnas son de solo lectura, así que se muestran tal cual
                       las manda el servidor. */
                    { type: 'text' },
                    { type: 'text' },
                    { type: 'text' },
                    { type: 'text', validator: 'custom.text', allowInvalid: false },
                    {},
                    { renderer: (instancia, td, row) => self.pintarAcciones(td, row) },
                ],
                manualRowResize: true,
                autoWrapRow: true,
                autoWrapCol: true,
                licenseKey: 'non-commercial-and-evaluation',
                height: '460px',
                /* Alto fijo: los botones de ACCIONES solo se construyen cuando esa
                   columna entra en pantalla, y sin esto la tabla crecía de golpe
                   al desplazarse a la derecha. */
                rowHeights: 30,
                hiddenColumns: { columns: [0], indicators: false },

                /* Resaltados: se calculan por fila en vez de mutar el meta durante
                   el render, como hacía el original. Las clases son las del layout. */
                cells: (row, col) => {
                    const f = this.filasDia[row];
                    if (!f) return {};
                    if (f[1] === '60 meses') return { className: 'fila-60-meses' };
                    if (/^P/i.test(String(f[6] ?? ''))) return { className: 'fila-acta-p' };
                    if (col === 15 && String(f[15] ?? '') < '00:20') return { className: 'celda-amarilla' };
                    return {};
                },

                afterChange: (cambios, origen) => {
                    if (origen === 'loadData' || !cambios) return;
                    for (const [row, prop, viejo, nuevo] of cambios) {
                        if (viejo !== nuevo) this.guardarCelda(row, viejo, nuevo);
                    }
                },

                // Antes de open(), que es quien despliega el calendario nativo.
                // El cuerpo de bloque es obligatorio: devolver algo desde
                // beforeBeginEditing cancelaría la edición.
                beforeBeginEditing: (fila, columna) => { this.limitarFecha(columna); },
                afterBeginEditing: (fila, columna) => { this.limitarFecha(columna); },
            });
            window.registrarHot?.(hotDia);
        },

        /* La columna FECHA solo admite del inicio del corte hasta ayer. */
        limitarFecha(columna) {
            if (columna !== 5) return;
            ponerLimitesFecha(hotDia, {
                min: this.fechaInicioCorte,
                max: new Date(Date.now() - 86400000).toISOString().slice(0, 10),
            });
        },

        pintarAcciones(td, row) {
            td.innerHTML = '';
            if (this.permiso !== 1) return td;

            const fila = this.filasDia[row] ?? [];
            const estado = fila[17];
            const disenoEspecial = fila[18];

            const caja = document.createElement('div');
            caja.style.cssText = 'display:flex;gap:.25rem;justify-content:center;align-items:center';

            const boton = (texto, clase, fn) => {
                const b = document.createElement('button');
                b.type = 'button';
                b.textContent = texto;
                b.className = clase;
                b.style.cssText = 'border:0;border-radius:.375rem;padding:.15rem .5rem;font-size:10px;'
                    + 'font-weight:600;color:#fff;cursor:pointer';
                b.addEventListener('click', fn);
                return b;
            };

            const azul = 'background:#1f47e0', ambar = 'background:#d97706',
                  verde = 'background:#059669', rojo = 'background:#dc2626';

            const bEditar = boton('Editar', '', () => this.editarFila(row));
            bEditar.style.cssText += ';' + azul;
            caja.appendChild(bEditar);

            const bDiseno = boton(disenoEspecial === 1 ? 'Quitar diseño especial' : 'Diseño especial',
                '', () => this.alternarDiseno(row, disenoEspecial));
            bDiseno.style.cssText += ';' + ambar;
            caja.appendChild(bDiseno);

            const bEstado = boton(estado === 1 ? 'Descontar' : 'Contar', '',
                () => this.alternarConteo(row, estado === 1));
            bEstado.style.cssText += ';' + (estado === 1 ? rojo : verde);
            caja.appendChild(bEstado);

            td.appendChild(caja);
            return td;
        },

        editarFila(row) {
            const bloqueadas = [1, 2, 3, 4, 13, 14, 15, 17, 18];
            hotDia.updateSettings({
                cells: (r, c) => (r === row && !bloqueadas.includes(c) ? { readOnly: false } : {}),
            });
        },

        async guardarCelda(row, viejo, nuevo) {
            const id = this.filasDia[row]?.[0];
            if (!id) return;

            // El original ubicaba la columna buscando el valor viejo en el JSON crudo.
            let columna = null;
            for (const objeto of Object.values(this.datosDia ?? {})) {
                for (const [clave, valor] of Object.entries(objeto)) {
                    if (valor === viejo) columna = clave;
                }
            }

            try {
                await window.api(this.urls.actualizarFila.replace(':id', id), {
                    method: 'POST',
                    body: { payload: { row, prop: columna, oldValue: viejo, newValue: nuevo } },
                });
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el cambio.' });
            }
        },

        async alternarConteo(row, descontar) {
            const id = this.filasDia[row]?.[0];
            const r = await window.Swal.fire({
                title: '¿Estás seguro?',
                text: descontar ? '¡Se descontará de producción!' : '¡Se sumará a producción!',
                icon: 'warning', showCancelButton: true,
                confirmButtonText: descontar ? '¡Sí, desasociar!' : '¡Sí, asociar!',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed && !r.value) return;

            try {
                await window.api(this.urls.alternarEstado.replace(':id', id), { method: 'POST' });
                window.Swal.fire(descontar ? 'Desasociado!' : 'Asociado!',
                    descontar ? 'El registro ha sido descontado.' : 'El registro ha sido sumado.', 'success');
                await this.refrescarDia();
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                    text: descontar ? 'Ocurrió un error al descontar el registro'
                                    : 'Ocurrió un error al sumar el registro' });
            }
        },

        async alternarDiseno(row, actual) {
            const id = this.filasDia[row]?.[0];
            const titulo = actual ? 'Desactivar diseño especial' : 'Diseño especial';
            const r = await window.Swal.fire({
                title: titulo,
                text: actual ? '¿Desea desactivar el diseño especial?'
                             : '¿Desea agregar el contrato como un diseño especial?',
                icon: 'info', showCancelButton: true,
                confirmButtonText: '¡Sí!', cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed && !r.value) return;

            try {
                const res = await window.api(this.urls.disenoEspecial.replace(':id', id), { method: 'POST' });
                if (!res.success) throw new Error('sin éxito');
                window.Swal.fire(titulo, res.diseño_especial
                    ? 'Se ha agregado un diseño especial.'
                    : 'Se ha desactivado el diseño especial.', 'success');
                await this.refrescarDia();
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'Ocurrió un error al actualizar el diseño especial' });
            }
        },

        async refrescarDia() {
            const { fecha, cc } = this.seleccion;
            try {
                const r = await window.api(this.urls.detallesDia
                    .replace(':fecha', fecha).replace(':inspector', cc));
                this.datosDia = r[0];
                this.filasDia = this.aFilas(r[0]);
                this.sinDatosDia = this.filasDia.length === 0;

                const caja = hotDia?.rootElement.querySelector('.ht_master .wtHolder');
                const arriba = caja ? caja.scrollTop : 0;
                const izquierda = caja ? caja.scrollLeft : 0;
                hotDia?.loadData(this.filasDia);
                if (caja) {
                    setTimeout(() => { caja.scrollTop = arriba; caja.scrollLeft = izquierda; }, 10);
                }
            } catch (e) {
                console.error(e);
            }
        },

        /* ------------------------- Agregar inspección ------------------------- */
        resetForm() {
            this.form = {
                nombreInspector: '', cc: '', municipio: '', fecha: '', fechaMinima: '',
                acta: 'P', tipoTrabajo: '', contrato: ':', orden: '', categoria: '',
                horaInicio: '', horaFinal: '', recintos: 'NO', cantidadRecintos: '', resultado: '',
            };
            this.errores = {};
            this.errorAgregar = '';
            this.municipios = { busqueda: '', lista: [], abierto: false };
        },

        get esLineaMatriz() { return this.form.tipoTrabajo === 'FI-29 revisión periódica línea matriz'; },

        get duracionCalculada() {
            return this.calcularDuracion(this.form.horaInicio, this.form.horaFinal);
        },

        normalizarActa(v) {
            let s = String(v ?? '');
            if (!s.startsWith('P')) s = 'P' + s;
            return ('P' + s.slice(1).replace(/[^0-9]/g, '')).slice(0, 19);
        },
        normalizarContrato(v) {
            const s = String(v ?? '');
            return ':' + s.replace(/^:/, '').replace(/[^0-9]/g, '').slice(0, 18);
        },

        abrirAgregar() {
            this.resetForm();
            const minima = new Date();
            minima.setDate(minima.getDate() - 7);
            this.form.fechaMinima = minima.toISOString().slice(0, 10);
            this.form.fecha = new Date(this.seleccion.fecha).toISOString().slice(0, 10);
            this.form.nombreInspector = this.seleccion.nombreCompleto;
            this.form.cc = this.seleccion.cc;
            this.modal = 'agregar';
        },

        async buscarMunicipios() {
            const q = this.municipios.busqueda.trim();
            if (q.length < 2) { this.municipios.lista = []; this.municipios.abierto = false; return; }
            try {
                const data = await window.api(`${this.urls.municipios}?term=${encodeURIComponent(q)}`);
                this.municipios.lista = Object.values(data ?? {});
                this.municipios.abierto = true;
            } catch (e) {
                this.municipios.lista = [];
            }
        },

        calcularDuracion(inicio, fin) {
            if (!inicio || !fin) return '';
            const [hi, mi] = inicio.split(':').map(Number);
            const [hf, mf] = fin.split(':').map(Number);
            let dif = new Date(0, 0, 0, hf, mf) - new Date(0, 0, 0, hi, mi);
            if (dif < 0) dif += 86400000;                        // cruce de medianoche
            const h = String(Math.floor(dif / 3600000)).padStart(2, '0');
            const m = String(Math.floor((dif % 3600000) / 60000)).padStart(2, '0');
            return `${h}:${m}`;
        },

        validarAgregar() {
            const f = this.form;
            const e = {};
            const matriz = this.esLineaMatriz;

            if (!f.municipio) e.municipio = true;
            if (!f.acta || f.acta === 'P') e.acta = true;
            if (!f.tipoTrabajo) e.tipoTrabajo = true;
            if (!f.contrato || f.contrato === ':') e.contrato = true;
            if (!f.horaInicio) e.horaInicio = true;
            if (!f.horaFinal) e.horaFinal = true;
            if (!f.resultado) e.resultado = true;

            // Para línea matriz no se exigen orden, categoría ni recintos.
            if (!matriz) {
                if (!f.orden) e.orden = true;
                if (!f.categoria) e.categoria = true;
                if (f.recintos === 'SI' && !f.cantidadRecintos) e.cantidadRecintos = true;
            }

            this.errores = e;
            return Object.keys(e).length === 0;
        },

        async agregarInspeccion() {
            this.errorAgregar = '';
            if (!this.validarAgregar()) {
                window.Swal.fire({ position: 'top-end', icon: 'warning', toast: true, timer: 4000,
                                   showConfirmButton: false, title: 'Por favor complete todos los campos' });
                return;
            }

            const f = this.form;
            const [a, m, d] = f.fecha.split('-');
            const fechaFormateada = `${d}-${m}-${a.slice(-2)}`;

            const datos = [
                null,
                f.nombreInspector,
                f.cc,
                f.municipio,
                fechaFormateada,
                f.acta,
                f.tipoTrabajo,
                f.contrato,
                f.orden,
                f.tipoTrabajo === 'RP 12161' ? f.orden : null,   // ORDEN EXT
                f.categoria,
                f.resultado,
                f.horaInicio,
                f.horaFinal,
                this.calcularDuracion(f.horaInicio, f.horaFinal),
                f.cantidadRecintos === '' ? 'NO' : f.cantidadRecintos,
                1,
            ];

            this.agregando = true;
            try {
                const r = await window.api(this.urls.insertar, { method: 'POST', body: { data: datos } });
                if (r.error) { this.errorAgregar = r.error; return; }

                window.Swal.fire({ position: 'top-end', icon: 'success', toast: true, timer: 3000,
                                   showConfirmButton: false, title: r.ok });
                this.modal = 'dia';
                await this.refrescarDia();
                await this.cargarDatos(this.idCorteDetalles, { silencioso: true });
            } catch (e) {
                this.errorAgregar = e?.data?.error ?? 'No se pudo agregar la inspección.';
            } finally {
                this.agregando = false;
            }
        },
    }));

    /* Validadores del editor de la tabla del día. */
    if (typeof Handsontable !== 'undefined') {
        Handsontable.validators.registerValidator('custom.numeric',
            (v, cb) => cb(!isNaN(Number(v)) && v !== null && v !== ''));
        Handsontable.validators.registerValidator('custom.text',
            (v, cb) => cb(v !== '' && (v === 'NO' || !isNaN(Number(v)))));
    }
});
</script>
