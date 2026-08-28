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
        // ---------------------------------------------------------------
        async guardarCampo(fila, campo, valor) {
            try {
                await window.api(this.urls.actualizar.replace(':id', fila.id), {
                    method: 'POST',
                    body: { campo, valor },
                });
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', text: 'No se pudo guardar el cambio' });
            }
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

        // ---------------------------------------------------------------
        // GUARDAR TODO (POST guardar_tabla/{super?})
        // El backend sustituye las columnas 16, 17 y 18 por
        // valoresSeleccionados["select_{tabla}_{n}"], con n avanzando de 3 en 3
        // por fila (recintos, estado, causal). Se reproduce ese contrato exacto.
        // ---------------------------------------------------------------
        construirPayload() {
            const encabezado = ['ID','INSPECTOR','CC OPERARIO','MUNICIPIO','FECHA','N° ACTA','TIPO TRABAJO',
                'CONTRATO','ORDEN TRABAJO','ORDEN EXT','CATEGORIA','RESULTADO  CIERRE','HORA INICIO',
                'HORA FINAL','DURACION','4 RECINTOS O MAS'];

            const valoresSeleccionados = {};
            const datos = [];
            const indicadores = [];

            this.tablas.forEach((tabla, t) => {
                const filas = [];
                const c = { certificadaCount: 0, certificadaConNovedadesCount: 0,
                            inspeccionadaConDefectoCriticoCount: 0,
                            inspeccionadaConDefectoNoCriticoCount: 0, totalCount: 0 };

                tabla.filas.forEach((f, i) => {
                    // El backend compara con la cadena "false" (así lo serializaba jQuery),
                    // no con el booleano: hay que mandarla como texto.
                    valoresSeleccionados[`select_${t}_${i * 3}`]     = f.tieneRecintos ? f.recintos : 'false';
                    valoresSeleccionados[`select_${t}_${i * 3 + 1}`] = f.estado;
                    valoresSeleccionados[`select_${t}_${i * 3 + 2}`] = f.causal;

                    filas.push([
                        f.id, f.nombre, f.cedula, f.municipio, f.fecha, f.acta, f.tipo, f.contrato,
                        f.orden, f.ordenExt, f.categoria, f.resultado, f.horaInicio, f.horaFinal,
                        f.duracion,
                        '', '', '',                      // 15-17: los reemplaza el backend
                        f.vence,                         // 18
                        f.rechazo,                       // 19
                        f.periodoGracia,                 // 20
                    ]);

                    if (f.estado === 'OK') {
                        switch (f.resultado) {
                            case 'CERTIFICADA':                                c.certificadaCount++;                       c.totalCount++; break;
                            case 'CERTIFICADA CON NOVEDADES':                  c.certificadaConNovedadesCount++;           c.totalCount++; break;
                            case 'INSPECCIONADA CON DEFECTO CRITICO VALLE':    c.inspeccionadaConDefectoCriticoCount++;    c.totalCount++; break;
                            case 'INSPECCIONADA CON DEFECTO NO CRITICO VALLE': c.inspeccionadaConDefectoNoCriticoCount++;  c.totalCount++; break;
                        }
                    }
                });

                datos.push(filas);
                indicadores.push(c);
            });

            return { valoresSeleccionados, encabezado, datos, indicadores };
        },

        async guardar() {
            this.guardando = true;
            try {
                const res = await window.api(this.urls.guardar, {
                    method: 'POST',
                    body: this.construirPayload(),
                });

                if (res.error) {
                    Swal.fire({ icon: 'warning', title: 'Advertencia', text: res.error });
                    return;
                }
                // Mismo criterio que antes: sin supervisor se descarga el archivo,
                // con supervisor se vuelve al listado.
                if (res.nombre && this.idSuper === null) {
                    window.location.href = res.nombre;
                } else if (res.ruta) {
                    setTimeout(() => { window.location.href = res.ruta; }, 200);
                }
            } catch (e) {
                console.error(e);
                Swal.fire({ icon: 'error', title: 'Error', text: e.data?.error ?? 'No se pudo guardar la bitácora' });
            } finally {
                this.guardando = false;
            }
        },
    }));
});
</script>
