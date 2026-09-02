<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('gestionInspectores', ({ filas, supervisores, urls }) => ({
        filas, supervisores, urls,

        /* --------------------------- Listado --------------------------- */
        busqueda: '',
        orden: 'apellidos',
        direccion: 'asc',
        pagina: 1,
        porPagina: 10,

        coincide(lista, texto) {
            const q = String(texto ?? '').trim().toLowerCase();
            if (q === '') return [...lista];
            return lista.filter(f =>
                String(f.id).includes(q) ||
                String(f.nombres).toLowerCase().includes(q) ||
                String(f.apellidos).toLowerCase().includes(q) ||
                String(f.cedula).includes(q) ||
                String(f.supervisor).toLowerCase().includes(q));
        },

        get filtrados() {
            const lista = this.coincide(this.filas, this.busqueda);

            const signo = this.direccion === 'asc' ? 1 : -1;
            return lista.sort((a, b) => {
                if (this.orden === 'id') return (a.id - b.id) * signo;
                return String(a[this.orden]).localeCompare(String(b[this.orden]), 'es') * signo;
            });
        },

        get totalPaginas() {
            return Math.max(1, Math.ceil(this.filtrados.length / this.porPagina));
        },
        get paginaActual() {
            return Math.min(Math.max(1, this.pagina), this.totalPaginas);
        },
        get paginados() {
            const desde = (this.paginaActual - 1) * this.porPagina;
            return this.filtrados.slice(desde, desde + this.porPagina);
        },

        ordenarPor(campo) {
            if (this.orden === campo) this.direccion = this.direccion === 'asc' ? 'desc' : 'asc';
            else { this.orden = campo; this.direccion = 'asc'; }
            this.pagina = 1;
        },

        /* -------------------------- Formulario -------------------------- */
        modal: null,
        error: '',
        invalidos: [],
        enviando: false,
        cargandoFicha: false,
        cargandoDesactivados: false,
        desactivados: [],
        busquedaDesactivados: '',
        form: {},

        formVacio() {
            return { id: '', nombres: '', apellidos: '', type_id: '', cedula: '',
                     supervisor: '', aprendiz: '0' };
        },

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

        /* Mismas restricciones de tecleo que antes: el ID y la cédula solo
           admiten dígitos, y los nombres letras en mayúscula. */
        soloDigitos(v) { return String(v).replace(/[^0-9]/g, ''); },
        soloLetras(v) {
            return String(v).replace(/[^A-Za-zÁÉÍÓÚáéíóúÑñ\s]/g, '').toUpperCase();
        },

        /* --------------------------- Alta nueva -------------------------- */
        abrirCrear() {
            this.form = this.formVacio();
            this.error = '';
            this.invalidos = [];
            this.modal = 'crear';
        },

        async guardarNuevo() {
            const requeridos = { idCrear: this.form.id, nombres: this.form.nombres,
                                 apellidos: this.form.apellidos, type_id: this.form.type_id,
                                 cedula: this.form.cedula, supervisor: this.form.supervisor };

            this.invalidos = Object.entries(requeridos).filter(([, v]) => !v).map(([k]) => k);
            if (this.invalidos.length) {
                this.error = 'Por favor complete todos los campos.';
                return;
            }

            this.enviando = true;
            this.error = '';
            try {
                const r = await window.api(this.urls.crear, { method: 'POST', body: requeridos });
                this.filas.push(this.aFila(r.inspector));
                this.cerrar();
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: r.success ?? 'Inspector creado',
                                   timer: 3000, showConfirmButton: false });
            } catch (e) {
                this.error = e?.data?.error ?? 'No se pudo crear el inspector.';
            } finally {
                this.enviando = false;
            }
        },

        /* ---------------------------- Edición ---------------------------- */
        /* La ficha se pide al servidor aunque la fila ya esté en pantalla:
           así se edita sobre el dato vigente y no sobre el de la carga. */
        async abrirEditar(fila) {
            this.form = { ...this.formVacio(), ...fila, supervisor: String(fila.supervisor_id ?? ''),
                          aprendiz: String(fila.aprendiz) };
            this.error = '';
            this.invalidos = [];
            this.modal = 'editar';
            this.cargandoFicha = true;

            try {
                const r = await window.api(this.urls.datos, { method: 'POST', body: { id: fila.id } });
                const i = r.inspector;
                this.form = {
                    id: i.id, nombres: i.nombres, apellidos: i.apellidos,
                    type_id: i.type_id, cedula: i.cedula,
                    supervisor: String(i.supervisor ?? ''), aprendiz: String(i.aprendiz ?? 0),
                };
            } catch (e) {
                this.error = e?.data?.error ?? 'No se pudieron cargar los datos del inspector.';
            } finally {
                this.cargandoFicha = false;
            }
        },

        async guardarEdicion() {
            const cuerpo = {
                id: this.form.id,
                nombres: this.form.nombres,
                apellidos: this.form.apellidos,
                supervisor: this.form.supervisor,
                aprendiz: this.form.aprendiz,
            };

            this.invalidos = ['nombres', 'apellidos', 'supervisor'].filter(c => !cuerpo[c]);
            if (this.invalidos.length) {
                this.error = 'Por favor complete todos los campos.';
                return;
            }

            this.enviando = true;
            this.error = '';
            try {
                const r = await window.api(this.urls.actualizar, { method: 'POST', body: cuerpo });

                const fila = this.filas.find(f => String(f.id) === String(cuerpo.id));
                if (fila) {
                    fila.nombres = cuerpo.nombres;
                    fila.apellidos = cuerpo.apellidos;
                    fila.supervisor_id = cuerpo.supervisor;
                    fila.supervisor = this.nombreSupervisor(cuerpo.supervisor);
                    fila.aprendiz = Number(cuerpo.aprendiz);
                }

                this.cerrar();
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: r.success ?? 'Inspector actualizado',
                                   timer: 3000, showConfirmButton: false });
            } catch (e) {
                this.error = e?.data?.error ?? 'No se pudo actualizar el inspector.';
            } finally {
                this.enviando = false;
            }
        },

        /* ------------------------- Desactivados -------------------------- */
        /* Mismo criterio de búsqueda que el listado principal, para que quien
           busque en un sitio no tenga que aprender otra cosa en el otro. */
        get desactivadosFiltrados() {
            return this.coincide(this.desactivados, this.busquedaDesactivados);
        },

        async abrirDesactivados() {
            this.modal = 'desactivados';
            this.busquedaDesactivados = '';
            this.cargandoDesactivados = true;
            try {
                const r = await window.api(this.urls.desactivados);
                this.desactivados = (r.inspectores ?? []).map(i => this.aFila(i));
            } catch (e) {
                this.desactivados = [];
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo cargar la lista.' });
            } finally {
                this.cargandoDesactivados = false;
            }
        },

        /* --------------------------- Estado ------------------------------ */
        /* El endpoint alterna el estado; `activar` solo dice de qué lista sale
           la fila y a cuál entra. */
        async cambiarEstado(fila, activar) {
            const r = await window.Swal.fire({
                icon: 'warning',
                title: '¿Estás seguro?',
                text: activar ? 'Esto activará al inspector nuevamente.'
                              : 'Esto desactivará al inspector.',
                showCancelButton: true,
                confirmButtonText: activar ? 'Sí, activar' : 'Sí, desactivar',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed) return;

            try {
                const resp = await window.api(this.urls.estado.replace('__id__', fila.id),
                                              { method: 'POST' });

                if (activar) {
                    this.desactivados = this.desactivados.filter(f => f.id !== fila.id);
                    this.filas.push(this.aFila(resp.inspector, fila));
                } else {
                    this.filas = this.filas.filter(f => f.id !== fila.id);
                }

                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: resp.success ?? 'Estado cambiado',
                                   timer: 3000, showConfirmButton: false });
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.error ?? 'No se pudo cambiar el estado.' });
            }
        },

        /* ---------------------------- Auxiliares -------------------------- */
        nombreSupervisor(id) {
            return this.supervisores.find(s => String(s.id) === String(id))?.nombre ?? '—';
        },

        /* Normaliza lo que devuelve el servidor a la forma de la tabla.
           `previa` sirve cuando la respuesta no trae el supervisor cargado.
           El aprendiz sale del dato real: la versión anterior lo escribía a
           mano como "SI" al crear, aunque los nuevos nacen sin marcar. */
        aFila(inspector, previa = null) {
            return {
                id: inspector.id,
                nombres: inspector.nombres,
                apellidos: inspector.apellidos,
                type_id: inspector.type_id,
                cedula: inspector.cedula,
                supervisor: inspector.supervisor?.name
                    ?? previa?.supervisor
                    ?? this.nombreSupervisor(inspector.SUPERVISOR),
                supervisor_id: inspector.SUPERVISOR ?? previa?.supervisor_id ?? '',
                aprendiz: Number(inspector.aprendiz ?? 0),
            };
        },
    }));
});
</script>
