<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('cortesProduccion', ({ cortes, causales, urls }) => ({
        cortes, causales, urls,

        buscarCorte: '',
        buscarCausal: '',

        modal: null,
        error: '',
        invalidos: [],
        enviando: false,
        cargandoFicha: false,

        corte: {},
        causal: {},
        editandoCorte: false,
        editandoCausal: false,

        /* ------------------------------ Listados ----------------------------- */
        get cortesFiltrados() {
            const q = this.buscarCorte.trim().toLowerCase();
            const lista = q === ''
                ? [...this.cortes]
                : this.cortes.filter(c =>
                    String(c.nombre).toLowerCase().includes(q) ||
                    String(c.fecha_inicio).includes(q) ||
                    String(c.fecha_fin).includes(q));

            // Del corte más reciente al más antiguo, como ordenaba la tabla.
            return lista.sort((a, b) => String(b.fecha_inicio).localeCompare(String(a.fecha_inicio)));
        },

        get causalesFiltradas() {
            const q = this.buscarCausal.trim().toLowerCase();
            return q === ''
                ? this.causales
                : this.causales.filter(c => String(c.nombre).toLowerCase().includes(q));
        },

        /* ----------------------------- Utilidades ---------------------------- */
        cerrar() {
            this.modal = null;
            this.error = '';
            this.invalidos = [];
        },

        claseCampo(campo) {
            return this.invalidos.includes(campo)
                ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                : '';
        },

        soloDigitos(v, max) { return String(v).replace(/[^0-9]/g, '').slice(0, max); },

        /* Un error puede llegar como `error` (validación y fallos) o como
           `status` + `message` (los tres choques de fechas de updateCorte). */
        mensajeDe(datos, porDefecto) {
            return datos?.error ?? datos?.message ?? porDefecto;
        },

        aviso(titulo) {
            window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                               title: titulo, timer: 3000, showConfirmButton: false });
        },

        /* ------------------------------- Cortes ------------------------------ */
        corteVacio() {
            return { id: null, nombre: '', fecha_inicio: '', fecha_fin: '', meta: '', dobles: '' };
        },

        async abrirCorte(corte = null) {
            this.corte = this.corteVacio();
            this.editandoCorte = corte !== null;
            this.error = '';
            this.invalidos = [];
            this.modal = 'corte';

            if (!corte) return;

            /* Se pide la ficha al servidor aunque la fila ya esté en pantalla,
               para editar sobre el dato vigente. */
            this.cargandoFicha = true;
            try {
                const r = await window.api(this.urls.editarCorte.replace('__id__', corte.id));
                const c = Array.isArray(r) ? r[0] : r;
                this.corte = {
                    id: corte.id,
                    nombre: c.nombre ?? '',
                    fecha_inicio: c.fecha_inicio ?? '',
                    fecha_fin: c.fecha_fin ?? '',
                    meta: String(c.meta ?? ''),
                    dobles: String(c.dobles ?? ''),
                };
            } catch (e) {
                this.error = this.mensajeDe(e?.data, 'No se pudo cargar el corte.');
            } finally {
                this.cargandoFicha = false;
            }
        },

        /* Las mismas reglas que valida el servidor, salvo el solapamiento de
           fechas, que solo él puede comprobar. */
        revisarCorte() {
            this.invalidos = ['nombre', 'fecha_inicio', 'fecha_fin', 'meta', 'dobles']
                .filter(campo => !String(this.corte[campo] ?? '').trim());
            if (this.invalidos.length) return 'Complete todos los campos.';

            if (this.corte.fecha_inicio > this.corte.fecha_fin) {
                this.invalidos = ['fecha_inicio', 'fecha_fin'];
                return 'La fecha de inicio no puede ser mayor a la fecha de fin.';
            }
            if (this.corte.fecha_inicio === this.corte.fecha_fin) {
                this.invalidos = ['fecha_inicio', 'fecha_fin'];
                return 'La fecha de inicio no puede ser igual a la fecha de fin.';
            }
            if (Number(this.corte.meta) > 250) {
                this.invalidos = ['meta'];
                return 'La meta no puede superar 250.';
            }
            if (Number(this.corte.dobles) > 50) {
                this.invalidos = ['dobles'];
                return 'El umbral de dobles no puede superar 50.';
            }
            return '';
        },

        async guardarCorte() {
            this.error = this.revisarCorte();
            if (this.error) return;

            const cuerpo = {
                nombre: this.corte.nombre.trim(),
                fecha_inicio: this.corte.fecha_inicio,
                fecha_fin: this.corte.fecha_fin,
                meta: this.corte.meta,
                dobles: this.corte.dobles,
            };

            this.enviando = true;
            try {
                const r = this.editandoCorte
                    ? await window.api(this.urls.guardarCorte.replace('__id__', this.corte.id),
                                       { method: 'PUT', body: cuerpo })
                    : await window.api(this.urls.crearCorte, { method: 'POST', body: cuerpo });

                /* updateCorte responde 200 con `status` cuando rechaza las
                   fechas; no es un fallo de red y hay que mirarlo aparte. */
                if (r?.status) {
                    this.error = this.mensajeDe(r, 'No se pudo guardar el corte.');
                    return;
                }

                const guardado = r.success;
                if (this.editandoCorte) {
                    const fila = this.cortes.find(c => String(c.id) === String(guardado.id));
                    if (fila) Object.assign(fila, this.aCorte(guardado));
                } else {
                    this.cortes.push(this.aCorte(guardado));
                }

                this.cerrar();
                this.aviso(this.editandoCorte ? 'Corte actualizado' : 'Corte creado');
            } catch (e) {
                this.error = this.mensajeDe(e?.data, 'No se pudo guardar el corte.');
            } finally {
                this.enviando = false;
            }
        },

        aCorte(c) {
            return {
                id: c.id, nombre: c.nombre,
                fecha_inicio: c.fecha_inicio, fecha_fin: c.fecha_fin,
                meta: c.meta, dobles: c.dobles,
            };
        },

        /* ------------------------------ Causales ----------------------------- */
        async abrirCausal(causal = null) {
            this.causal = { id: null, nombre: '' };
            this.editandoCausal = causal !== null;
            this.error = '';
            this.invalidos = [];
            this.modal = 'causal';

            if (!causal) return;

            this.cargandoFicha = true;
            try {
                const r = await window.api(this.urls.editarCausal.replace('__id__', causal.id));
                const c = Array.isArray(r) ? r[0] : r;
                this.causal = { id: causal.id, nombre: c.nom_causal ?? '' };
            } catch (e) {
                this.error = this.mensajeDe(e?.data, 'No se pudo cargar la causal.');
            } finally {
                this.cargandoFicha = false;
            }
        },

        async guardarCausal() {
            const nombre = String(this.causal.nombre ?? '').trim();
            if (!nombre) {
                this.invalidos = ['nombre'];
                this.error = 'Escribe el nombre de la causal.';
                return;
            }

            this.enviando = true;
            this.error = '';
            try {
                // El alta espera `nom_causal` y la edición `nombre`.
                const r = this.editandoCausal
                    ? await window.api(this.urls.guardarCausal.replace('__id__', this.causal.id),
                                       { method: 'PUT', body: { nombre } })
                    : await window.api(this.urls.crearCausal,
                                       { method: 'POST', body: { nom_causal: nombre } });

                const guardada = r.causal;
                if (this.editandoCausal) {
                    const fila = this.causales.find(c => String(c.id) === String(guardada.id));
                    if (fila) fila.nombre = guardada.nom_causal;
                } else {
                    this.causales.push({ id: guardada.id, nombre: guardada.nom_causal,
                                         activa: guardada.status !== 0, fija: false });
                }

                this.cerrar();
                this.aviso(r.success ?? 'Causal guardada');
            } catch (e) {
                this.error = this.mensajeDe(e?.data, 'No se pudo guardar la causal.');
            } finally {
                this.enviando = false;
            }
        },

        async cambiarEstadoCausal(causal) {
            const r = await window.Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: causal.activa ? 'Esto desactivará la causal.' : 'Esto activará la causal.',
                showCancelButton: true,
                confirmButtonText: causal.activa ? 'Sí, desactivar' : 'Sí, activar',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed) return;

            try {
                const resp = await window.api(this.urls.estadoCausal,
                                              { method: 'POST', body: { id: causal.id } });
                // El servidor devuelve el estado ya alternado; se refleja tal cual.
                causal.activa = Number(resp.causal.status) === 1;
                this.aviso(resp.success ?? 'Estado actualizado');
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: this.mensajeDe(e?.data, 'No se pudo cambiar el estado.') });
            }
        },
    }));
});
</script>
