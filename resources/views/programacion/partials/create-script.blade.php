<script>
/* La instancia de Handsontable y la referencia al componente viven fuera del
   estado de Alpine: el proxy reactivo rompe la identidad que usa la librería,
   y el renderizador de celda necesita llegar al componente sin pasar por él. */
let hotProgramacion = null;
let rendererListo = false;

/* Orden de las columnas de la tabla. Se usa en tres sitios (definición,
   volcado de la búsqueda y alta desde plantilla), así que se declara una vez. */
const COLS = ['id', 'CONTRATO', 'TIPO_TRABAJO', 'FECHA', 'CELULAR', 'NOMBRE_USUARIO',
              'ORDEN_TRABAJO', 'DIRECCION', 'BARRIO', 'CIUDAD', 'ACTIVA', 'SUSPENDIDO',
              'CATEGORIA', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'PORQUE_PROGRAMO',
              'TECNICO', 'JORNADA'];
const col = (nombre) => COLS.indexOf(nombre);

/* Campos obligatorios del alta manual, en el orden en que se piden. */
const OBLIGATORIOS = ['CONTRATO', 'CELULAR', 'NOMBRE_USUARIO', 'DIRECCION', 'BARRIO',
                      'TIPO_TRABAJO', 'CIUDAD', 'FECHA_AGENDAMIENTO', 'CATEGORIA',
                      'OBSERVACIONES', 'TECNICO', 'JORNADA'];

/* Aplica min/max al input del editor de fecha.
   Hay que hacerlo ANTES de que el editor abra: Handsontable 18 usa un
   <input type="date"> nativo y su open() llama a showPicker(), que corre antes
   del hook afterBeginEditing. Poniendo el tope después, el calendario ya estaba
   desplegado sin él; a la segunda edición parecía funcionar solo porque el
   atributo había quedado del intento anterior.
   El reintento cubre el caso de que el input aún no exista. */
function ponerLimites(hot, limites, intentos = 12) {
    const editor = hot?.getActiveEditor?.();
    const input = editor?.TEXTAREA
        ?? hot?.rootElement?.querySelector('.handsontableInput[type="date"]');

    if (input) {
        for (const [nombre, valor] of Object.entries(limites)) input[nombre] = valor;
        return;
    }
    if (intentos > 0) requestAnimationFrame(() => ponerLimites(hot, limites, intentos - 1));
}

const hoy = () => {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};
const manana = () => {
    const d = new Date();
    d.setDate(d.getDate() + 1);
    return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
};

document.addEventListener('alpine:init', () => {
    Alpine.data('programacionCreate', ({ soloLectura, puedeAsignarTecnico, tablaId,
                                         usuario, tecnicos, filas, urls }) => {
    /* Las filas se quedan en el cierre y no en el objeto devuelto: todo lo que
       vive ahí lo envuelve el proxy reactivo de Alpine, y Handsontable guarda
       referencias a su propio arreglo de datos. */
    const filasIniciales = filas;

    return {
        soloLectura, puedeAsignarTecnico, tablaId, usuario, tecnicos, urls,

        modal: null,
        verMas: '',
        guardando: false,
        manana: manana(),

        /* El inspector solo lo puede tocar quien tiene el permiso; el resto de
           columnas editables son las mismas para todos. */
        get columnasEditables() {
            return this.puedeAsignarTecnico
                ? ['TECNICO', 'CELULAR', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'JORNADA']
                : ['CELULAR', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'JORNADA'];
        },

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.plantilla = this.plantillaVacia();
            this.registrarRenderizador();
            this.construirTabla();

            if (!this.soloLectura) {
                this.desbloquearFilasConContrato();
                // Dos filas en blanco al final para empezar a escribir.
                this.anadirFila();
                this.anadirFila();
            }

            this.$watch('$store.ui.dark', () => hotProgramacion?.render());
        },

        /* Celda de observaciones: si el texto es largo se recorta y se ofrece
           un ojo que abre la ventana con el contenido completo. */
        registrarRenderizador() {
            if (rendererListo) return;
            rendererListo = true;

            Handsontable.renderers.registerRenderer('verMasRenderer',
                function (instancia, td, fila, columna, prop, valor, meta) {
                    Handsontable.renderers.TextRenderer.apply(this, arguments);
                    if (typeof valor !== 'string' || valor.length <= 30) return;

                    td.textContent = '';
                    td.classList.add('celda-ver-mas');

                    const texto = document.createElement('span');
                    texto.textContent = valor;
                    texto.title = valor;

                    const boton = document.createElement('button');
                    boton.type = 'button';
                    boton.className = 'ver-mas-btn';
                    boton.setAttribute('aria-label', 'Ver observación completa');
                    boton.innerHTML = '<i class="fas fa-eye"></i>';
                    // El texto se guarda en el nodo: el delegado del contenedor
                    // lo lee sin tener que volver a preguntarle a la tabla.
                    boton.dataset.texto = valor;

                    td.append(texto, boton);
                });
        },

        /* ------------------------------ Columnas ----------------------------- */
        columnas() {
            const self = this;
            return [
                { data: 'id', title: 'ID', readOnly: true },
                {
                    data: 'CONTRATO', title: 'CONTRATO',
                    validator(valor, callback) {
                        if (valor === '' || valor === null || /^[0-9]+$/.test(valor)) return callback(true);
                        window.Swal.fire({ icon: 'warning', title: 'Contrato',
                                           text: 'Ingrese solo números en el campo CONTRATO.' });
                        callback(false);
                    },
                },
                { data: 'TIPO_TRABAJO', title: 'TIPO DE OBRA', readOnly: true },
                { data: 'FECHA', title: 'FECHA', type: 'date', dateFormat: 'YYYY-MM-DD',
                  correctFormat: true, defaultDate: new Date(), readOnly: true },
                {
                    data: 'CELULAR', title: 'CELULAR', readOnly: true,
                    validator(valor, callback) {
                        if (valor === '' || valor === null || self.celularValido(valor)) {
                            return callback(true);
                        }
                        window.Swal.fire({ icon: 'warning', title: 'Celular',
                                           text: 'El celular debe tener 10 dígitos y solo números.' });
                        callback(false);
                    },
                },
                { data: 'NOMBRE_USUARIO', title: 'NOMBRE USUARIO', readOnly: true },
                { data: 'ORDEN_TRABAJO', title: 'ORDEN DE TRABAJO', readOnly: true },
                { data: 'DIRECCION', title: 'DIRECCION', readOnly: true },
                { data: 'BARRIO', title: 'BARRIO', readOnly: true },
                { data: 'CIUDAD', title: 'CIUDAD', readOnly: true },
                { data: 'ACTIVA', title: 'ACTIVA', readOnly: true },
                { data: 'SUSPENDIDO', title: 'SUSPENSION', readOnly: true },
                { data: 'CATEGORIA', title: 'CATEGORIA', readOnly: true },
                {
                    data: 'FECHA_AGENDAMIENTO', title: 'FECHA AGENDAMIENTO', type: 'date',
                    dateFormat: 'YYYY-MM-DD', correctFormat: true, readOnly: true,
                    /* El atributo `min` del input solo limita el calendario; escribir
                       a mano o pegar se cuela igual, así que el límite se comprueba
                       también aquí. Solo corre al editar: una tabla antigua que se
                       reabre conserva sus fechas pasadas. */
                    validator(valor, callback) {
                        if (valor === '' || valor === null) return callback(true);

                        const texto = String(valor);
                        if (!/^\d{4}-\d{2}-\d{2}$/.test(texto)) {
                            window.Swal.fire({ icon: 'warning', title: 'Fecha de agendamiento',
                                               text: 'La fecha debe tener el formato AAAA-MM-DD.' });
                            return callback(false);
                        }
                        if (texto <= hoy()) {
                            window.Swal.fire({ icon: 'warning', title: 'Fecha de agendamiento',
                                               text: 'La fecha debe ser posterior a hoy.' });
                            return callback(false);
                        }
                        callback(true);
                    },
                },
                { data: 'OBSERVACIONES', title: 'OBSERVACIONES', width: 300,
                  renderer: 'verMasRenderer', className: 'htCenter', readOnly: true },
                { data: 'PORQUE_PROGRAMO', title: 'PORQUE SE PROGRAMO', className: 'htCenter', readOnly: true },
                { data: 'TECNICO', title: 'TECNICO', type: 'dropdown', source: [...self.tecnicos],
                  width: 200, className: 'htCenter', readOnly: true },
                { data: 'JORNADA', title: 'JORNADA', type: 'dropdown', readOnly: true,
                  editor: 'select', selectOptions: ['mañana', 'tarde', 'todo el dia'] },
            ];
        },

        construirTabla() {
            const contenedor = document.getElementById('tabla_programacion');

            hotProgramacion = new Handsontable(contenedor, {
                data: filasIniciales,
                columns: this.columnas(),
                rowHeaders: true,
                dropdownMenu: true,
                filters: true,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                manualColumnResize: true,
                manualRowResize: true,
                rowHeights: 26,
                height: '65vh',
                licenseKey: 'non-commercial-and-evaluation',
                readOnly: this.soloLectura,
                afterChange: (cambios, origen) => this.alCambiar(cambios, origen),
                // beforeBeginEditing: antes de open(), que es quien despliega el
                // calendario. El de after queda de red por si el input aún no
                // existía; devolver algo aquí cancelaría la edición, así que los
                // dos manejadores llevan cuerpo de bloque.
                beforeBeginEditing: (fila, columna) => { this.limitarCalendario(columna); },
                afterBeginEditing: (fila, columna) => { this.limitarCalendario(columna); },
            });
            window.registrarHot?.(hotProgramacion);

            // Un solo delegado para todos los ojos de la columna observaciones:
            // el original enganchaba un listener por celda en cada repintado.
            contenedor.addEventListener('click', (e) => {
                const boton = e.target.closest('.ver-mas-btn');
                if (!boton) return;
                e.stopPropagation();
                this.verMas = boton.dataset.texto ?? '';
                this.modal = 'verMas';
            });
        },

        /* Handsontable 18 cambió Pikaday por un <input type="date"> nativo y
           `datePickerConfig` dejó de tener efecto (la propia librería avisa por
           consola). El tope se pone ahora en el atributo `min` del input, en
           cuanto el editor se abre. */
        limitarCalendario(columna) {
            if (columna !== col('FECHA_AGENDAMIENTO')) return;
            ponerLimites(hotProgramacion, { min: manana() });
        },

        celularValido(v) { return /^[0-9]{10}$/.test(String(v ?? '')); },

        fechaFutura(v) {
            const texto = String(v ?? '');
            return /^\d{4}-\d{2}-\d{2}$/.test(texto) && texto > hoy();
        },

        /* --------------------------- Bloqueo de celdas ------------------------ */
        marcarFila(fila, editable) {
            for (const nombre of this.columnasEditables) {
                hotProgramacion.getCellMeta(fila, col(nombre)).readOnly = !editable;
            }
        },

        desbloquearFilasConContrato() {
            for (let fila = 0; fila < hotProgramacion.countRows(); fila++) {
                const contrato = hotProgramacion.getDataAtCell(fila, col('CONTRATO'));
                if (contrato !== null && contrato !== '') this.marcarFila(fila, true);
            }
            hotProgramacion.render();
        },

        anadirFila() {
            hotProgramacion.alter('insert_row_above', hotProgramacion.countRows());
        },

        limpiarFila(fila, { conservarContrato = false } = {}) {
            const cambios = [[fila, 0, '']];
            for (let c = 2; c < hotProgramacion.countCols(); c++) cambios.push([fila, c, '']);
            if (!conservarContrato) cambios.push([fila, 1, '']);
            hotProgramacion.batch(() => hotProgramacion.setDataAtCell(cambios, 'programmatic'));
        },

        /* ------------------------------ Cambios ------------------------------ */
        alCambiar(cambios, origen) {
            if (!cambios) return;
            if (!['edit', 'CopyPaste.paste', 'Autofill.fill'].includes(origen)) return;

            for (const [fila, propiedad, anterior, nuevo] of cambios) {
                if (propiedad === 'CONTRATO') {
                    this.alCambiarContrato(fila, nuevo);
                } else if (this.columnasEditables.includes(propiedad) && nuevo !== anterior) {
                    const id = hotProgramacion.getDataAtCell(fila, 0);
                    this.guardarCampo(id, propiedad, nuevo);
                }
            }
        },

        async alCambiarContrato(fila, valor) {
            if (valor === '' || valor === null) {
                // El id se lee antes de vaciar la fila; el borrado va detrás.
                const id = hotProgramacion.getDataAtCell(fila, 0);
                this.marcarFila(fila, false);
                this.limpiarFila(fila, { conservarContrato: true });
                hotProgramacion.render();
                this.borrarEnServidor(id);
                return;
            }

            // Un valor no numérico ya lo avisó el validador de la columna; no
            // tiene sentido preguntarle al servidor por él.
            if (!/^[0-9]+$/.test(String(valor))) return;

            if (this.contratoRepetido(fila, valor)) {
                window.Swal.fire({ icon: 'warning', title: 'Contrato repetido',
                                   text: 'El contrato ya está programado en la misma tabla.' });
                hotProgramacion.setDataAtCell(fila, 1, '', 'programmatic');
                return;
            }

            if (!this.soloLectura) this.marcarFila(fila, true);

            await this.buscarContrato(fila, valor);
        },

        contratoRepetido(fila, valor) {
            const columna = hotProgramacion.getDataAtCol(col('CONTRATO'));
            return columna.some((v, i) => i !== fila && v === valor);
        },

        async buscarContrato(fila, valor) {
            let datos;
            try {
                datos = await window.api(this.urls.busqueda.replace('__id__', valor));
            } catch (e) {
                await this.avisarNoEncontrado(fila);
                return;
            }

            // El endpoint devuelve cuerpo vacío cuando no hay registro.
            if (!datos || typeof datos !== 'object') {
                await this.avisarNoEncontrado(fila);
                return;
            }

            if (datos.movilidad || datos.errors) {
                await window.Swal.fire({ icon: 'warning', title: 'Advertencia',
                                         text: 'Contrato ya ejecutado', allowOutsideClick: false });
                this.limpiarFila(fila);
                return;
            }

            await this.esperarEscrituras(fila, () => this.volcarBusqueda(fila, datos));
            await this.guardarFila(fila, hotProgramacion.getDataAtRow(fila));
        },

        async avisarNoEncontrado(fila) {
            await window.Swal.fire({ icon: 'warning', title: 'Sin resultados',
                                     text: 'No se encontró registro con el contrato ingresado.' });
            this.limpiarFila(fila, { conservarContrato: true });
        },

        /* Espera a que Handsontable aplique de verdad un lote de escrituras.
           No las aplica en el acto: la validación de celda va por medio, así que
           leer la fila justo después devuelve los valores de antes. Se resuelve
           con el afterChange que trae cambios de ESTA fila, y hay un plazo de
           gracia por si el lote no genera ninguno (valores idénticos) y el
           evento no llega a dispararse. */
        esperarEscrituras(fila, escribir, espera = 1000) {
            return new Promise((resolver) => {
                let listo = false;
                let plazo = null;

                const terminar = () => {
                    if (listo) return;
                    listo = true;
                    clearTimeout(plazo);
                    hotProgramacion.removeHook('afterChange', alAplicar);
                    // Un tick más: el hook corre dentro del ciclo de la tabla.
                    queueMicrotask(resolver);
                };

                const alAplicar = (cambios, origen) => {
                    if (origen !== 'programmatic' || !cambios) return;
                    if (!cambios.some(([f]) => f === fila)) return;
                    terminar();
                };

                hotProgramacion.addHook('afterChange', alAplicar);
                plazo = setTimeout(terminar, espera);
                escribir();
            });
        },

        /* Vuelca en la fila la respuesta de la base.
           ACTIVA y SUSPENDIDO salen ambas de DESC_ESTADO_PROD: no son dos datos
           distintos sino las dos caras del mismo estado. */
        volcarBusqueda(fila, datos) {
            const cambios = [
                [fila, col('FECHA'), hoy()],
                [fila, col('PORQUE_PROGRAMO'), this.usuario],
            ];

            const mapa = {
                ID_TIPO_TRABAJO: 'TIPO_TRABAJO',
                NOMBRE: 'NOMBRE_USUARIO',
                NUMERO_ORDEN: 'ORDEN_TRABAJO',
                DIRECCION: 'DIRECCION',
                BARRIO: 'BARRIO',
                DESC_LOCALIDAD: 'CIUDAD',
                NOM_CATE: 'CATEGORIA',
                ID_TECNICO: 'TECNICO',
            };

            for (const [clave, columna] of Object.entries(mapa)) {
                if (clave in datos) cambios.push([fila, col(columna), datos[clave]]);
            }

            if ('DESC_ESTADO_PROD' in datos) {
                const estado = String(datos.DESC_ESTADO_PROD ?? '').toLowerCase();
                cambios.push([fila, col('ACTIVA'), estado === 'activo' ? 'Si' : 'No']);
                cambios.push([fila, col('SUSPENDIDO'), estado === 'suspendido' ? 'Si' : 'No']);
            }

            hotProgramacion.batch(() => hotProgramacion.setDataAtCell(cambios, 'programmatic'));
        },

        /* ------------------------- Guardado de la fila ------------------------ */
        /* `datosFila` se lee de la rejilla DESPUÉS de esperar a que aplique las
           escrituras; leerla antes devolvería los valores previos. */
        async guardarFila(fila, datosFila) {
            if (datosFila[0] !== null && datosFila[0] !== '' && datosFila[0] !== undefined) return;

            let r;
            try {
                r = await window.api(this.urls.store, {
                    method: 'POST', body: { data: datosFila, tabla: this.tablaId },
                });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo guardar el registro.' });
                return;
            }

            if (r.movilidad) {
                await window.Swal.fire({
                    icon: 'warning', title: 'Advertencia', allowOutsideClick: false,
                    text: `${r.movilidad} por ${r.usuario} fecha: ${r.agendamiento}`,
                });
                this.limpiarFila(fila);
                return;
            }

            if (r.exist) {
                await this.resolverDuplicado(fila, r);
                return;
            }

            if (r.error) {
                window.Swal.fire({ icon: 'error', title: 'Error', text: r.error });
                return;
            }

            if (r.id) {
                hotProgramacion.setDataAtCell(fila, 0, r.id, 'programmatic');
                this.anadirFila();
            }
        },

        /* El contrato ya estaba programado: se ofrece reprogramar, que borra la
           programación anterior y vuelve a escribir el contrato para que el
           flujo normal lo dé de alta de nuevo. */
        async resolverDuplicado(fila, r) {
            const respuesta = await window.Swal.fire({
                icon: 'warning', title: 'Advertencia', allowOutsideClick: false,
                text: `Este contrato ya tiene una programación para el ${r.agendamiento} `
                    + `por ${r.usuario}, ¿desea reprogramar?`,
                showDenyButton: true, showCancelButton: false,
                confirmButtonText: 'Sí', denyButtonText: 'No',
            });

            if (respuesta.isDenied || respuesta.isDismissed) {
                this.limpiarFila(fila);
                return;
            }

            const contrato = hotProgramacion.getDataAtCell(fila, col('CONTRATO'));
            try {
                await window.api(this.urls.destroy, { method: 'DELETE', body: { data: r.id } });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo liberar la programación anterior.' });
                return;
            }

            this.limpiarFila(fila);
            // Reescribir el contrato relanza la búsqueda y el alta.
            hotProgramacion.setDataAtCell(fila, col('CONTRATO'), contrato, 'edit');
        },

        async guardarCampo(id, propiedad, valor) {
            if (valor === '' || valor === null || id === '' || id === null || id === undefined) return;

            try {
                const r = await window.api(this.urls.update.replace('__id__', id), {
                    method: 'PUT', body: { propiedad, valor },
                });
                if (r?.error) {
                    window.Swal.fire({ icon: 'error', title: 'Error', text: r.error });
                }
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo actualizar el registro.' });
            }
        },

        async borrarEnServidor(id) {
            if (id === null || id === '' || id === undefined) return;

            try {
                await window.api(this.urls.destroy, { method: 'DELETE', body: { data: id } });
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: 'Registro eliminado', timer: 3000,
                                   showConfirmButton: false });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo eliminar el registro.' });
            }
        },

        /* ------------------------------ Finalizar ---------------------------- */
        /* Solo se puede cerrar la programación si hay al menos una fila y todas
           las que tienen algo lo tienen todo. */
        revisarFilas() {
            const datos = hotProgramacion.getData();
            const vacio = (v) => v === '' || v === null || v === undefined;
            const incompletas = [];
            let hayDatos = false;

            datos.forEach((fila, i) => {
                if (fila.every(vacio)) return;
                hayDatos = true;
                if (fila.some(vacio)) incompletas.push(i + 1);
            });

            /* Aquí solo se miran los huecos. El celular de diez dígitos y la
               fecha futura se exigen al editar y en el alta manual, pero no al
               cerrar: una programación antigua que se reabre trae fechas ya
               pasadas y no debe quedar bloqueada. */
            return { hayDatos, incompletas };
        },

        enumerarFilas(numeros) {
            return numeros.length > 1
                ? `las filas ${numeros.join(', ')}`
                : `la fila ${numeros[0]}`;
        },

        async finalizar() {
            const { hayDatos, incompletas } = this.revisarFilas();

            if (!hayDatos) {
                window.Swal.fire({ icon: 'warning', title: 'Tabla vacía',
                                   text: 'Agrega al menos un contrato antes de guardar.' });
                return;
            }
            if (incompletas.length) {
                window.Swal.fire({
                    icon: 'warning', title: 'Hay campos incompletos',
                    text: `Revisa ${this.enumerarFilas(incompletas)}.`,
                });
                return;
            }

            this.guardando = true;
            try {
                const r = await window.api(this.urls.finish, { method: 'POST' });
                if (r?.ok) { window.location.href = this.urls.index; return; }
                if (r?.error) {
                    window.Swal.fire({ icon: 'error', title: 'Error', text: r.error });
                }
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo finalizar la programación.' });
            } finally {
                this.guardando = false;
            }
        },

        /* =========================== ALTA EN PLANTILLA ======================== */
        plantilla: {},
        invalidos: [],
        enviandoPlantilla: false,
        municipioTexto: '',
        municipios: [],
        buscandoMunicipios: false,
        temporizadorMunicipios: null,

        plantillaVacia() {
            return {
                CONTRATO: '', TIPO_TRABAJO: '', FECHA: hoy(), CELULAR: '', NOMBRE_USUARIO: '',
                ORDEN_TRABAJO: '', DIRECCION: '', BARRIO: '', CIUDAD: '', estado: 'activo',
                CATEGORIA: '', FECHA_AGENDAMIENTO: '', OBSERVACIONES: '',
                PORQUE_PROGRAMO: this.usuario, TECNICO: '', JORNADA: '',
            };
        },

        abrirPlantilla() {
            this.plantilla = this.plantillaVacia();
            this.invalidos = [];
            this.municipioTexto = '';
            this.municipios = [];
            this.modal = 'plantilla';
        },

        cerrarPlantilla() {
            this.modal = null;
            this.invalidos = [];
        },

        claseCampo(campo) {
            return this.invalidos.includes(campo)
                ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                : '';
        },

        soloDigitos(v, max) { return String(v).replace(/[^0-9]/g, '').slice(0, max); },
        soloLetras(v, max) { return String(v).replace(/[^a-zA-ZáéíóúÁÉÍÓÚñÑ ]/g, '').slice(0, max); },

        buscarMunicipios() {
            clearTimeout(this.temporizadorMunicipios);
            const termino = this.municipioTexto.trim();
            if (termino.length < 2) { this.municipios = []; return; }

            this.temporizadorMunicipios = setTimeout(async () => {
                this.buscandoMunicipios = true;
                try {
                    const datos = await window.api(
                        `${this.urls.municipios}?term=${encodeURIComponent(termino)}`);
                    // Una respuesta que llega tarde no debe pisar lo ya escrito.
                    if (termino !== this.municipioTexto.trim()) return;
                    this.municipios = Object.values(datos ?? {});
                } catch (e) {
                    this.municipios = [];
                } finally {
                    this.buscandoMunicipios = false;
                }
            }, 250);
        },

        async agregarPlantilla() {
            // La orden de trabajo es el único campo opcional: si va vacía, N/A.
            if (!this.plantilla.ORDEN_TRABAJO) this.plantilla.ORDEN_TRABAJO = 'N/A';

            this.invalidos = OBLIGATORIOS.filter(campo => !this.plantilla[campo]);
            if (this.invalidos.length) {
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'warning',
                                   title: 'Por favor complete todos los campos',
                                   timer: 4000, showConfirmButton: false });
                return;
            }

            // Las mismas dos reglas que la rejilla, para no crear aquí filas que
            // luego impedirían guardar la programación.
            if (!this.celularValido(this.plantilla.CELULAR)) {
                this.invalidos = ['CELULAR'];
                window.Swal.fire({ icon: 'warning', title: 'Celular',
                                   text: 'El celular debe tener 10 dígitos y solo números.' });
                return;
            }
            if (!this.fechaFutura(this.plantilla.FECHA_AGENDAMIENTO)) {
                this.invalidos = ['FECHA_AGENDAMIENTO'];
                window.Swal.fire({ icon: 'warning', title: 'Fecha de agendamiento',
                                   text: 'La fecha debe ser posterior a hoy.' });
                return;
            }

            const activo = this.plantilla.estado === 'activo';
            const datos = {
                ...this.plantilla,
                ACTIVA: activo ? 'Si' : 'No',
                SUSPENDIDO: activo ? 'No' : 'Si',
            };
            delete datos.estado;

            this.enviandoPlantilla = true;
            try {
                const r = await window.api(this.urls.plantilla, {
                    method: 'POST', body: { data: datos, tabla: this.tablaId },
                });

                datos.id = r.id;
                this.insertarFila(datos);
                this.cerrarPlantilla();
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: 'Registro exitoso', timer: 4000,
                                   showConfirmButton: false });
            } catch (e) {
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'error',
                                   title: e?.data?.error ?? 'Error al registrar',
                                   timer: 4000, showConfirmButton: false });
            } finally {
                this.enviandoPlantilla = false;
            }
        },

        /* Escribe el alta en la primera fila libre y deja otra en blanco debajo. */
        insertarFila(datos) {
            let destino = hotProgramacion.countRows() - 1;
            while (destino >= 0) {
                const fila = hotProgramacion.getDataAtRow(destino);
                if (fila.some(celda => celda !== null && celda !== '')) break;
                destino--;
            }
            destino += 1;

            if (destino >= hotProgramacion.countRows()) this.anadirFila();

            const cambios = COLS.map((nombre, i) => [destino, i, datos[nombre] ?? '']);
            hotProgramacion.batch(() => hotProgramacion.setDataAtCell(cambios, 'programmatic'));

            this.marcarFila(destino, true);
            this.anadirFila();
            hotProgramacion.render();
        },
    };
    });
});
</script>

@push('styles')
    <style>
        /* Celda de observaciones con el ojo al final. */
        .celda-ver-mas {
            display: flex; align-items: center; justify-content: space-between;
            gap: 4px; overflow: hidden; white-space: nowrap;
        }
        .celda-ver-mas > span { flex: 1; overflow: hidden; text-overflow: ellipsis; }
        .ver-mas-btn {
            flex-shrink: 0; border: 0; background: transparent; cursor: pointer;
            padding: 0 2px; color: #1f47e0;
        }
        .dark .ver-mas-btn { color: #8eb6ff; }
    </style>
@endpush
