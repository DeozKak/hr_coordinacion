<script>
/* =====================================================================
   Anchos de las columnas de nombres.

   No son cifras a ojo: se midió el texto real contra la fuente que sirve
   la página (Plus Jakarta Sans 400) al tamaño de la rejilla compacta,
   11px. El nombre más largo de los 155 posibles —los 106 que hay en datos
   más la lista completa de inspectores activos, que es lo que ofrece el
   desplegable— es "111. CUBIDES CASTELLANOS MICHAEL EDUARDO" y ocupa
   254px. A eso se le suman el relleno de la celda (6px por lado) y, sólo
   en las columnas editables, los 28px de la flecha del desplegable
   (16 de icono + 8 de margen inicial + 4 de final).

   SUPERVISOR se queda como estaba: guarda nombres de usuario, y el más
   largo que existe hoy mide 185px, así que sus 210 ya sobran.
   ===================================================================== */
const ANCHO_NOMBRE_EDITABLE = 296;   // 254 + 12 de relleno + 28 de flecha
const ANCHO_NOMBRE_LECTURA  = 268;   // 254 + 12 de relleno
const ANCHO_RECEPCION       = 136;   // "NO PROCEDENTE" (94) + 12 + 28
const ANCHO_MOTIVO          = 184;   // "Demora prestacion servicio" (143) + 12 + 28

/* =====================================================================
   Mapeo fila BD -> arreglo de la tabla.
   El orden DEBE coincidir con `colHeaders`; lo usan tanto la carga inicial
   como el refresco automático de datos-actualizados.
   ===================================================================== */
function mapearFila(row) {
    return [
        row.NUMERO_ORDEN, row.CONTRATO, row.CEDULA, row.NOMBRE,
        row.DESC_DEPART, row.DESC_LOCALIDAD, row.BARRIO, row.DIRECCION,
        row.DESC_CATEGORIA, row.COD_UNIDAD_OPER, row.DESC_TIPO_TRABAJO,
        row.FECHA_ASIGNACION, row.OBSERVACION_SOLICITUD, row.FECHA_CIERRE_ULTIMA,
        row.OBSERVACIÓN_CIERRE_ULTIMA, row.TIPO_TRABAJO_CIERRE_ULTIMA,
        row.DESC_CAUSAL_CIERRE_ULTIMA, row.FECHA_ASIGNACIÓN_ULTIMA,
        row.OBSERVACIÓN_ASIGNACIÓN_ULTIMA, row.GESTIÓN_ASIGNACIÓN_ULTIMA,
        row.TIPO_TRABAJO_ASIGNACIÓN_ULTIMA, row.MOTIVO_DE_PQR, row.RESPONSABLE,
        row.ASIGNADO, row.SUPERVISOR, row.FECHA_ASIGNADO,
        row.TECNICO_AGENDADO, row.FECHA_AGENDAMIENTO,
        row.INSTRUCCIONES_CAMPO, row.OBSERVACION_SUPERVISOR, row.RECEPCION,
        row.FECHA_RECEPCION, row.FECHA_SOLICITUD_CIERRE, row.OBSERVACION_GESTION,
        row.CODIGO_AUTORIZACION, row.FECHA_RESPUESTA, row.FECHA_LIMITE,
        row.DIAS_FALTANTES,
    ];
}

document.addEventListener('alpine:init', () => {
    Alpine.data('coordinacionPqrs', ({ permisoEditar, urls }) => ({
        permisoEditar, urls,

        hot: null,
        hotHistorico: null,

        /* Las filas, en el componente y no sólo dentro de Handsontable: en móvil
           no hay rejilla y las tarjetas se pintan de aquí. */
        filas: [],
        desplegadas: {},          // números de orden con su ficha abierta
        conteoContratos: {},
        totalFilas: 0,
        refrescando: false,
        /* Firma de lo que hay pintado ahora mismo. Viaja en cada sondeo para
           que el servidor pueda contestar "sin cambios" en vez de la tabla. */
        firmaDatos: '',
        ultimaActualizacion: '',
        temporizador: null,

        modal: null,
        verMas: '',

        cargar:     { enviando: false, error: '', nombres: { asignadas: '', cerradas: '', html: '' } },
        gdw:        { pendientes: false, fecha: '', enviando: false, error: '' },
        supervisor: { lista: [], seleccionado: '', cargando: false, exportando: false, error: '' },
        historico:  { orden: '', contrato: '', fechaInicio: '', fechaFin: '',
                      buscando: false, exportando: false, vacio: false, total: 0, error: '',
                      colHeaders: [], data: [] },

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.registrarRenderers();
            this.filas = dataFromPHP.map(mapearFila);
            this.vigilarAncho();
            if (this.hayRejilla()) this.construirTabla();

            // Un solo listener delegado para los botones "ver más" de la tabla:
            // el original añadía uno por celda en cada repintado.
            this.$el.addEventListener('click', (e) => {
                const btn = e.target.closest('.ver-mas-btn');
                if (!btn) return;
                e.stopPropagation();
                this.verMas = btn._valor ?? '';
                this.modal = 'verMas';
            });

            this.iniciarActualizacionAutomatica();
            this.marcarActualizacion();
        },

        cerrar() { this.modal = null; },

        marcarActualizacion() {
            this.ultimaActualizacion = new Date().toLocaleTimeString('es-CO',
                { hour: '2-digit', minute: '2-digit' });
        },

        mensajeError(e, respaldo) {
            const d = e?.data ?? e;
            if (d && typeof d === 'object') {
                if (typeof d.error === 'string') return d.error;
                if (typeof d.mensaje === 'string') return d.mensaje;
                if (d.errors && typeof d.errors === 'object') return Object.values(d.errors).flat();
                if (d.error && typeof d.error === 'object') return Object.values(d.error).flat();
                if (typeof d.message === 'string') return d.message;
            }
            return e instanceof Error ? e.message : respaldo;
        },

        descargar(url) {
            const a = document.createElement('a');
            a.href = url;
            a.setAttribute('download', '');
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
        },

        /* El color del semáforo, o null si a esta fila no le toca ninguno.
           Vive fuera del renderizador porque las tarjetas de móvil pintan el
           mismo semáforo y no pasan por Handsontable. */
        colorDelSemaforo(recepcion, dias) {
            if (recepcion === 'ACCEDE' || recepcion === 'NO ACCEDE') return '#90EE90';
            if (recepcion === 'NO PROCEDENTE')                       return '#83b7f1';
            if (dias === null || dias === '')                        return null;

            const diasNum = parseInt(dias, 10);
            if (diasNum === 0)                  return '#ff9535';
            if (diasNum <= 0)                   return '#ff8493';
            if (diasNum === 2 || diasNum === 1) return '#f8f849';
            return null;
        },

        /* ---------------------------- Renderizadores -------------------------- */
        registrarRenderers() {
            const self = this;

            /* handsontable.css (v15+) trae:
                   .handsontable .htDimmed { color: … !important; background-color: … !important }
               y HOT marca como htDimmed TODA celda de solo lectura. Como esta tabla
               es readOnly salvo unas pocas columnas, ese !important pisaba los
               colores en línea del semáforo: por eso hay que escribirlos también
               con prioridad. */
            const pintar = (td, fondo, texto) => {
                if (fondo) td.style.setProperty('background-color', fondo, 'important');
                if (texto) td.style.setProperty('color', texto, 'important');
            };

            Handsontable.renderers.registerRenderer('contratoRenderer',
                function (instance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);

                    const recepcionCol = colHeaders.indexOf('RECEPCIÓN');
                    const diasCol      = colHeaders.indexOf('DÍAS RESTANTES');
                    const recepcion    = instance.getDataAtCell(row, recepcionCol);
                    const dias         = instance.getDataAtCell(row, diasCol);

                    td.style.removeProperty('background-color');
                    td.style.fontWeight = 'normal';
                    td.title = '';

                    const fondo = self.colorDelSemaforo(recepcion, dias);

                    /* Los cinco colores del semáforo son claros en los dos modos,
                       así que encima el texto va oscuro siempre. Sin fondo propio
                       manda el tema, que ya sabe de claro y oscuro: antes aquí se
                       fijaba #1e293b a pelo, que es justo el fondo de la tabla en
                       oscuro, y los contratos sin color desaparecían. */
                    if (fondo) {
                        pintar(td, fondo, '#1e293b');
                    } else {
                        td.style.setProperty('color', 'var(--ht-read-only-color)', 'important');
                    }

                    // Contratos repetidos, por encima de lo anterior.
                    if (value && self.conteoContratos[value] > 1) {
                        pintar(td, null, fondo ? '#d32f2f' : 'var(--ht-alerta-color)');
                        td.style.fontWeight = 'bold';
                        td.title = `¡Atención! Este contrato está repetido ${self.conteoContratos[value]} veces.`;
                    }
                });

            Handsontable.renderers.registerRenderer('verMasRenderer',
                function (instance, td, row, col, prop, value, cellProperties) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);

                    if (!value || typeof value !== 'string' || value.length <= 40) return;

                    // Se construye con el DOM y no con innerHTML: un valor con
                    // comillas rompía el title="" de la plantilla anterior.
                    td.textContent = '';
                    const caja = document.createElement('div');
                    caja.style.cssText = 'display:flex;align-items:center;gap:.25rem;width:100%';

                    const texto = document.createElement('span');
                    texto.style.cssText = 'flex:1;min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap';
                    texto.textContent = value;
                    texto.title = value;

                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'ver-mas-btn';
                    boton.style.cssText = 'flex:none;border:0;background:transparent;cursor:pointer;padding:0 2px;color:#1f47e0';
                    boton.title = 'Ver información completa';
                    boton.innerHTML = '<i class="fas fa-eye"></i>';
                    boton._valor = value;

                    caja.append(texto, boton);
                    td.appendChild(caja);
                });
        },

        /* ------------------------------- Tabla ------------------------------- */
        actualizarConteoContratos(filas) {
            const idx = colHeaders.indexOf('CONTRATO');
            const conteo = {};
            if (idx !== -1) {
                for (const fila of filas) {
                    const contrato = fila[idx];
                    if (contrato) conteo[contrato] = (conteo[contrato] || 0) + 1;
                }
            }
            this.conteoContratos = conteo;
            this.totalFilas = filas.length;
        },

        /**
         * Qué se puede editar, de qué tipo y contra qué columna de la base.
         *
         * Es la única definición: antes lo mismo estaba repartido en tres listas
         * —los tipos de columna de la rejilla, la lista de cabeceras editables de
         * `alCambiar` y el mapa `aCampoBD`—, y añadir un campo obligaba a tocar
         * las tres. Ahora las tres salen de aquí, y también los controles de las
         * tarjetas de móvil, que si no habrían sido una cuarta.
         *
         * `campo` sólo se declara cuando difiere de la cabecera: ASIGNADO y
         * RESPONSABLE se llaman igual en pantalla y en la base.
         */
        camposEditables() {
            return [
                { header: 'MOTIVO DE PQR', campo: 'MOTIVO_DE_PQR', tipo: 'lista',
                  opciones: ['', 'Apelacion', 'Atencion brindada', 'Cobros ocasionados',
                             'Deja daños', 'Demora prestacion servicio', 'Error legalizacion',
                             'Inconforme con el proceso', 'Incumplimiento cita',
                             'Presentacion personal', 'Solicitud de dineros', 'No aplica'] },
                { header: 'RESPONSABLE', tipo: 'lista', opciones: listaInspectores },
                { header: 'ASIGNADO', tipo: 'lista', opciones: listaInspectores },
                { header: 'INSTRUCCIONES CAMPO', campo: 'INSTRUCCIONES_CAMPO', tipo: 'texto' },
                { header: 'OBSERVACION SUPERVISOR', campo: 'OBSERVACION_SUPERVISOR', tipo: 'texto' },
                { header: 'RECEPCIÓN', campo: 'RECEPCION', tipo: 'lista',
                  opciones: ['', 'ACCEDE', 'NO ACCEDE', 'GDW', 'NO PROCEDENTE'] },
                { header: 'FECHA SOLICITUD CIERRE', campo: 'FECHA_SOLICITUD_CIERRE', tipo: 'fecha' },
                { header: 'OBSERVACIÓN GESTIÓN', campo: 'OBSERVACION_GESTION', tipo: 'texto' },
                { header: 'CÓDIGO AUTORIZACIÓN', campo: 'CODIGO_AUTORIZACION', tipo: 'numero' },
            ].map(c => ({ ...c, campo: c.campo ?? c.header }));
        },

        /**
         * Los que esta persona puede tocar de verdad.
         *
         * Sin el permiso fino sólo queda la observación del supervisor, igual que
         * decide el controlador: la lista blanca del servidor manda, y esto sólo
         * evita ofrecer en pantalla lo que allí se va a rechazar.
         */
        camposPermitidos() {
            const todos = this.camposEditables();

            return this.permisoEditar
                ? todos
                : todos.filter(c => c.header === 'OBSERVACION SUPERVISOR');
        },

        configColumnas() {
            const permitidos = new Map(this.camposPermitidos().map(c => [c.header, c]));
            const tipoDeHot = { lista: 'dropdown', texto: 'text', numero: 'numeric', fecha: 'date' };

            return colHeaders.map((header) => {
                const campo = permitidos.get(header);
                if (!campo) return { readOnly: true };

                const columna = { type: tipoDeHot[campo.tipo], readOnly: false };
                if (campo.tipo === 'lista') columna.source = campo.opciones;
                if (campo.tipo === 'fecha') columna.dateFormat = 'YYYY-MM-DD';

                return columna;
            });
        },

        construirTabla() {
            const contenedor = document.getElementById('tabla');
            if (!contenedor || typeof Handsontable === 'undefined') {
                console.error('El contenedor para Handsontable no fue encontrado o la librería no está cargada.');
                return;
            }

            this.actualizarConteoContratos(this.filas);

            const sinIcono = [
                'CONTRATO', 'ASIGNADO', 'RESPONSABLE', 'FECHA ASIGNADO', 'SUPERVISOR',
                'RECEPCIÓN', 'FECHA RECEPCIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                'FECHA LÍMITE', 'DÍAS RESTANTES', 'DÍAS FALTANTES',
            ];

            this.hot = new Handsontable(contenedor, {
                /* Se le entrega el arreglo crudo: Handsontable guarda la
                   referencia y la lee celda a celda en cada repintado, y hacerlo
                   a través del Proxy de Alpine cuesta en una rejilla de 38
                   columnas. Las tarjetas de móvil sí van por el Proxy, pero allí
                   no hay rejilla con la que competir. */
                data: Alpine.raw(this.filas),
                colHeaders: colHeaders,
                columns: this.configColumnas(),
                rowHeaders: true,
                readOnly: true,
                height: '650px',
                width: '100%',
                filters: true,
                columnSorting: {
                    initialConfig: { column: colHeaders.indexOf('DÍAS RESTANTES'), sortOrder: 'asc' },
                },
                fixedColumnsStart: 2,
                dropdownMenu: true,
                manualColumnResize: true,
                manualRowResize: true,
                contextMenu: false,
                autoWrapRow: false,
                autoWrapCol: false,
                wordWrap: false,
                licenseKey: 'non-commercial-and-evaluation',

                // Anchos ajustados a la cabecera compacta de 9px: el original
                // calculaba 8px por letra para una fuente bastante mayor.
                colWidths: (index) => {
                    const h = colHeaders[index];
                    // Los tres que llevan nombre de inspector. Los dos primeros
                    // son desplegables y necesitan sitio para la flecha.
                    if (h === 'ASIGNADO' || h === 'RESPONSABLE') return ANCHO_NOMBRE_EDITABLE;
                    if (h === 'TÉCNICO PROXIMA PROGRAMACION') return ANCHO_NOMBRE_LECTURA;
                    if (h === 'SUPERVISOR') return 210;
                    if (h === 'RECEPCIÓN') return ANCHO_RECEPCION;
                    if (h === 'MOTIVO DE PQR') return ANCHO_MOTIVO;
                    if (h === 'OBSERVACIÓN SOLICITUD' || h === 'OBSERVACIÓN GESTIÓN'
                        || h === 'OBSERVACION SUPERVISOR' || h === 'INSTRUCCIONES CAMPO') return 260;
                    return Math.max(84, (h.length * 6) + 30);
                },

                cells: (row, col) => {
                    const h = colHeaders[col];
                    if (h === 'CONTRATO') return { renderer: 'contratoRenderer' };
                    if (!sinIcono.includes(h)) return { renderer: 'verMasRenderer' };
                    return {};
                },

                afterGetColHeader: (col, TH) => {
                    TH.style.color = 'white';
                    TH.style.fontWeight = 'bold';
                    if (col >= 0 && col <= 12)       TH.style.backgroundColor = '#5ab15d';
                    else if (col >= 13 && col <= 20) TH.style.backgroundColor = '#BA68C8';
                    else if (col >= 21 && col <= 27) TH.style.backgroundColor = '#4F81BD';
                    else if (col >= 28 && col <= 29) TH.style.backgroundColor = '#595858';
                    else if (col >= 30 && col <= 35) TH.style.backgroundColor = '#ed5e5b';
                    else if (col >= 36 && col <= 37) {
                        TH.style.backgroundColor = '#ffa43b';
                        TH.style.color = 'black';
                    }
                },

                afterChange: (changes, source) => this.alCambiar(changes, source),
            });
            window.registrarHot?.(this.hot);
        },

        /* --------------------------- Guardado en línea ------------------------ */
        alCambiar(changes, source) {
            if (source === 'loadData' || source === 'programmatic' || !changes) return;

            const editables = new Map(this.camposEditables().map(c => [c.header, c]));

            for (const [row, prop, oldValue, newValue] of changes) {
                const colIndex = typeof prop === 'number' ? prop : this.hot.propToCol(prop);
                const header = colHeaders[colIndex];
                const campo = editables.get(header);
                if (!campo || oldValue === newValue) continue;

                this.guardarCelda({ row, colIndex, header, campo: campo.campo, oldValue, newValue });
            }
        },

        async guardarCelda({ row, colIndex, header, campo, oldValue, newValue }) {
            const orden    = this.leerCelda(row, 0);
            const contrato = this.leerCelda(row, 1);

            try {
                const res = await window.api(this.urls.actualizar, {
                    method: 'POST',
                    body: { orden, contrato, campo, valor: newValue },
                });

                // El servidor devuelve las fechas derivadas que se pintan en la fila.
                const lote = [];
                if (header === 'ASIGNADO') {
                    lote.push([row, colHeaders.indexOf('FECHA ASIGNADO'), res.fecha_asignado || null]);
                    lote.push([row, colHeaders.indexOf('SUPERVISOR'), res.supervisor || null]);
                }
                if (header === 'RECEPCIÓN') {
                    lote.push([row, colHeaders.indexOf('FECHA RECEPCIÓN'), res.fecha_extra || null]);
                }
                if (header === 'CÓDIGO AUTORIZACIÓN') {
                    lote.push([row, colHeaders.indexOf('FECHA RESPUESTA'), res.fecha_extra || null]);
                }
                if (lote.length) this.escribirCeldas(lote);

            } catch (e) {
                const mensaje = this.mensajeError(e, 'Error guardando datos.');

                // Se revierte la celda y las fechas que dependían de ella.
                this.escribirCeldas([[row, colIndex, oldValue]]);

                if (header === 'ASIGNADO' && !oldValue) {
                    this.escribirCeldas([
                        [row, colHeaders.indexOf('FECHA ASIGNADO'), null],
                        [row, colHeaders.indexOf('SUPERVISOR'), null],
                    ]);
                }
                if (header === 'RECEPCIÓN' && !oldValue) {
                    this.escribirCeldas([[row, colHeaders.indexOf('FECHA RECEPCIÓN'), null]]);
                }
                if (header === 'CÓDIGO AUTORIZACIÓN' && !oldValue) {
                    this.escribirCeldas([[row, colHeaders.indexOf('FECHA RESPUESTA'), null]]);
                }

                window.Swal.fire({
                    icon: 'error', title: 'Error de Validación',
                    text: Array.isArray(mensaje) ? mensaje.join(' ') : mensaje,
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                });
            }
        },

        /* ---------------------------- Vista de móvil -------------------------- */
        /**
         * ¿Toca rejilla o fichas?
         *
         * Lo decide el CSS, no un número repetido aquí: se mira si el contenedor
         * de la rejilla está visible. `offsetParent` es null cuando él o alguno
         * de sus padres está en `display:none`, que es lo que le hace el
         * `hidden lg:block` de la vista.
         */
        hayRejilla() {
            return !!document.getElementById('tabla')?.offsetParent;
        },

        /* Rejilla y fichas no conviven: Handsontable mide su contenedor al
           construirse y con `display:none` nace con ancho cero. */
        vigilarAncho() {
            let temporizador;

            window.addEventListener('resize', () => {
                clearTimeout(temporizador);
                temporizador = setTimeout(() => this.sincronizarVista(), 150);
            });
        },

        sincronizarVista() {
            const toca = this.hayRejilla();

            if (!toca && this.hot) {
                this.hot.destroy();
                this.hot = null;
                return;
            }

            if (toca && !this.hot) this.construirTabla();
        },

        /**
         * ¿Hay una edición a medias que no se debe pisar?
         *
         * En la rejilla se le pregunta al editor de celda. En móvil no hay editor:
         * el equivalente es que el foco esté dentro de una ficha, porque el
         * refresco automático llega cada minuto y borraría lo que se esté
         * escribiendo.
         */
        editando() {
            if (this.hot) return !!(this.hot.getActiveEditor() && this.hot.getActiveEditor().isOpened());

            return !!document.activeElement?.closest('[data-ficha-pqrs]');
        },

        /* Lectura y escritura de celdas que sirven con rejilla y sin ella. */
        leerCelda(fila, columna) {
            return this.hot ? this.hot.getDataAtCell(fila, columna) : this.filas[fila]?.[columna];
        },

        escribirCeldas(lote) {
            if (this.hot) {
                this.hot.setDataAtCell(lote, 'programmatic');
                return;
            }

            for (const [fila, columna, valor] of lote) {
                if (this.filas[fila]) this.filas[fila][columna] = valor;
            }
        },

        /* Índice de una cabecera; se memoriza porque las fichas lo piden por
           cada campo y por cada fila. */
        columnaDe(header) {
            this._columnas ??= {};
            this._columnas[header] ??= colHeaders.indexOf(header);

            return this._columnas[header];
        },

        valorDe(fila, header) {
            return fila[this.columnaDe(header)];
        },

        /**
         * Las fichas de móvil, en el mismo orden que la rejilla: por días
         * restantes ascendente, que es lo más urgente arriba.
         */
        get fichas() {
            const dias = this.columnaDe('DÍAS RESTANTES');

            return this.filas
                .map((fila, indice) => ({ fila, indice }))
                .sort((a, b) => (Number(a.fila[dias]) || 0) - (Number(b.fila[dias]) || 0));
        },

        /* Los datos de sólo lectura que identifican la queja. */
        fichaDatos(fila) {
            return [
                ['Nombre', this.valorDe(fila, 'NOMBRE')],
                ['Dirección', this.valorDe(fila, 'DIRECCIÓN')],
                ['Barrio', this.valorDe(fila, 'BARRIO')],
                ['Localidad', this.valorDe(fila, 'LOCALIDAD')],
                ['Tipo de trabajo', this.valorDe(fila, 'TIPO TRABAJO')],
                ['Fecha límite', this.valorDe(fila, 'FECHA LÍMITE')],
                ['Supervisor', this.valorDe(fila, 'SUPERVISOR')],
                ['Fecha asignado', this.valorDe(fila, 'FECHA ASIGNADO')],
                ['Fecha recepción', this.valorDe(fila, 'FECHA RECEPCIÓN')],
                ['Fecha respuesta', this.valorDe(fila, 'FECHA RESPUESTA')],
                ['Observación solicitud', this.valorDe(fila, 'OBSERVACIÓN SOLICITUD')],
            ].filter(([, valor]) => valor !== null && valor !== '' && valor !== undefined);
        },

        /* El mismo semáforo que pinta la columna CONTRATO en la rejilla. */
        estiloContrato(fila) {
            const fondo = this.colorDelSemaforo(
                this.valorDe(fila, 'RECEPCIÓN'),
                this.valorDe(fila, 'DÍAS RESTANTES')
            );

            const repetido = this.contratoRepetido(fila);

            if (!fondo) return repetido ? 'color:var(--ht-alerta-color);font-weight:700' : '';

            return `background-color:${fondo};color:${repetido ? '#d32f2f' : '#1e293b'}`
                 + (repetido ? ';font-weight:700' : '');
        },

        contratoRepetido(fila) {
            const contrato = this.valorDe(fila, 'CONTRATO');

            return !!contrato && this.conteoContratos[contrato] > 1;
        },

        alternarFicha(orden) {
            this.desplegadas[orden] = !this.desplegadas[orden];
        },

        /**
         * Guarda un campo editado desde una ficha.
         *
         * Se escribe primero y se guarda después, igual que hace la rejilla: si
         * el servidor rechaza, `guardarCelda` revierte la celda —y las fechas que
         * dependían de ella— y avisa. Por eso los controles se enlazan con
         * `:value` y no con `x-model`: la reversión tiene que poder devolver el
         * control a su valor anterior.
         */
        async editarCampo(indice, campo, evento) {
            const columna = this.columnaDe(campo.header);
            const anterior = this.filas[indice][columna];
            const nuevo = evento.target.value;

            if (String(anterior ?? '') === String(nuevo ?? '')) return;

            this.filas[indice][columna] = nuevo;

            await this.guardarCelda({
                row: indice, colIndex: columna, header: campo.header,
                campo: campo.campo, oldValue: anterior, newValue: nuevo,
            });
        },

        /* ------------------------ Refresco cada minuto ------------------------ */
        iniciarActualizacionAutomatica() {
            this.temporizador = setInterval(async () => {
                // No interrumpe al usuario si está editando.
                if (this.editando()) return;

                this.refrescando = true;
                try {
                    const p = this.firmaDatos
                        ? `?firma=${encodeURIComponent(this.firmaDatos)}`
                        : '';
                    const res = await window.api(this.urls.datosActualizados + p);

                    // Nada cambió: se deja la rejilla como está y se sale.
                    if (res.sin_cambios) { this.marcarActualizacion(); return; }
                    if (!res.data) return;

                    this.firmaDatos = res.firma ?? '';
                    const nuevas = res.data.map(mapearFila);

                    this.actualizarConteoContratos(nuevas);
                    this.filas = nuevas;

                    /* En móvil las tarjetas se repintan solas al cambiar `filas`.
                       Todo lo que sigue es preservar orden, filtros, scroll y
                       selección de la rejilla, y sin rejilla no hay nada de eso:
                       la comprobación va antes de tocar `this.hot`, no después. */
                    if (!this.hot) { this.marcarActualizacion(); return; }

                    const plugSort    = this.hot.getPlugin('columnSorting');
                    const plugFiltros = this.hot.getPlugin('filters');

                    const ordenActual   = plugSort.getSortConfig();
                    const filtrosActual = plugFiltros.conditionCollection.exportAllConditions();
                    const seleccion     = this.hot.getSelected();
                    const scroller      = this.hot.rootElement.querySelector('.ht_master .wtHolder');
                    const scrollTop     = scroller ? scroller.scrollTop : 0;
                    const scrollLeft    = scroller ? scroller.scrollLeft : 0;

                    this.hot.loadData(Alpine.raw(this.filas));

                    if (filtrosActual && filtrosActual.length > 0) {
                        plugFiltros.conditionCollection.importAllConditions(filtrosActual);
                        plugFiltros.filter();
                    }
                    if (ordenActual && ordenActual.length > 0) {
                        plugSort.sort(ordenActual);
                    } else {
                        const c = colHeaders.indexOf('DÍAS RESTANTES');
                        if (c !== -1) plugSort.sort({ column: c, sortOrder: 'asc' });
                    }

                    if (seleccion && seleccion.length > 0) {
                        const [r1, c1, r2, c2] = seleccion[0];
                        this.hot.selectCell(r1, c1, r2, c2, false, false);
                    }
                    if (scroller) {
                        setTimeout(() => {
                            scroller.scrollTop = scrollTop;
                            scroller.scrollLeft = scrollLeft;
                        }, 10);
                    }

                    this.marcarActualizacion();
                } catch (e) {
                    console.error('Error actualizando la tabla en segundo plano:', e);
                } finally {
                    this.refrescando = false;
                }
            }, 60000);
        },

        /* ---------------------------- Cargar datos ---------------------------- */
        abrirCargar() {
            this.cargar = { enviando: false, error: '',
                            nombres: { asignadas: '', cerradas: '', html: '' } };
            this.modal = 'cargar';
        },

        async enviarCargar() {
            const datos = new FormData();
            const asignadas = this.$refs.asignadas.files[0];
            const cerradas  = this.$refs.cerradas.files[0];

            if (asignadas) datos.append('Asignadas', asignadas);
            if (cerradas)  datos.append('Cerradas', cerradas);
            for (const f of this.$refs.html.files) datos.append('archivos_html[]', f);

            this.cargar.enviando = true;
            this.cargar.error = '';
            try {
                await window.api(this.urls.importar, { method: 'POST', body: datos });
                this.cerrar();
                window.location.reload();
            } catch (e) {
                this.cargar.error = this.mensajeError(e, 'No se pudieron procesar los archivos.');
                this.cargar.enviando = false;
            }
        },

        /* --------------------------- Exportar a GDW --------------------------- */
        abrirExportarGDW() {
            this.gdw = { pendientes: false, fecha: '', enviando: false, error: '' };
            this.modal = 'gdw';
        },

        async enviarExportarGDW() {
            const datos = new FormData();
            if (this.gdw.pendientes) datos.append('exportar_pendientes', 'on');
            else datos.append('fecha_exportacion', this.gdw.fecha);

            this.gdw.enviando = true;
            this.gdw.error = '';
            try {
                const res = await window.api(this.urls.exportarGDW, { method: 'POST', body: datos });

                if (!res.success) {
                    this.gdw.error = res.mensaje || 'Hubo un problema al realizar la búsqueda.';
                    return;
                }

                window.Swal.fire({
                    icon: 'success', position: 'top-end', toast: true, timer: 4000,
                    showConfirmButton: false,
                    title: `Se procesaron ${res.cantidad_encontrada} registros. Descargando…`,
                });

                if (res.url_punto_interes) this.descargar(res.url_punto_interes);
                if (res.url_tareas) setTimeout(() => this.descargar(res.url_tareas), 500);
                this.cerrar();
            } catch (e) {
                this.gdw.error = this.mensajeError(e, 'Error al intentar procesar la solicitud.');
            } finally {
                this.gdw.enviando = false;
            }
        },

        /* ----------------------- Exportar por supervisor ---------------------- */
        async abrirExportarSupervisor() {
            this.supervisor.error = '';
            this.supervisor.seleccionado = '';
            this.modal = 'supervisor';

            if (this.supervisor.lista.length > 0) return;   // sólo se consulta una vez

            this.supervisor.cargando = true;
            try {
                const data = await window.api(this.urls.supervisores);
                this.supervisor.lista = data.map(s => s.name);
            } catch (e) {
                console.error('Error cargando supervisores:', e);
                this.supervisor.error = 'No se pudieron cargar los supervisores.';
            } finally {
                this.supervisor.cargando = false;
            }
        },

        async exportarSupervisor() {
            if (!this.supervisor.seleccionado) {
                this.supervisor.error = 'Por favor seleccione un supervisor.';
                return;
            }
            this.supervisor.exportando = true;
            this.supervisor.error = '';
            try {
                const res = await window.api(this.urls.exportarSuper, {
                    method: 'POST',
                    body: { supervisor_name: this.supervisor.seleccionado },
                });
                if (res.downloadUrl) {
                    this.descargar(res.downloadUrl);
                    this.cerrar();
                }
            } catch (e) {
                this.supervisor.error = this.mensajeError(e, 'Error al exportar.');
            } finally {
                this.supervisor.exportando = false;
            }
        },

        /* ------------------------------ Histórico ----------------------------- */
        abrirHistorico() {
            this.modal = 'historico';
            // La tabla se dibuja dentro de un contenedor que estaba oculto:
            // hay que recalcular tamaños al mostrarlo.
            if (this.hotHistorico) setTimeout(() => this.hotHistorico.render(), 300);
        },

        async buscarHistorico() {
            const h = this.historico;
            h.error = '';
            h.vacio = false;

            const params = new URLSearchParams();
            if (h.orden.trim())    params.set('orden', h.orden.trim());
            if (h.contrato.trim()) params.set('contrato', h.contrato.trim());
            if (h.fechaInicio)     params.set('fecha_inicio', h.fechaInicio);
            if (h.fechaFin)        params.set('fecha_fin', h.fechaFin);

            if ([...params.keys()].length === 0) {
                window.Swal.fire('Atención', 'Debe llenar al menos un criterio de búsqueda', 'warning');
                return;
            }

            if (this.hotHistorico) { this.hotHistorico.destroy(); this.hotHistorico = null; }
            h.total = 0;
            h.buscando = true;

            try {
                const data = await window.api(`${this.urls.historico}?${params.toString()}`);
                if (data.success && data.data.length > 0) {
                    h.total = data.data.length;
                    await this.$nextTick();
                    this.construirTablaHistorico(data.data);
                } else {
                    h.vacio = true;
                }
            } catch (e) {
                console.error('Error fetching historico:', e);
                h.error = 'Ocurrió un error al consultar el histórico.';
            } finally {
                h.buscando = false;
            }
        },

        construirTablaHistorico(filas) {
            const cabeceras = [
                'NÚMERO ORDEN', 'CONTRATO', 'CÉDULA', 'NOMBRE', 'DEPARTAMENTO',
                'LOCALIDAD', 'BARRIO', 'DIRECCIÓN', 'CATEGORÍA',
                'COD UNIDAD OPERATIVA', 'TIPO TRABAJO', 'FECHA ASIGNACIÓN',
                'OBSERVACIÓN SOLICITUD', 'FECHA CIERRE ÚLTIMA', 'OBSERVACIÓN CIERRE ÚLTIMA',
                'TIPO TRABAJO CIERRE ÚLTIMA', 'CAUSAL CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA',
                'OBSERVACIÓN ASIGNACIÓN ÚLTIMA', 'GESTIÓN ASIGNACIÓN ÚLTIMA',
                'TIPO TRABAJO ASIGNACIÓN ÚLTIMA', 'MOTIVO PQR',
                'RESPONSABLE', 'ASIGNADO', 'SUPERVISOR', 'FECHA ASIGNADO', 'RECEPCIÓN',
                'FECHA RECEPCIÓN', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                'FECHA LEGALIZACIÓN', 'CAUSAL LEGALIZACIÓN', 'OBSERVACIÓN LEGALIZACIÓN',
            ];

            const datos = filas.map(row => [
                row.NUMERO_ORDEN, row.CONTRATO, row.CEDULA, row.NOMBRE, row.DESC_DEPART,
                row.DESC_LOCALIDAD, row.BARRIO, row.DIRECCION, row.DESC_CATEGORIA,
                row.COD_UNIDAD_OPER, row.DESC_TIPO_TRABAJO, row.FECHA_ASIGNACION,
                row.OBSERVACION_SOLICITUD, row.FECHA_CIERRE_ULTIMA, row.OBSERVACIÓN_CIERRE_ULTIMA,
                row.TIPO_TRABAJO_CIERRE_ULTIMA, row.DESC_CAUSAL_CIERRE_ULTIMA, row.FECHA_ASIGNACIÓN_ULTIMA,
                row.OBSERVACIÓN_ASIGNACIÓN_ULTIMA, row.GESTIÓN_ASIGNACIÓN_ULTIMA,
                row.TIPO_TRABAJO_ASIGNACIÓN_ULTIMA, row.MOTIVO_DE_PQR,
                row.RESPONSABLE, row.ASIGNADO, row.SUPERVISOR, row.FECHA_ASIGNADO, row.RECEPCION,
                row.FECHA_RECEPCION, row.OBSERVACION_GESTION, row.CODIGO_AUTORIZACION, row.FECHA_RESPUESTA,
                row.FECHA_LEGALIZACION, row.DESC_CAUSAL_LEGALIZACION, row.OBSERVACION_LEGALIZACION,
            ]);

            const sinIcono = [
                'CONTRATO', 'CÉDULA', 'NÚMERO ORDEN', 'FECHA ASIGNACIÓN',
                'FECHA CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA', 'FECHA ASIGNADO',
                'FECHA RECEPCIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA', 'FECHA LEGALIZACIÓN',
            ];

            this.historico.colHeaders = cabeceras;

            this.hotHistorico = new Handsontable(document.getElementById('tabla_historico'), {
                data: datos,
                colHeaders: cabeceras,
                columns: cabeceras.map(() => ({ readOnly: true })),
                rowHeaders: true,
                width: '100%',
                height: '400px',
                filters: true,
                dropdownMenu: true,
                manualColumnResize: true,
                autoWrapRow: false,
                autoWrapCol: false,
                wordWrap: false,
                /* Aquí la tabla entera es de sólo lectura, así que las columnas
                   de nombres no reservan sitio para ninguna flecha. El resto
                   conserva los 130 de siempre. */
                colWidths: (index) => {
                    const h = cabeceras[index];
                    if (h === 'RESPONSABLE' || h === 'ASIGNADO') return ANCHO_NOMBRE_LECTURA;
                    if (h === 'SUPERVISOR') return 190;
                    if (h === 'RECEPCIÓN') return ANCHO_RECEPCION - 28;
                    // Aquí la cabecera es 'MOTIVO PQR', sin el 'DE'.
                    if (h === 'MOTIVO PQR') return ANCHO_MOTIVO - 28;
                    return 130;
                },
                fixedColumnsStart: 2,
                licenseKey: 'non-commercial-and-evaluation',
                cells: (row, col) => (!sinIcono.includes(cabeceras[col]) ? { renderer: 'verMasRenderer' } : {}),
                afterGetColHeader: (col, TH) => {
                    TH.style.backgroundColor = '#8064A2';
                    TH.style.color = 'white';
                    TH.style.fontWeight = 'bold';
                },
            });
            window.registrarHot?.(this.hotHistorico);
        },

        async exportarHistorico() {
            if (!this.hotHistorico) {
                this.historico.error = 'No hay datos cargados para exportar.';
                return;
            }

            // Se exporta lo que se ve: respeta filtros y orden de la tabla.
            const filas = [];
            const nFilas = this.hotHistorico.countRows();
            const nCols  = this.hotHistorico.countCols();
            for (let r = 0; r < nFilas; r++) {
                const fila = [];
                for (let c = 0; c < nCols; c++) fila.push(this.hotHistorico.getDataAtCell(r, c));
                filas.push(fila);
            }

            if (filas.length === 0) {
                this.historico.error = 'La tabla está vacía después de aplicar los filtros.';
                return;
            }

            this.historico.exportando = true;
            this.historico.error = '';
            try {
                const res = await window.api(this.urls.exportarHistorico, {
                    method: 'POST',
                    body: { datos_tabla: filas },
                });
                if (res.downloadUrl) this.descargar(res.downloadUrl);
            } catch (e) {
                this.historico.error = this.mensajeError(e, 'No se pudo exportar.');
            } finally {
                this.historico.exportando = false;
            }
        },

        destroy() {
            if (this.temporizador) clearInterval(this.temporizador);
            if (this.hot) this.hot.destroy();
            if (this.hotHistorico) this.hotHistorico.destroy();
        },
    }));
});
</script>
