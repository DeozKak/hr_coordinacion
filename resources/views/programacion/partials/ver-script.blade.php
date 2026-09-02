<script>
/* La instancia de Handsontable vive fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotAgenda = null;

/* Las tres columnas que necesitan más sitio; al resto se le calcula el ancho
   por la longitud de su título. */
const ANCHO_AMPLIO = 300;
const anchoPorTitulo = (titulo) => Math.max(100, String(titulo).length * 8 + 40);

document.addEventListener('alpine:init', () => {
    Alpine.data('verProgramacion', ({ puedeCambiarTecnico, tecnicos, urls }) => ({
        puedeCambiarTecnico, tecnicos, urls,

        fechaInicio: '',
        fechaFin: '',
        rango: false,

        buscando: false,
        ocupado: false,
        mensajeOcupado: 'Generando archivo…',
        hayResultados: false,
        total: 0,
        buscado: false,

        sincronizando: false,
        porcentaje: 0,
        temporizador: null,

        modal: null,
        verMas: '',
        detenido: false,

        /* ------------------------------- Init -------------------------------- */
        init() {
            this.vigilarSincronizacion();
            this.$watch('$store.ui.dark', () => hotAgenda?.render());
        },

        destroy() {
            // La marca es necesaria además del clearTimeout: si el componente
            // muere mientras una consulta está en vuelo, al resolverse volvería
            // a programar el siguiente sondeo.
            this.detenido = true;
            clearTimeout(this.temporizador);
            this.temporizador = null;
        },

        get descripcionRango() {
            if (!this.rango || !this.fechaFin) return `del ${this.fechaInicio}`;
            return `entre ${this.fechaInicio} y ${this.fechaFin}`;
        },

        get mensajeVacio() {
            return this.buscado
                ? 'No hay programaciones agendadas para esa fecha.'
                : 'Todavía no has hecho ninguna búsqueda.';
        },

        /* ------------------------------ Colores ------------------------------ */
        /* Los registros creados a mano no traen orden de trabajo; se marcan para
           distinguirlos de un vistazo. El color va por el renderizador y no por
           una clase CSS: en Handsontable 18 la regla .htDimmed de las celdas de
           solo lectura pisa cualquier fondo puesto desde la hoja de estilos. */
        paleta() {
            return Alpine.store('ui').dark
                ? { plantilla: '#854d0e' }
                : { plantilla: '#fef08a' };
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
            return this.razon('#ffffff', fondo) >= this.razon('#0f172a', fondo)
                ? '#ffffff' : '#0f172a';
        },

        pintar(td, clave) {
            const fondo = this.paleta()[clave];
            if (!fondo) return;
            td.style.setProperty('background-color', fondo, 'important');
            td.style.setProperty('color', this.contraste(fondo), 'important');
        },

        /* ------------------------------ Búsqueda ------------------------------ */
        async buscar() {
            if (!this.fechaInicio) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la fecha',
                                   text: 'Selecciona una fecha de agendamiento.' });
                return;
            }
            if (this.rango && !this.fechaFin) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la fecha final',
                                   text: 'Selecciona hasta qué día quieres buscar.' });
                return;
            }
            if (this.rango && this.fechaInicio > this.fechaFin) {
                window.Swal.fire({ icon: 'warning', title: 'Rango al revés',
                                   text: 'La fecha inicial no puede ser posterior a la final.' });
                return;
            }

            this.buscando = true;
            try {
                const r = await window.api(this.urls.buscar, {
                    method: 'POST',
                    body: {
                        fechaInicio: this.fechaInicio,
                        fechaFin: this.rango ? this.fechaFin : null,
                    },
                });

                this.buscado = true;
                this.total = r.data?.length ?? 0;
                this.hayResultados = this.total > 0;

                if (!this.hayResultados) {
                    this.destruirTabla();
                    return;
                }

                await this.$nextTick();
                this.construirTabla(r.data);
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo consultar el agendamiento.' });
            } finally {
                this.buscando = false;
            }
        },

        destruirTabla() {
            hotAgenda?.destroy();
            hotAgenda = null;
        },

        /* Las columnas se deducen de la propia respuesta en vez de la lista de
           columnas de la tabla: esa trae seis nombres de más (HORA_INICIO,
           plantilla, EJECUTADA…) que no viajan en los datos, y desalineaban los
           títulos con el contenido. */
        construirTabla(filas) {
            this.destruirTabla();

            const self = this;
            const claves = Object.keys(filas[0]);

            /* Renderizador de celda: pinta el fondo cuando toca y, si el texto es
               largo, lo recorta y añade el ojo. Va como cierre y no como
               renderizador registrado por nombre, porque necesita el componente. */
            const renderizador = function (instancia, td, fila, columna, prop, valor, meta) {
                Handsontable.renderers.TextRenderer.apply(this, arguments);

                td.style.removeProperty('background-color');
                if (meta.claveColor) self.pintar(td, meta.claveColor);

                if (typeof valor !== 'string' || valor.length <= 30) return;

                td.textContent = '';
                td.classList.add('celda-ver-mas');

                const texto = document.createElement('span');
                texto.textContent = valor;
                texto.title = valor;

                const boton = document.createElement('button');
                boton.type = 'button';
                boton.className = 'ver-mas-btn';
                boton.setAttribute('aria-label', 'Ver texto completo');
                boton.innerHTML = '<i class="fas fa-eye"></i>';
                boton.dataset.texto = valor;

                td.append(texto, boton);
            };

            const colTecnico = claves.indexOf('TECNICO');
            const colOrden = claves.indexOf('ORDEN_TRABAJO');
            const contenedor = document.getElementById('buscador');

            hotAgenda = new Handsontable(contenedor, {
                data: filas,
                columns: claves.map(clave => ({ data: clave })),
                colHeaders: claves.map(clave => clave.replace(/_/g, ' ')),
                rowHeaders: true,
                readOnly: true,
                height: '65vh',
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                manualColumnResize: true,
                filters: true,
                dropdownMenu: true,
                contextMenu: true,
                copyPaste: { copyColumnHeaders: true, copyColumnGroupHeaders: true },
                hiddenColumns: { columns: [claves.indexOf('id')].filter(i => i >= 0) },
                colWidths(indice) {
                    const clave = claves[indice] ?? '';
                    if (clave === 'OBSERVACIONES' || clave === 'TECNICO') return ANCHO_AMPLIO;
                    return anchoPorTitulo(clave.replace(/_/g, ' '));
                },
                licenseKey: 'non-commercial-and-evaluation',

                cells(fila, columna) {
                    const datos = this.instance.getSourceDataAtRow(fila);
                    const meta = {
                        renderer: renderizador,
                        // Sin orden de trabajo: viene del alta manual.
                        claveColor: datos?.ORDEN_TRABAJO === 'N/A' ? 'plantilla' : null,
                    };

                    if (self.puedeCambiarTecnico && columna === colTecnico) {
                        return { ...meta, readOnly: false, type: 'dropdown',
                                 source: [...self.tecnicos], strict: true, allowInvalid: false };
                    }
                    return meta;
                },

                afterChange: (cambios, origen) => {
                    if (origen !== 'edit' || !cambios) return;
                    for (const [fila, prop, viejo, nuevo] of cambios) {
                        if (prop !== 'TECNICO' || viejo === nuevo) continue;
                        this.guardarTecnico(hotAgenda.getSourceDataAtRow(fila)?.id, nuevo);
                    }
                },
            });
            window.registrarHot?.(hotAgenda);
            window.centrarHot?.(hotAgenda);

            // Un solo delegado para todos los ojos, en lugar de uno por celda.
            contenedor.addEventListener('click', (e) => {
                const boton = e.target.closest('.ver-mas-btn');
                if (!boton) return;
                e.stopPropagation();
                this.verMas = boton.dataset.texto ?? '';
                this.modal = 'verMas';
            });
        },

        async guardarTecnico(id, valor) {
            if (!id) return;
            try {
                const r = await window.api(this.urls.actualizar.replace('__id__', id), {
                    method: 'PUT', body: { propiedad: 'TECNICO', valor },
                });
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: r?.message ?? 'Técnico actualizado',
                                   timer: 2000, showConfirmButton: false });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo cambiar el técnico.' });
            }
        },

        /* ---------------------------- Exportaciones --------------------------- */
        /* Las dos plantillas devuelven la URL de un archivo ya generado.
           Se manda lo que hay en la tabla EN ESTE MOMENTO: el original guardaba
           una copia al buscar, así que un cambio de técnico no salía en la de
           supervisores. */
        async exportar(cual) {
            if (!hotAgenda) return;

            const esSup = cual === 'sup';
            this.mensajeOcupado = esSup ? 'Generando plantilla de supervisores…'
                                        : 'Generando plantilla GDW…';
            this.ocupado = true;

            try {
                const cuerpo = { data: hotAgenda.getData() };
                if (esSup) {
                    cuerpo.fechaInicio = this.fechaInicio;
                    cuerpo.fechaFin = this.rango ? this.fechaFin : null;
                }

                const r = await window.api(esSup ? this.urls.exportarSup : this.urls.exportarGdw,
                                           { method: 'POST', body: cuerpo });
                if (r?.url) window.location.href = r.url;
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo generar el archivo.' });
            } finally {
                this.ocupado = false;
            }
        },

        /* La reasignación descarga un Excel directamente, así que va por fetch:
           la respuesta es binaria y no pasa por window.api. */
        async asignar() {
            if (this.rango) {
                window.Swal.fire({ icon: 'warning', title: 'Solo una fecha',
                                   text: 'La reasignación funciona con una fecha concreta. '
                                       + 'Desmarca la búsqueda por rango.' });
                return;
            }
            if (!this.fechaInicio) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la fecha',
                                   text: 'Selecciona una fecha antes de asignar.' });
                return;
            }

            this.mensajeOcupado = 'Preparando la reasignación…';
            this.ocupado = true;

            try {
                const respuesta = await fetch(this.urls.reasignar.replace('__fecha__', this.fechaInicio));
                if (!respuesta.ok) {
                    const error = await respuesta.json().catch(() => ({}));
                    throw new Error(error.mensaje ?? 'No se pudo generar el archivo.');
                }

                const blob = await respuesta.blob();
                const enlace = document.createElement('a');
                enlace.href = URL.createObjectURL(blob);
                enlace.download = `Reasignacion_${this.fechaInicio}.xlsx`;
                document.body.appendChild(enlace);
                enlace.click();
                enlace.remove();
                URL.revokeObjectURL(enlace.href);
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error', text: e.message });
            } finally {
                this.ocupado = false;
            }
        },

        /* ------------------------ Sincronización de técnicos ------------------ */
        /* Mientras un job de asignación corre no tiene sentido buscar. Se
           consulta cada 5 s, pero solo con la pestaña visible: antes seguía
           preguntando indefinidamente aunque nadie estuviera mirando. */
        async vigilarSincronizacion() {
            if (this.detenido) return;
            clearTimeout(this.temporizador);

            if (!document.hidden) {
                try {
                    const r = await window.api(this.urls.trabajos);
                    this.sincronizando = r?.percentage !== null && r?.percentage !== undefined;
                    this.porcentaje = this.sincronizando ? r.percentage : 0;
                } catch (e) {
                    this.sincronizando = false;
                }
            }

            if (this.detenido) return;
            this.temporizador = setTimeout(() => this.vigilarSincronizacion(), 5000);
        },
    }));
});
</script>

@push('styles')
    <style>
        /* Celda con el ojo al final para el texto largo. */
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
