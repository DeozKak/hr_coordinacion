<script>
/* La instancia de Handsontable vive fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotCoordinacion = null;

const COORD_POR_PAGINA = 100;   // lo que devuelve el servidor por página

/* Orden de las columnas. La respuesta trae los registros como objeto con los
   campos por nombre; esta lista decide qué se ve y en qué orden. */
const COORD_CAMPOS = [
    'indice', 'orden', 'contrato', 'producto', 'numero_solicitud', 'tipo_solicitud', 'NIT_CC',
    'nombre_lugar', 'departamento', 'localidad', 'sector_operativo', 'direccion', 'consecutivo_ruta',
    'telefono', 'medidor', 'categoria', 'unidad_operativa', 'tipo_trabajo', 'fecha_asignacion',
    'observacion_solicitud',
    'orden_solicitud_externa', 'tipo_solicitud_externa', 'fecha_solicitud_externa',
    'observacion_externa', 'fecha_reasignacion_externa',
    'FECHA_AGENDAMIENTO', 'jornada', 'CELULAR', 'OBSERVACIONES', 'estado_programacion',
    'codigo_tecnico', 'fecha_asignacion_inspector',
    'estado_recepcion', 'fecha_recepcion', 'cantidad_vne', 'ultima_vne', 'fecha_ultima_vne',
    'inspector_ultima_vne', 'compilado_observacion', 'causa_cierre', 'fecha_solicitud_cierre',
    'num_acta', 'validacion_formato', 'observacion_rechazo',
    'dia_ingreso', 'tipo_orden', 'sede', 'grupo', 'subgrupo', 'meses', 'fecha_vence_certificado',
    'dias_ejecutar', 'cumplimiento_politicas', 'cartera', 'consumo', 'fecha_ult_cert',
    'estado_gestion', 'ult_comentario', 'nom_inspector', 'dias_gestion_actual', 'fecha_actual',
    'marca',
];

const COORD_TITULOS = [
    '#', 'Orden', 'Contrato', 'Producto', 'Numero solicitud', 'Tipo solicitud', 'Cedula', 'Nombre',
    'Departamento', 'Localidad', 'Barrio', 'Dirección', 'Consecutivo Ruta', 'Telefono', 'Medidor',
    'Categoria', 'Unidad', 'Tipo trabajo', 'Fecha asignación', 'Observación solicitud',
    'Orden externa', 'Tipo solicitud', 'Fecha solicitud', 'Observación externa', 'Fecha reasignación',
    'Fecha programación', 'Jornada', 'Telefono usuario', 'Descripción programacion',
    'Estado programación', 'Asignación inspector', 'Fecha asignación inspector',
    'Estado recepción', 'Fecha recepción', '# VNE', 'Estado ultima VNE', 'Fecha ultima VNE',
    'Inspector ultima VNE', 'Compilado observación', 'Causa cierre', 'Fecha solicitud de cierre',
    'Acta real', 'Validación formato', 'Observacion rechazo',
    'Día ingreso', 'Tipo orden', 'Sede', 'Grupo', 'Sub grupo', 'Meses', 'Fecha vence certificado',
    'Días para ejecutar', 'Cumplimiento politicas', 'Cartera', 'Consumo', 'Fecha ultimo certificado',
    'Estado gestion', 'Observacion OSF', 'Nombre inspector', 'Días gestion actual', 'Fecha actual',
    'Marca',
];

/* Bloques de la cabecera superior; `hasta` es la última columna de cada uno. */
const COORD_BLOQUES = [
    { hasta: 19, clave: 'g1', titulo: '1. ASIGNACIÓN BASE OSF' },
    { hasta: 24, clave: 'g2', titulo: '2. INFORMACIÓN COMPLEMENTARIA 12161' },
    { hasta: 29, clave: 'g3', titulo: '3. PROGRAMACIÓN DE ÓRDENES' },
    { hasta: 31, clave: 'g4', titulo: '4. ASIGNACIÓN INSPECTOR' },
    { hasta: 40, clave: 'g5', titulo: '5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO' },
    { hasta: 43, clave: 'g6', titulo: '6. GESTIÓN REALIZADA OFICINA' },
    { hasta: COORD_CAMPOS.length - 1, clave: 'g7', titulo: '7. FORMULACIÓN Y CÁLCULO' },
];

const cc = (nombre) => COORD_CAMPOS.indexOf(nombre);

document.addEventListener('alpine:init', () => {
    Alpine.data('coordinacion', ({ urls }) => ({
        urls,

        filtrosAbiertos: true,
        cargando: false,
        asignando: false,
        marcarTodos: false,
        modal: null,
        pagina: 1,
        total: 0,

        opcionesGrupo: [],
        opcionesSubgrupo: [],

        /* Fuentes de los desplegables de la rejilla; llegan con cada respuesta. */
        estadosProgramacion: [],
        tecnicos: [],
        causasCierre: [],

        filtros: {
            orden: [],
            orden_solicitud_externa: [],
            contrato: [],
            codigo_tecnico: [],
            localidad: '',
            sector_operativo: '',
            id_sede: [],
            id_grupo: '',
            id_subGrupo: '',
            diasInicio: '',
            diasFin: '',
        },

        impresion: { sede: '', tipoOrden: '', fechaAsigna: 'no', fecha: '', excel: false, pdf: false },
        errorImpresion: '',

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.construirTabla();
            this.$watch('$store.ui.dark', () => hotCoordinacion?.render());
            // Al cambiar de sede se recargan los grupos, como hacía TomSelect.
            this.$watch('filtros.id_sede', () => this.cargarGrupos());
            this.cargar();
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.total / COORD_POR_PAGINA)); },

        get hayFiltro() {
            return Object.values(this.filtros)
                .some(v => Array.isArray(v) ? v.length > 0 : String(v).trim() !== '');
        },

        /* Permite el signo menos delante: los días pueden ser negativos. */
        soloEnteros(valor) {
            const limpio = String(valor).replace(/[^0-9-]/g, '');
            return limpio.startsWith('-') ? '-' + limpio.slice(1).replace(/-/g, '')
                                          : limpio.replace(/-/g, '');
        },

        /* ------------------------------ Colores ------------------------------ */
        paleta() {
            return Alpine.store('ui').dark ? {
                g1: '#15803d', g2: '#6d28d9', g3: '#9a3412', g4: '#1d4ed8',
                g5: '#b91c1c', g6: '#7e22ce', g7: '#0e7490',
            } : {
                g1: '#bbf7d0', g2: '#ddd6fe', g3: '#fed7aa', g4: '#bfdbfe',
                g5: '#fecaca', g6: '#e9d5ff', g7: '#a5f3fc',
            };
        },

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
            return this.razon('#ffffff', fondo) >= this.razon('#0f172a', fondo) ? '#ffffff' : '#0f172a';
        },
        claveDe(columna) {
            return (COORD_BLOQUES.find(b => columna <= b.hasta) ?? COORD_BLOQUES.at(-1)).clave;
        },

        /* ------------------------------- Tabla ------------------------------- */
        cabeceras() {
            let desde = 0;
            const superior = COORD_BLOQUES.map((b) => {
                const fila = { label: b.titulo, colspan: b.hasta - desde + 1 };
                desde = b.hasta + 1;
                return fila;
            });
            return [superior, [...COORD_TITULOS]];
        },

        /* Sólo cinco columnas se editan; el resto es de sólo lectura. */
        columnas() {
            const editables = {
                estado_programacion: { type: 'dropdown', source: [...this.estadosProgramacion],
                                       strict: true, allowInvalid: false, filter: false },
                codigo_tecnico: { type: 'dropdown', source: [...this.tecnicos],
                                  strict: true, filter: false, className: 'tecnico' },
                causa_cierre: { type: 'dropdown', source: [...this.causasCierre],
                                strict: true, allowInvalid: false, filter: false },
                fecha_solicitud_cierre: { type: 'date', dateFormat: 'YYYY-MM-DD', correctFormat: true },
                marca: { type: 'checkbox' },
            };

            return COORD_CAMPOS.map(campo => editables[campo]
                ? { data: campo, readOnly: false, ...editables[campo] }
                : { data: campo, readOnly: true });
        },

        construirTabla() {
            const self = this;

            hotCoordinacion = new Handsontable(document.getElementById('tablaCoordinacion'), {
                data: [],
                nestedHeaders: this.cabeceras(),
                columns: this.columnas(),
                colHeaders: true,
                rowHeaders: false,
                height: '65vh',
                rowHeights: 26,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                fixedColumnsStart: 5,
                manualColumnResize: true,
                manualRowResize: true,
                licenseKey: 'non-commercial-and-evaluation',

                afterGetColHeader(columna, TH) {
                    if (columna < 0) return;
                    const fondo = self.paleta()[self.claveDe(columna)];
                    TH.style.setProperty('background-color', fondo, 'important');
                    TH.style.setProperty('color', self.contraste(fondo), 'important');
                    TH.style.fontWeight = '600';
                },

                afterChange: (cambios, origen) => this.alEditar(cambios, origen),
            });
            window.registrarHot?.(hotCoordinacion);
        },

        /* ------------------------------ Consulta ----------------------------- */
        /* El servidor espera cada filtro con una forma distinta: los campos de
           varios números como cadena separada por comas, inspector y sede como
           arreglo, grupo y sub grupo dentro de `datos`, y los días dentro de
           `dias`. */
        datosFormulario() {
            const f = this.filtros;
            const d = {};

            for (const campo of ['orden', 'orden_solicitud_externa', 'contrato']) {
                if (f[campo].length) d[campo] = f[campo].join(',');
            }
            if (f.codigo_tecnico.length) d.codigo_tecnico = [...f.codigo_tecnico];
            if (f.id_sede.length) d.id_sede = [...f.id_sede];
            if (f.localidad.trim()) d.localidad = f.localidad.trim();
            if (f.sector_operativo.trim()) d.sector_operativo = f.sector_operativo.trim();

            /* `datos` sólo viaja cuando hay grupo o sub grupo. El servidor cruza
               esos grupos con las sedes elegidas para sacar los municipios, y si
               va sin grupo ni sub grupo recorre todos: con la lista de sedes
               vacía la consulta no encuentra el municipio y revienta. */
            if (f.id_grupo || f.id_subGrupo) {
                d.datos = {
                    id_grupo: f.id_grupo ? [f.id_grupo] : [],
                    id_subGrupo: f.id_subGrupo ? [f.id_subGrupo] : [],
                };
            }

            if (f.diasInicio !== '' || f.diasFin !== '') {
                d.dias = { dia_inicio: f.diasInicio, dia_fin: f.diasFin };
            }
            return d;
        },

        /* Serializa el objeto anidado a la forma que Laravel vuelve a montar. */
        aParametros(objeto, prefijo, p = new URLSearchParams()) {
            for (const [clave, valor] of Object.entries(objeto)) {
                const nombre = `${prefijo}[${clave}]`;
                if (Array.isArray(valor)) {
                    for (const v of valor) p.append(`${nombre}[]`, v);
                } else if (valor && typeof valor === 'object') {
                    this.aParametros(valor, nombre, p);
                } else {
                    p.set(nombre, valor);
                }
            }
            return p;
        },

        revisarFiltros() {
            if ((this.filtros.id_grupo || this.filtros.id_subGrupo) && !this.filtros.id_sede.length) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la sede',
                                   text: 'Para filtrar por grupo o sub grupo hay que elegir también la sede.' });
                return false;
            }
            return true;
        },

        async cargar() {
            if (!this.revisarFiltros()) return;

            this.cargando = true;
            try {
                const datos = this.datosFormulario();
                const hayDatos = Object.keys(datos).length > 0;
                const p = hayDatos ? this.aParametros(datos, 'datosFormulario') : new URLSearchParams();
                p.set('pagina', this.pagina);

                const base = hayDatos ? this.urls.filtrar : this.urls.todo;
                const r = await window.api(`${base}?${p.toString()}`);

                this.aplicarFuentes(r);

                const filas = this.aFilas(r.data);
                this.total = Number(r.totalResults) || filas.length;

                /* Las fuentes de los desplegables cambian con cada respuesta, así
                   que las columnas se rehacen antes de cargar los datos. */
                hotCoordinacion.updateSettings({ columns: this.columnas() });
                hotCoordinacion.loadData(filas);
                hotCoordinacion.render();

                if (hayDatos && filas.length === 0) {
                    window.Swal.fire({ icon: 'warning', title: 'Advertencia',
                                       text: 'No se encontraron datos con los filtros seleccionados' });
                }
            } catch (e) {
                console.error('Error al consultar coordinación:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudieron obtener los datos de coordinación.' });
            } finally {
                this.cargando = false;
            }
        },

        aplicarFuentes(r) {
            this.estadosProgramacion = r.estadoProgramacion ?? [];
            this.tecnicos = (r.inspectores ?? []).map(i => `${i.id}-${i.nombres} ${i.apellidos}`);
            this.causasCierre = ['Seleccione...',
                ...(r.causasCierre ?? []).map(c => `${c.id}-${c.causa_cierre}`)];
        },

        aFilas(datos) {
            return Object.values(datos ?? {}).map((fila) => {
                const salida = {};
                for (const campo of COORD_CAMPOS) salida[campo] = fila[campo] ?? '';
                return salida;
            });
        },

        buscar() {
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
                orden: [], orden_solicitud_externa: [], contrato: [], codigo_tecnico: [],
                localidad: '', sector_operativo: '', id_sede: [], id_grupo: '', id_subGrupo: '',
                diasInicio: '', diasFin: '',
            };
            return this.buscar();
        },

        /* -------------------------- Grupos dependientes ----------------------- */
        async cargarGrupos() {
            this.filtros.id_grupo = '';
            this.filtros.id_subGrupo = '';
            this.opcionesGrupo = [];
            this.opcionesSubgrupo = [];
            if (!this.filtros.id_sede.length) return;

            try {
                const r = await window.api(
                    `${this.urls.grupos}?idSede=${encodeURIComponent(this.filtros.id_sede.join(','))}`);
                this.opcionesGrupo = (r.grupos ?? []).map(g => ({ value: g.id, label: g.grupo }));
            } catch (e) {
                console.error('No se pudieron cargar los grupos:', e);
            }
        },

        async cargarSubgrupos() {
            this.filtros.id_subGrupo = '';
            this.opcionesSubgrupo = [];
            if (!this.filtros.id_grupo) return;

            try {
                const r = await window.api(
                    `${this.urls.subgrupos}?idGrupo=${encodeURIComponent(this.filtros.id_grupo)}`);
                this.opcionesSubgrupo = (r.subGrupo ?? []).map(s => ({ value: s.id, label: s.subgrupo }));
            } catch (e) {
                console.error('No se pudieron cargar los sub grupos:', e);
            }
        },

        /* ------------------------------ Edición ------------------------------ */
        aviso(icono, titulo) {
            window.Swal.fire({ toast: true, position: 'top-end', icon: icono, title: titulo,
                               timer: 3000, showConfirmButton: false });
        },

        async alEditar(cambios, origen) {
            // 'masivo' es el marcado de todas las filas, que se guarda de una vez.
            if (!cambios || origen === 'programmatic' || origen === 'masivo') return;

            for (const [fila, prop, viejo, nuevo] of cambios) {
                const orden = hotCoordinacion.getSourceDataAtRow(fila)?.orden;
                if (!orden) continue;

                if (prop === 'codigo_tecnico') await this.guardarTecnico(fila, orden, nuevo);
                else if (prop === 'estado_programacion') await this.guardarEstado(orden, nuevo);
                else if (prop === 'causa_cierre') await this.guardarCausa(orden, nuevo);
                else if (prop === 'fecha_solicitud_cierre') await this.guardarFechaCierre(orden, nuevo);
                else if (prop === 'marca') await this.guardarMarca(orden);
            }
        },

        /* El desplegable trae "12-JUAN CARLOS PEREZ GOMEZ": se parte por el
           primer guion y el nombre se reordena a apellidos + nombres, que es el
           formato de la columna "Nombre inspector". */
        nombreInspector(resto) {
            const partes = String(resto).trim().split(/\s+/);
            return partes.length === 4
                ? `${partes[2]} ${partes[3]} ${partes[0]} ${partes[1]}`
                : `${partes[1] ?? ''} ${partes[2] ?? ''} ${partes[0] ?? ''}`.trim();
        },

        async guardarTecnico(fila, orden, valor) {
            const [codigo, ...resto] = String(valor ?? '').split('-');
            if (!codigo) return;

            const hoy = new Date();
            const fechaHoy = [hoy.getFullYear(),
                              String(hoy.getMonth() + 1).padStart(2, '0'),
                              String(hoy.getDate()).padStart(2, '0')].join('-');

            const escribir = (cod, fecha, nombre) => hotCoordinacion.batch(() =>
                hotCoordinacion.setDataAtRowProp([
                    [fila, 'codigo_tecnico', cod],
                    [fila, 'fecha_asignacion_inspector', fecha],
                    [fila, 'nom_inspector', nombre],
                ], 'programmatic'));

            escribir(codigo, fechaHoy, this.nombreInspector(resto.join('-')));

            try {
                const r = await window.api(this.urls.programacion, {
                    method: 'POST',
                    body: { codigoTecnico: codigo, ordenEnviar: orden },
                });
                if (String(r).trim() === '3') {
                    escribir('', '', '');
                    this.aviso('warning', 'El código del técnico es incorrecto');
                }
            } catch (e) {
                escribir('', '', '');
                this.aviso('error', 'No se pudo guardar el inspector');
            }
        },

        async guardarEstado(orden, valor) {
            if (!valor) return;
            try {
                await window.api(this.urls.programacion, {
                    method: 'POST', body: { estado: valor, ordenEnviar: orden },
                });
            } catch (e) {
                this.aviso('error', 'No se pudo guardar el estado de programación');
            }
        },

        async guardarCausa(orden, valor) {
            try {
                const r = await window.api(this.urls.causaCierre, {
                    method: 'POST', body: { causaCierre: valor, ordenEnviar: orden },
                });
                this.aviso(String(r).trim() === '1' ? 'success' : 'error',
                           String(r).trim() === '1' ? 'Causa de cierre registrada'
                                                    : 'Error al guardar la causa de cierre');
            } catch (e) {
                this.aviso('error', 'No se pudo guardar la causa de cierre');
            }
        },

        async guardarFechaCierre(orden, valor) {
            try {
                const r = await window.api(this.urls.fechaCierre, {
                    method: 'POST', body: { fechaSolicitudCierre: valor, ordenEnviar: orden },
                });
                this.aviso(String(r).trim() === '1' ? 'success' : 'error',
                           String(r).trim() === '1' ? 'Fecha de solicitud de cierre registrada'
                                                    : 'Error al guardar la fecha de solicitud de cierre');
            } catch (e) {
                this.aviso('error', 'No se pudo guardar la fecha de solicitud de cierre');
            }
        },

        async guardarMarca(orden) {
            try {
                await window.api(this.urls.marca, { method: 'POST', body: { ordenEnviar: orden } });
            } catch (e) {
                this.aviso('error', 'No se pudo guardar la marca');
            }
        },

        /* ---------------------------- Acciones masivas ------------------------ */
        async marcarTodas() {
            const marcar = this.marcarTodos;

            // 'masivo' evita que cada casilla dispare su propio guardado.
            hotCoordinacion.batch(() => {
                const filas = hotCoordinacion.countRows();
                const cambios = [];
                for (let f = 0; f < filas; f++) cambios.push([f, 'marca', marcar]);
                hotCoordinacion.setDataAtRowProp(cambios, 'masivo');
            });

            this.cargando = true;
            try {
                await window.api(this.urls.marcaMasiva, {
                    method: 'POST',
                    body: { datosFormulario: this.datosFormulario(), marca: marcar },
                });
                this.aviso('success', marcar ? 'Se marcaron todas las órdenes'
                                             : 'Se desmarcaron todas las órdenes');
            } catch (e) {
                this.aviso('error', 'Error al marcar las órdenes');
            } finally {
                this.cargando = false;
            }
        },

        async asignarPorCercania() {
            this.asignando = true;
            try {
                const r = await window.api(this.urls.cercania, { method: 'POST' });
                if (String(r).trim() === '1') {
                    window.Swal.fire({ icon: 'warning', title: 'Sin órdenes para asignar por cercanía' });
                    return;
                }
                await window.Swal.fire({ icon: 'success', title: 'Órdenes asignadas con éxito' });
                this.pagina = 1;
                await this.cargar();
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudieron asignar las órdenes por cercanía.' });
            } finally {
                this.asignando = false;
            }
        },

        /* --------------------------- Impresión masiva ------------------------- */
        impresionValida() {
            this.errorImpresion = '';
            if (!this.impresion.excel && !this.impresion.pdf) {
                this.errorImpresion = 'Por favor seleccione un método de exporte.';
                return false;
            }
            return true;
        },
    }));
});
</script>
