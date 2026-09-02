<script>
/* La instancia de Handsontable vive fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotRelaciones = null;

/* Columnas de la rejilla, por nombre. */
const REL = ['municipio', 'grupo', 'subgrupo', 'barrio', 'inspectores'];
const cr = (nombre) => REL.indexOf(nombre);

document.addEventListener('alpine:init', () => {
    Alpine.data('zonificacion', ({ municipios, barrios, sedes, zonas, opciones, urls, puedeGestionar }) => ({
        municipios,
        barrios,
        sedes,
        zonas,
        opciones,
        urls,
        puedeGestionar,

        buscaMunicipio: '',
        buscaBarrio: '',

        filtros: { municipio: '', grupo: '', subgrupo: '', barrio: '', inspector: '' },
        filas: [],
        barriosDisponibles: [''],
        buscando: false,
        actualizando: false,
        errorBusqueda: '',

        modal: null,
        error: '',
        invalidos: [],
        guardando: false,

        municipio: { id: null, nombre: '', id_sede: '', id_zona: '' },
        barrio: { id: null, barrio: '' },
        sede: { id: null, nombre: '' },
        zona: { id: null, nombre: '' },

        detalle: { grupo: '', subgrupo: '', inspectores: [] },
        cargandoInspectores: false,

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.$watch('$store.ui.dark', () => hotRelaciones?.render());
        },

        get hayResultados() { return this.filas.length > 0; },

        get hayFiltro() {
            return Object.values(this.filtros).some(v => v !== '' && v !== null);
        },

        /* --------------------------- Listas laterales ------------------------- */
        coincide(texto, q) { return String(texto ?? '').toLowerCase().includes(q); },

        get municipiosFiltrados() {
            const q = this.buscaMunicipio.trim().toLowerCase();
            if (!q) return this.municipios;
            return this.municipios.filter(m =>
                this.coincide(m.nombre, q) || this.coincide(m.sede, q) || this.coincide(m.zona, q));
        },

        get barriosFiltrados() {
            const q = this.buscaBarrio.trim().toLowerCase();
            if (!q) return this.barrios;
            return this.barrios.filter(b => this.coincide(b.barrio, q) || this.coincide(b.municipio, q));
        },

        /* ------------------------------ Ventanas ----------------------------- */
        cerrar() {
            // La bandera primero: lo demás son campos que la ventana ya no
            // muestra y no deben cambiar mientras se está cerrando.
            this.modal = null;
            this.error = '';
            this.invalidos = [];
        },

        claseCampo(campo) {
            return this.invalidos.includes(campo)
                ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                : '';
        },

        /* El registro se COPIA en vez de volver a pedirlo al servidor: la ficha
           ya viaja completa con la tabla, incluidos id_sede e id_zona. */
        abrirMunicipio(m = null) {
            this.municipio = m
                ? { id: m.id, nombre: m.nombre, id_sede: String(m.id_sede ?? ''), id_zona: String(m.id_zona ?? '') }
                : { id: null, nombre: '', id_sede: '', id_zona: '' };
            this.error = '';
            this.invalidos = [];
            this.modal = 'municipio';
        },

        abrirBarrio(b = null) {
            this.barrio = b ? { id: b.id, barrio: b.barrio } : { id: null, barrio: '' };
            this.error = '';
            this.invalidos = [];
            this.modal = 'barrio';
        },

        abrirSede(s = null) {
            this.sede = s ? { id: s.id, nombre: s.nombre } : { id: null, nombre: '' };
            this.error = '';
            this.invalidos = [];
            this.modal = 'sede';
        },

        abrirZona(z = null) {
            this.zona = z ? { id: z.id, nombre: z.nombre } : { id: null, nombre: '' };
            this.error = '';
            this.invalidos = [];
            this.modal = 'zona';
        },

        /* Mensaje del servidor cuando lo hay; si no, uno genérico. */
        motivo(e, porDefecto) {
            return e?.data?.error ?? e?.data?.message ?? porDefecto;
        },

        aviso(titulo) {
            window.Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: titulo,
                               timer: 3000, showConfirmButton: false });
        },

        /* ------------------------------ Municipios ---------------------------- */
        async guardarMunicipio() {
            this.invalidos = [];
            if (!this.municipio.nombre.trim()) this.invalidos.push('nombre');
            if (!this.municipio.id_sede) this.invalidos.push('sede');
            if (!this.municipio.id_zona) this.invalidos.push('zona');
            if (this.invalidos.length) {
                this.error = 'El nombre, la sede y la zona son obligatorios.';
                return;
            }

            const cuerpo = {
                nombre: this.municipio.nombre.trim(),
                sede: Number(this.municipio.id_sede),
                zona: Number(this.municipio.id_zona),
            };

            this.guardando = true;
            this.error = '';
            try {
                const editando = this.municipio.id;
                const r = editando
                    ? await window.api(this.urls.updateMunicipio.replace('__ID__', editando),
                                       { method: 'PUT', body: cuerpo })
                    : await window.api(this.urls.storeMunicipio, { method: 'POST', body: cuerpo });

                const fila = {
                    id: r.ok.id,
                    nombre: r.ok.nombre,
                    id_sede: r.ok.id_sede,
                    id_zona: r.ok.id_zona,
                    sede: r.ok.sede?.nombre ?? 'Sin asignar',
                    zona: r.ok.zona?.nombre ?? 'Sin asignar',
                    activo: Number(r.ok.status) === 1,
                };

                if (editando) {
                    const i = this.municipios.findIndex(m => m.id === fila.id);
                    if (i !== -1) Object.assign(this.municipios[i], fila);
                } else {
                    this.municipios.push(fila);
                    this.opciones.municipio = [...this.opciones.municipio,
                                               { value: fila.id, label: fila.nombre }];
                }

                this.cerrar();
                this.aviso(r.success ?? 'Municipio guardado');
            } catch (e) {
                this.error = this.motivo(e, 'No se pudo guardar el municipio.');
            } finally {
                this.guardando = false;
            }
        },

        async cambiarEstado(registro, tabla) {
            try {
                const r = await window.api(this.urls.cambiarEstado, {
                    method: 'POST',
                    body: { id: registro.id, table: tabla },
                });
                // Ojo: Boolean("0") es true; el JSON puede traer el tinyint como texto.
                registro.activo = Number(r.success.status) === 1;
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: this.motivo(e, 'No se pudo cambiar el estado.') });
            }
        },

        /* -------------------------------- Barrios ----------------------------- */
        async guardarBarrio() {
            this.invalidos = [];
            if (!this.barrio.barrio.trim()) {
                this.invalidos.push('barrio');
                this.error = 'Por favor ingrese el nombre del barrio.';
                return;
            }

            /* Sólo se envía el nombre. La versión anterior mandaba además una
               variable `municipio` que nunca se declaró: al editar un barrio
               lanzaba un ReferenceError y la petición ni siquiera salía. El
               servidor tampoco lo usa. */
            const cuerpo = { barrio: this.barrio.barrio.trim() };

            this.guardando = true;
            this.error = '';
            try {
                const editando = this.barrio.id;
                const r = editando
                    ? await window.api(this.urls.updateBarrio.replace('__ID__', editando),
                                       { method: 'PUT', body: cuerpo })
                    : await window.api(this.urls.storeBarrio, { method: 'POST', body: cuerpo });

                const fila = {
                    id: r.ok.id,
                    barrio: r.ok.barrio,
                    municipio: r.ok.municipios?.[0]?.nombre ?? 'N/A',
                };

                if (editando) {
                    const i = this.barrios.findIndex(b => b.id === fila.id);
                    if (i !== -1) Object.assign(this.barrios[i], fila);
                    const j = this.opciones.barrio.findIndex(o => o.value === fila.id);
                    if (j !== -1) this.opciones.barrio[j].label = fila.barrio;
                } else {
                    this.barrios.push(fila);
                    this.opciones.barrio = [...this.opciones.barrio,
                                            { value: fila.id, label: fila.barrio }];
                }

                this.cerrar();
                this.aviso(r.success ?? 'Barrio guardado');
            } catch (e) {
                this.error = this.motivo(e, 'No se pudo guardar el barrio.');
            } finally {
                this.guardando = false;
            }
        },

        /* ----------------------------- Sedes y zonas -------------------------- */
        /* Sede y zona comparten forma; el par de métodos por cada una existe
           sólo porque las rutas y la clave de la respuesta son distintas. */
        async guardarRegistro(tipo, ficha, lista, rutas, clave) {
            this.invalidos = [];
            if (!ficha.nombre.trim()) {
                this.invalidos.push('nombre');
                this.error = `Por favor ingrese el nombre de la ${tipo}.`;
                return;
            }

            this.guardando = true;
            this.error = '';
            try {
                const editando = ficha.id;
                const r = editando
                    ? await window.api(rutas.update.replace('__ID__', editando),
                                       { method: 'PUT', body: { nombre: ficha.nombre.trim() } })
                    : await window.api(rutas.store, { method: 'POST', body: { nombre: ficha.nombre.trim() } });

                const dato = r[clave];
                const fila = { id: dato.id, nombre: dato.nombre, activo: Number(dato.status) === 1 };

                if (editando) {
                    const i = lista.findIndex(x => x.id === fila.id);
                    if (i !== -1) Object.assign(lista[i], { nombre: fila.nombre });
                } else {
                    lista.push(fila);
                }

                // Se vuelve al listado, no se cierra todo.
                this.modal = 'sedes';
                this.error = '';
                this.aviso(r.success ?? 'Registro guardado');
            } catch (e) {
                this.error = this.motivo(e, `No se pudo guardar la ${tipo}.`);
            } finally {
                this.guardando = false;
            }
        },

        guardarSede() {
            return this.guardarRegistro('sede', this.sede, this.sedes,
                { store: this.urls.storeSede, update: this.urls.updateSede }, 'sede');
        },

        guardarZona() {
            return this.guardarRegistro('zona', this.zona, this.zonas,
                { store: this.urls.storeZona, update: this.urls.updateZona }, 'zona');
        },

        async cambiarEstadoSuelto(registro, url, clave) {
            try {
                const r = await window.api(url, { method: 'POST', body: { id: registro.id } });
                registro.activo = Number(r[clave].status) === 1;
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: this.motivo(e, 'No se pudo cambiar el estado.') });
            }
        },

        cambiarEstadoSede(s) { return this.cambiarEstadoSuelto(s, this.urls.estadoSede, 'sede'); },
        cambiarEstadoZona(z) { return this.cambiarEstadoSuelto(z, this.urls.estadoZona, 'zona'); },

        /* ------------------------------- Filtros ------------------------------ */
        consulta(url) {
            const p = new URLSearchParams();
            for (const [clave, valor] of Object.entries(this.filtros)) {
                if (valor !== '' && valor !== null) p.set(clave, valor);
            }
            const q = p.toString();
            return q ? `${url}?${q}` : url;
        },

        limpiarFiltros() {
            this.filtros = { municipio: '', grupo: '', subgrupo: '', barrio: '', inspector: '' };
            this.errorBusqueda = '';
            this.actualizarFiltros();
        },

        /* Cada filtro acota los demás: se vuelve a preguntar qué combinaciones
           siguen existiendo y se reconstruyen las cinco listas. */
        async actualizarFiltros() {
            this.actualizando = true;
            try {
                const r = await window.api(this.consulta(this.urls.selects));
                const datos = r.data ?? [];

                this.opciones = {
                    municipio: this.unicos(datos, 'tbl_localidades_municipio', 'nombre'),
                    grupo:     this.unicos(datos, 'tbl_grupo', 'grupo'),
                    subgrupo:  this.unicos(datos, 'tbl_subgrupo', 'subgrupo'),
                    barrio:    this.unicos(datos, 'tbl_barrios', 'barrio'),
                    inspector: this.unicosInspectores(datos),
                };
            } catch (e) {
                console.error('No se pudieron ajustar los filtros:', e);
            } finally {
                this.actualizando = false;
            }
        },

        unicos(filas, relacion, campo) {
            const mapa = new Map();
            for (const fila of filas) {
                const obj = fila[relacion];
                if (obj?.id && !mapa.has(obj.id)) mapa.set(obj.id, { value: obj.id, label: obj[campo] });
            }
            return [...mapa.values()];
        },

        unicosInspectores(filas) {
            const mapa = new Map();
            for (const fila of filas) {
                if (!Array.isArray(fila.inspectores)) continue;
                for (const i of fila.inspectores) {
                    if (i?.id && !mapa.has(i.id)) {
                        mapa.set(i.id, { value: i.id, label: `${i.id}. ${i.apellidos} ${i.nombres}` });
                    }
                }
            }
            return [...mapa.values()];
        },

        /* ------------------------------ Búsqueda ------------------------------ */
        async buscar() {
            if (!this.hayFiltro) {
                this.errorBusqueda = 'Debe proporcionar al menos un valor para municipio, barrio, grupo, sub grupo o inspector.';
                return;
            }

            this.buscando = true;
            this.errorBusqueda = '';
            try {
                const r = await window.api(this.consulta(this.urls.buscar));

                this.filas = (r.data ?? []).map(fila => ({
                    id: fila.id ?? '',
                    municipio: fila.tbl_localidades_municipio?.nombre ?? '',
                    grupo: fila.tbl_grupo?.grupo ?? '',
                    subgrupo: fila.tbl_subgrupo?.subgrupo ?? '',
                    barrio: fila.tbl_barrios
                        ? `${fila.tbl_barrios.id}. ${fila.tbl_barrios.barrio}`
                        : '',
                    inspectores: '',
                }));

                // Lo primero de la lista es el vacío, para poder dejar la celda sin barrio.
                this.barriosDisponibles = ['', ...(r.barrios ?? []).map(b => `${b.id}. ${b.barrio}`)];

                // Visible ANTES de montar: si no, la rejilla se mide a cero.
                await this.$nextTick();
                this.montarTabla();
            } catch (e) {
                this.filas = [];
                this.errorBusqueda = this.motivo(e, 'No se pudo realizar la búsqueda.');
            } finally {
                this.buscando = false;
            }
        },

        /* ------------------------------- Rejilla ------------------------------ */
        columnas() {
            return [
                { data: 'municipio', readOnly: true },
                { data: 'grupo', readOnly: true },
                { data: 'subgrupo', readOnly: true },
                {
                    data: 'barrio',
                    type: 'dropdown',
                    source: [...this.barriosDisponibles],
                    allowEmpty: true,
                    strict: true,
                },
                {
                    data: 'inspectores',
                    readOnly: true,
                    renderer(instancia, td, fila) {
                        td.innerHTML = '';
                        const enlace = document.createElement('button');
                        enlace.type = 'button';
                        enlace.className = 'ver-inspectores';
                        enlace.textContent = 'Ver';
                        enlace.dataset.fila = fila;
                        td.appendChild(enlace);
                        td.className = 'htCenter htMiddle';
                    },
                },
            ];
        },

        /* Copias planas: los objetos que guarda Alpine son proxies reactivos y
           Handsontable pierde la identidad con la que trabaja por dentro. */
        filasPlanas() { return this.filas.map(f => ({ ...f })); },

        montarTabla() {
            const self = this;

            if (hotRelaciones) {
                hotRelaciones.updateSettings({ columns: this.columnas() });
                hotRelaciones.loadData(this.filasPlanas());
                hotRelaciones.render();
                return;
            }

            hotRelaciones = new Handsontable(document.getElementById('tablaRelaciones'), {
                data: this.filasPlanas(),
                colHeaders: ['MUNICIPIO', 'GRUPO', 'SUB GRUPO', 'BARRIO', 'INSPECTORES'],
                columns: this.columnas(),
                rowHeaders: true,
                stretchH: 'all',
                height: '55vh',
                rowHeights: 26,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                manualColumnResize: true,
                licenseKey: 'non-commercial-and-evaluation',
                /* Sólo se puede asignar barrio donde todavía no hay ninguno, y
                   sólo con permiso de gestión. */
                cells(fila, columna) {
                    if (columna !== cr('barrio')) return {};
                    const valor = this.instance.getDataAtCell(fila, columna);
                    return { readOnly: !self.puedeGestionar || !(valor === '' || valor === null) };
                },
                afterChange: (cambios, origen) => this.alAsignarBarrio(cambios, origen),
            });
            window.registrarHot?.(hotRelaciones);

            /* Un solo escuchador delegado en la raíz: los <td> se reciclan al
               desplazarse y engancharlos uno a uno los perdería. */
            hotRelaciones.rootElement.addEventListener('click', (e) => {
                const boton = e.target.closest('.ver-inspectores');
                if (!boton) return;
                e.preventDefault();
                this.verInspectores(Number(boton.dataset.fila));
            });
        },

        async alAsignarBarrio(cambios, origen) {
            if (origen !== 'edit' || !cambios) return;

            for (const [fila, prop, viejo, nuevo] of cambios) {
                if (prop !== 'barrio' || !nuevo || nuevo === viejo) continue;

                const registro = hotRelaciones.getSourceDataAtRow(fila);
                if (!registro?.id) continue;

                try {
                    await window.api(this.urls.asignarBarrio, {
                        method: 'POST',
                        body: { barrio: nuevo, id: registro.id },
                    });
                    this.aviso('Barrio asignado');
                } catch (e) {
                    // Se devuelve la celda a su valor anterior: el servidor no lo aceptó.
                    hotRelaciones.setDataAtRowProp(fila, 'barrio', viejo ?? '', 'programmatic');
                    window.Swal.fire({ icon: 'error', title: 'Error',
                                       text: this.motivo(e, 'No se pudo asignar el barrio.') });
                }
            }
        },

        async verInspectores(fila) {
            const registro = hotRelaciones.getSourceDataAtRow(fila);
            if (!registro?.id) return;

            this.detalle = { grupo: '', subgrupo: '', inspectores: [] };
            this.cargandoInspectores = true;
            this.modal = 'inspectores';

            try {
                const r = await window.api(this.urls.inspectores.replace('__ID__', registro.id));
                this.detalle = {
                    grupo: r.grupo?.grupo ?? '',
                    subgrupo: r.subgrupo?.subgrupo ?? '',
                    inspectores: r.inspectores ?? [],
                };
            } catch (e) {
                this.cerrar();
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudieron obtener los inspectores asignados.' });
            } finally {
                this.cargandoInspectores = false;
            }
        },
    }));
});
</script>
