<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bitacoraTabla', ({ tablas, causales, inspectores, idBitacora, idSuper, urls }) => ({
        tablas, causales, inspectores, idBitacora, idSuper, urls,

        indiceActivo: 0,
        modal: null,
        guardando: false,
        agregando: false,

        municipios: [],
        buscandoMunicipio: false,

        papel: {},
        errores: {},

        init() {
            // El original llamaba a cambiarColor() sobre cada select al cargar,
            // lo que dejaba la causal en '--SELECCIONE CAUSAL--' en las filas OK.
            for (const t of this.tablas) {
                for (const f of t.filas) {
                    if (f.estado === 'OK') f.causal = '--SELECCIONE CAUSAL--';
                    this.sembrarConfirmado(f);
                }
            }
            this.resetPapel();
        },

        get tablaActiva() { return this.tablas[this.indiceActivo] ?? { nombre: '', filas: [] }; },

        /* Mismos criterios que contadores_dinamicos(): sólo cuentan las filas en OK. */
        get indicadores() {
            const c = { certificada: 0, conNovedades: 0, defectoCritico: 0, defectoNoCritico: 0, total: 0 };
            for (const f of this.tablaActiva.filas) {
                if (f.estado !== 'OK') continue;
                switch (f.resultado) {
                    case 'CERTIFICADA':                                 c.certificada++;      c.total++; break;
                    case 'CERTIFICADA CON NOVEDADES':                   c.conNovedades++;     c.total++; break;
                    case 'INSPECCIONADA CON DEFECTO CRITICO VALLE':     c.defectoCritico++;   c.total++; break;
                    case 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE':  c.defectoNoCritico++; c.total++; break;
                }
            }
            return c;
        },

        // ---------------------------------------------------------------
        // AUTOGUARDADO (POST bitacora/actualizar/{id})
        //
        // La bitácora definitiva se arma con lo que hay en la tabla de
        // borrador, no con lo que la pantalla lleve en memoria. Un cambio que
        // no llegue al servidor no existe, así que no se da por bueno hasta
        // que responda: si falla, la celda vuelve a su último valor confirmado
        // y se avisa. Antes se pintaba al instante y sólo se registraba el
        // fallo, con lo que quedaba a la vista un dato que no se guardó.
        // ---------------------------------------------------------------

        /**
         * Fija el estado que el servidor da por bueno para una fila.
         *
         * Se llama al cargar la tabla y al añadir una inspección, nunca desde
         * `guardarCampo`: para entonces `x-model` ya mutó la fila, así que
         * sembrarla ahí guardaba como «confirmado» el valor que acababa de
         * cambiar y el primer fallo no revertía nada.
         */
        sembrarConfirmado(fila) {
            fila._confirmado = {
                '4_RECINTOS': fila.tieneRecintos ? fila.recintos : 'NO',
                'ESTADO': fila.estado,
                'CAUSAL': fila.causal,
            };
        },

        /** Último valor que el servidor confirmó para cada campo de la fila. */
        confirmado(fila) {
            if (!fila._confirmado) this.sembrarConfirmado(fila);
            return fila._confirmado;
        },

        /** Devuelve la celda a lo último que el servidor dio por guardado. */
        revertir(fila, campo) {
            const previo = this.confirmado(fila)[campo];

            if (campo === '4_RECINTOS') {
                fila.tieneRecintos = previo !== 'NO' && previo !== '';
                fila.recintos = fila.tieneRecintos ? previo : '';
            } else if (campo === 'ESTADO') {
                fila.estado = previo;
                if (previo === 'OK') fila.causal = '--SELECCIONE CAUSAL--';
            } else if (campo === 'CAUSAL') {
                fila.causal = previo;
            }
        },

        async guardarCampo(fila, campo, valor) {
            const anterior = this.confirmado(fila);

            try {
                const r = await window.api(this.urls.actualizar.replace(':id', fila.id), {
                    method: 'POST',
                    body: { campo, valor },
                });

                // Se pinta lo que el servidor dice que quedó, no lo que se envió.
                anterior[campo] = r?.valor ?? valor;
                if (campo === 'ESTADO' && r?.valor === 'OK') {
                    fila.causal = '--SELECCIONE CAUSAL--';
                    anterior.CAUSAL = r?.causal ?? null;
                }
                return true;
            } catch (e) {
                this.revertir(fila, campo);
                Swal.fire({
                    icon: 'error',
                    title: 'El cambio no se guardó',
                    text: this.motivoError(e) || 'No se pudo guardar el cambio; la celda volvió a su valor anterior.',
                });
                return false;
            }
        },

        motivoError(e) {
            const d = e?.data ?? e?.response ?? null;
            return d?.error ?? d?.message ?? '';
        },

        alternarRecintos(fila) {
            if (fila.tieneRecintos) return;          // al marcar no se envía nada hasta escribir la cantidad
            fila.recintos = '';
            this.guardarCampo(fila, '4_RECINTOS', 'NO');
        },

        cambiarEstado(fila) {
            // Al volver a OK se descarta la causal, igual que hacía cambiarColor().
            if (fila.estado === 'OK') fila.causal = '--SELECCIONE CAUSAL--';
            this.guardarCampo(fila, 'ESTADO', fila.estado);
        },

        // ---------------------------------------------------------------
        // INSPECCIÓN EN PAPEL (POST bitacora/agregar)
        // ---------------------------------------------------------------
        get fechaMinima() {
            const d = new Date();
            d.setDate(d.getDate() - 7);            // el original permitía 7 días atrás
            return d.toISOString().slice(0, 10);
        },

        get esLineaMatriz() {
            return ['FI-29 revisión periódica línea matriz', 'FI-31 REVISIÓN NUEVA LINEA MATRIZ']
                .includes(this.papel.tipo);
        },

        get requiereCausal() {
            return this.papel.resultado !== '' && this.papel.resultado !== 'CERTIFICADA';
        },

        get papelInspectorFijo() {
            // Si hay un inspector activo en el selector, el modal queda fijado a él.
            return !!this.inspectores.find(i => i.nombre === this.tablaActiva.nombre);
        },

        resetPapel() {
            const fijo = this.inspectores.find(i => i.nombre === this.tablaActiva.nombre);
            this.papel = {
                cedula: fijo?.cedula ?? '',
                municipio: '', municipioTexto: '',
                fecha: '', acta: 'P', tipo: '', contrato: ':',
                categoria: '', recintos: 'NO', cantidadRecintos: '',
                resultado: '', causal: '',
            };
            this.errores = {};
            this.municipios = [];
        },

        abrirPapel() {
            this.resetPapel();
            this.modal = 'papel';
        },

        async buscarMunicipios() {
            const q = this.papel.municipioTexto.trim();
            if (q.length < 2) { this.municipios = []; return; }

            this.buscandoMunicipio = true;
            try {
                const data = await window.api(`${this.urls.municipios}?term=${encodeURIComponent(q)}`);
                this.municipios = Object.keys(data).map(k => ({ id: k, text: data[k] }));
            } catch (e) {
                this.municipios = [];
            } finally {
                this.buscandoMunicipio = false;
            }
        },

        validarPapel() {
            const p = this.papel;
            const e = {};

            if (!p.cedula) e.cedula = true;
            if (!p.municipio) e.municipio = true;
            if (!p.fecha) e.fecha = true;
            if (!p.acta || p.acta === 'P') e.acta = true;
            if (!p.tipo) e.tipo = true;
            if (!p.contrato || p.contrato === ':') e.contrato = true;
            if (!p.resultado) e.resultado = true;

            // Categoría y recintos no aplican a líneas matriz.
            if (!this.esLineaMatriz) {
                if (!p.categoria) e.categoria = true;
                if (p.recintos === 'SI' && !p.cantidadRecintos.trim()) e.cantidadRecintos = true;
            }
            // La causal sólo es obligatoria si el resultado no es CERTIFICADA.
            if (this.requiereCausal && !p.causal.trim()) e.causal = true;

            this.errores = e;
            return Object.keys(e).length === 0;
        },

        async agregarPapel() {
            if (!this.validarPapel()) {
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'warning',
                    title: 'Por favor complete todos los campos',
                    showConfirmButton: false, timer: 4000,
                });
                return;
            }

            const p = this.papel;
            const inspector = this.inspectores.find(i => i.cedula === p.cedula);
            const indice = this.tablas.findIndex(t => t.nombre === inspector?.nombre);

            if (indice === -1) {
                Swal.fire({ icon: 'error', title: 'Error', text: 'El inspector no tiene tabla en esta bitácora' });
                return;
            }

            // Mismo control de duplicados que validacionDatos(): contrato repetido.
            if (this.tablas[indice].filas.some(f => f.contrato === p.contrato)) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Contrato duplicado',
                    text: 'El contrato y la orden de trabajo ya se encuentran registrados en la bitácora. Por favor, verifique los datos ingresados.',
                });
                return;
            }

            this.agregando = true;
            try {
                const res = await window.api(this.urls.agregar, {
                    method: 'POST',
                    body: {
                        datos: {
                            nombre: inspector.nombre,
                            cedula: p.cedula,
                            municipio: p.municipio,
                            fecha: p.fecha,
                            acta: p.acta,
                            tipoTrabajo: p.tipo,
                            contrato: p.contrato,
                            categoria: p.categoria,
                            cantidadRecintos: p.cantidadRecintos,
                            resultadoCierre: p.resultado,
                            rechazo: p.causal,
                            id_bitacora: this.idBitacora,
                            id_super: this.idSuper,
                        },
                    },
                });

                if (!res.id) throw new Error('Error en la respuesta del servidor');

                this.tablas[indice].filas.push({
                    id: String(res.id),
                    nombre: inspector.nombre,
                    cedula: p.cedula,
                    municipio: p.municipio,
                    fecha: p.fecha,
                    acta: p.acta,
                    tipo: p.tipo,
                    contrato: p.contrato,
                    orden: '',
                    // El original sólo repetía la orden para RP 12161 (siempre vacía aquí).
                    ordenExt: '',
                    categoria: p.categoria,
                    resultado: p.resultado,
                    horaInicio: '', horaFinal: '', duracion: '', duracionMin: null,
                    tieneRecintos: p.recintos === 'SI',
                    recintos: p.recintos === 'SI' ? p.cantidadRecintos : '',
                    estado: 'OK',
                    causal: '--SELECCIONE CAUSAL--',
                    vence: '',
                    rechazo: p.causal,
                    periodoGracia: '',
                    gDevolucion: false, gracia: false, vence60: false,
                    nueva: true,
                });

                /* La fila recién añadida ya existe en el borrador tal cual, así
                   que ese es su estado confirmado de partida. */
                this.sembrarConfirmado(this.tablas[indice].filas.at(-1));

                this.indiceActivo = indice;
                this.modal = null;
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: 'Inspección agregada correctamente',
                    showConfirmButton: false, timer: 3000,
                });
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', text: e.message ?? 'No se pudo agregar la inspección' });
            } finally {
                this.agregando = false;
            }
        },


        /* El servidor arma la bitácora con lo que hay en el borrador de
           autoguardado, así que ya no se le manda la tabla: lo que se ve en
           pantalla es un reflejo de esa tabla, no la fuente. */
        async guardar() {
            this.guardando = true;
            try {
                const res = await window.api(this.urls.guardar, { method: 'POST' });

                if (res.error) {
                    Swal.fire({ icon: 'warning', title: 'Advertencia', text: res.error });
                    return;
                }
                if (res.ruta) {
                    setTimeout(() => { window.location.href = res.ruta; }, 200);
                }
            } catch (e) {
                Swal.fire({ icon: 'error', title: 'Error', text: e.data?.error ?? 'No se pudo guardar la bitácora' });
            } finally {
                this.guardando = false;
            }
        },
    }));
});
</script>
