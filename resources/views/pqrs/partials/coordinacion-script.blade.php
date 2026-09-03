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
        conteoContratos: {},
        totalFilas: 0,
        refrescando: false,
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
            this.construirTabla(dataFromPHP.map(mapearFila));

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

                    // Estado base
                    td.style.removeProperty('background-color');
                    td.style.setProperty('color', '#1e293b', 'important');
                    td.style.fontWeight = 'normal';
                    td.title = '';

                    // 1. Contratos repetidos
                    if (value && self.conteoContratos[value] > 1) {
                        td.style.setProperty('color', '#d32f2f', 'important');
                        td.style.fontWeight = 'bold';
                        td.title = `¡Atención! Este contrato está repetido ${self.conteoContratos[value]} veces.`;
                    }

                    // 2. Semáforo por recepción / días restantes
                    if (recepcion === 'ACCEDE' || recepcion === 'NO ACCEDE') {
                        pintar(td, '#90EE90');
                    } else if (recepcion === 'NO PROCEDENTE') {
                        pintar(td, '#83b7f1');
                    } else if (dias !== null && dias !== '') {
                        const diasNum = parseInt(dias, 10);
                        if (diasNum === 0)              pintar(td, '#ff9535');
                        else if (diasNum <= 0)          pintar(td, '#ff8493');
                        else if (diasNum === 2 || diasNum === 1) pintar(td, '#f8f849');
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

        configColumnas() {
            return colHeaders.map((header) => {
                if (!this.permisoEditar) {
                    if (header === 'OBSERVACION SUPERVISOR') return { type: 'text', readOnly: false };
                    return { readOnly: true };
                }
                if (header === 'FECHA SOLICITUD CIERRE') {
                    return { type: 'date', dateFormat: 'YYYY-MM-DD', readOnly: false };
                }
                if (header === 'ASIGNADO' || header === 'RESPONSABLE') {
                    return { type: 'dropdown', source: listaInspectores, readOnly: false };
                }
                if (header === 'SUPERVISOR') return { readOnly: true };
                if (header === 'RECEPCIÓN') {
                    return { type: 'dropdown',
                             source: ['', 'ACCEDE', 'NO ACCEDE', 'GDW', 'NO PROCEDENTE'],
                             readOnly: false };
                }
                if (header === 'OBSERVACIÓN GESTIÓN')  return { type: 'text', readOnly: false };
                if (header === 'CÓDIGO AUTORIZACIÓN')  return { type: 'numeric', readOnly: false };
                if (header === 'MOTIVO DE PQR') {
                    return { type: 'dropdown',
                             source: ['', 'Apelacion', 'Atencion brindada', 'Cobros ocasionados',
                                      'Deja daños', 'Demora prestacion servicio', 'Error legalizacion',
                                      'Inconforme con el proceso', 'Incumplimiento cita',
                                      'Presentacion personal', 'Solicitud de dineros', 'No aplica'],
                             readOnly: false };
                }
                if (header === 'INSTRUCCIONES CAMPO')   return { type: 'text', readOnly: false };
                if (header === 'OBSERVACION SUPERVISOR') return { type: 'text', readOnly: false };
                return { readOnly: true };
            });
        },

        construirTabla(filas) {
            const contenedor = document.getElementById('tabla');
            if (!contenedor || typeof Handsontable === 'undefined') {
                console.error('El contenedor para Handsontable no fue encontrado o la librería no está cargada.');
                return;
            }

            this.actualizarConteoContratos(filas);

            const sinIcono = [
                'CONTRATO', 'ASIGNADO', 'RESPONSABLE', 'FECHA ASIGNADO', 'SUPERVISOR',
                'RECEPCIÓN', 'FECHA RECEPCIÓN', 'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA',
                'FECHA LÍMITE', 'DÍAS RESTANTES', 'DÍAS FALTANTES',
            ];

            this.hot = new Handsontable(contenedor, {
                data: filas,
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

            const editables = ['ASIGNADO', 'RESPONSABLE', 'RECEPCIÓN', 'OBSERVACIÓN GESTIÓN',
                'CÓDIGO AUTORIZACIÓN', 'MOTIVO DE PQR', 'FECHA SOLICITUD CIERRE',
                'INSTRUCCIONES CAMPO', 'OBSERVACION SUPERVISOR'];

            // Nombre visible -> columna real de la base.
            const aCampoBD = {
                'RECEPCIÓN': 'RECEPCION',
                'OBSERVACIÓN GESTIÓN': 'OBSERVACION_GESTION',
                'CÓDIGO AUTORIZACIÓN': 'CODIGO_AUTORIZACION',
                'MOTIVO DE PQR': 'MOTIVO_DE_PQR',
                'FECHA SOLICITUD CIERRE': 'FECHA_SOLICITUD_CIERRE',
                'INSTRUCCIONES CAMPO': 'INSTRUCCIONES_CAMPO',
                'OBSERVACION SUPERVISOR': 'OBSERVACION_SUPERVISOR',
            };

            for (const [row, prop, oldValue, newValue] of changes) {
                const colIndex = typeof prop === 'number' ? prop : this.hot.propToCol(prop);
                const header = colHeaders[colIndex];
                if (!editables.includes(header) || oldValue === newValue) continue;

                this.guardarCelda({ row, colIndex, header,
                                    campo: aCampoBD[header] ?? header,
                                    oldValue, newValue });
            }
        },

        async guardarCelda({ row, colIndex, header, campo, oldValue, newValue }) {
            const orden    = this.hot.getDataAtCell(row, 0);
            const contrato = this.hot.getDataAtCell(row, 1);

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
                if (lote.length) this.hot.setDataAtCell(lote, 'programmatic');

            } catch (e) {
                const mensaje = this.mensajeError(e, 'Error guardando datos.');

                // Se revierte la celda y las fechas que dependían de ella.
                this.hot.setDataAtCell(row, colIndex, oldValue, 'programmatic');

                if (header === 'ASIGNADO' && !oldValue) {
                    this.hot.setDataAtCell([
                        [row, colHeaders.indexOf('FECHA ASIGNADO'), null],
                        [row, colHeaders.indexOf('SUPERVISOR'), null],
                    ], 'programmatic');
                }
                if (header === 'RECEPCIÓN' && !oldValue) {
                    this.hot.setDataAtCell(row, colHeaders.indexOf('FECHA RECEPCIÓN'), null, 'programmatic');
                }
                if (header === 'CÓDIGO AUTORIZACIÓN' && !oldValue) {
                    this.hot.setDataAtCell(row, colHeaders.indexOf('FECHA RESPUESTA'), null, 'programmatic');
                }

                window.Swal.fire({
                    icon: 'error', title: 'Error de Validación',
                    text: Array.isArray(mensaje) ? mensaje.join(' ') : mensaje,
                    toast: true, position: 'top-end', showConfirmButton: false, timer: 3000,
                });
            }
        },

        /* ------------------------ Refresco cada minuto ------------------------ */
        iniciarActualizacionAutomatica() {
            this.temporizador = setInterval(async () => {
                // No interrumpe al usuario si está editando una celda.
                if (!this.hot || (this.hot.getActiveEditor() && this.hot.getActiveEditor().isOpened())) return;

                this.refrescando = true;
                try {
                    const res = await window.api(this.urls.datosActualizados);
                    if (!res.data) return;

                    const nuevas = res.data.map(mapearFila);

                    const plugSort    = this.hot.getPlugin('columnSorting');
                    const plugFiltros = this.hot.getPlugin('filters');

                    const ordenActual   = plugSort.getSortConfig();
                    const filtrosActual = plugFiltros.conditionCollection.exportAllConditions();
                    const seleccion     = this.hot.getSelected();
                    const scroller      = this.hot.rootElement.querySelector('.ht_master .wtHolder');
                    const scrollTop     = scroller ? scroller.scrollTop : 0;
                    const scrollLeft    = scroller ? scroller.scrollLeft : 0;

                    this.actualizarConteoContratos(nuevas);
                    this.hot.loadData(nuevas);

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
