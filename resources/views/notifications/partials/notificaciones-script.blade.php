<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('gestionNotificaciones', ({ usuarios, urls }) => ({
        usuarios,
        urls,

        busqueda: '',
        orden: { campo: 'name', dir: 'asc' },
        paginaActual: 1,
        porPagina: 10,

        /* `modal` es la bandera que mira la ventana y `editando` el registro.
           Van separadas a propósito: x-trap compara su valor anterior por
           identidad y está escrito para banderas, no para objetos. */
        modal: null,
        editando: null,

        disponibles: [],
        asignadas: [],
        disponiblesSel: [],
        asignadasSel: [],

        nuevaNotificacion: '',
        cargando: false,
        guardando: false,
        error: '',

        /* ------------------------------ Listado ------------------------------ */
        get filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            const filas = q
                ? this.usuarios.filter(u => [u.name, u.email, ...u.roles, ...u.notificaciones]
                    .join(' ').toLowerCase().includes(q))
                : this.usuarios;

            const { campo, dir } = this.orden;
            return [...filas].sort((a, b) =>
                String(a[campo] ?? '').localeCompare(String(b[campo] ?? ''), 'es', { numeric: true })
                * (dir === 'asc' ? 1 : -1));
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.filtrados.length / this.porPagina)); },

        get pagina() {
            // Si el filtro dejó la página actual fuera de rango, vuelve a la última válida.
            if (this.paginaActual > this.totalPaginas) this.paginaActual = this.totalPaginas;
            return this.filtrados.slice((this.paginaActual - 1) * this.porPagina,
                                        this.paginaActual * this.porPagina);
        },

        ordenarPor(campo) {
            this.orden = this.orden.campo === campo
                ? { campo, dir: this.orden.dir === 'asc' ? 'desc' : 'asc' }
                : { campo, dir: 'asc' };
        },

        /* ------------------------------ Ventanas ----------------------------- */
        cerrar() {
            // La bandera primero: el resto son campos que la ventana ya no
            // muestra y no deben cambiar mientras se está cerrando.
            this.modal = null;
            this.editando = null;
            this.error = '';
            this.nuevaNotificacion = '';
        },

        abrirCrear() {
            this.nuevaNotificacion = '';
            this.error = '';
            this.modal = 'crear';
        },

        async abrirEditar(usuario) {
            this.editando = usuario;
            this.modal = 'editar';
            this.error = '';
            this.disponibles = [];
            this.asignadas = [];
            this.disponiblesSel = [];
            this.asignadasSel = [];
            this.cargando = true;

            try {
                const r = await window.api(this.urls.cargar, {
                    method: 'POST',
                    body: { id: usuario.id },
                });
                this.asignadas = (r.asignadas ?? []).map(n => n.Nombre);
                this.disponibles = (r.disponibles ?? []).map(n => n.Nombre);
            } catch (e) {
                this.error = 'No se pudieron cargar las notificaciones.';
            } finally {
                this.cargando = false;
            }
        },

        mover(desde, hacia, seleccion) {
            this[hacia].push(...this[seleccion]);
            this[desde] = this[desde].filter(n => !this[seleccion].includes(n));
            this[seleccion] = [];
        },

        /* ------------------------------ Guardado ----------------------------- */
        async guardar() {
            this.guardando = true;
            this.error = '';
            try {
                /* El servidor espera las dos listas completas: asigna las de una
                   y desasigna las de la otra, así que no basta con enviar lo que
                   se movió. */
                const r = await window.api(this.urls.guardar, {
                    method: 'POST',
                    body: {
                        id: this.editando.id,
                        assignedNotifications: [...this.asignadas],
                        revokedNotifications: [...this.disponibles],
                    },
                });

                if (r.status !== 'success') {
                    this.error = r.message ?? 'No se pudieron actualizar las notificaciones.';
                    return;
                }

                // La tabla se redibuja sola: sólo hay que actualizar el estado.
                this.editando.notificaciones = [...this.asignadas];
                const nombre = this.editando.name;
                this.cerrar();
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: `Notificaciones de ${nombre} actualizadas`,
                                   timer: 3000, showConfirmButton: false });
            } catch (e) {
                this.error = 'Hubo un problema al actualizar las notificaciones.';
            } finally {
                this.guardando = false;
            }
        },

        async crear() {
            const nombre = this.nuevaNotificacion.trim();
            if (!nombre) {
                this.error = 'Debe ingresar un nombre para la notificación.';
                return;
            }

            this.guardando = true;
            this.error = '';
            try {
                const r = await window.api(this.urls.crear, { method: 'POST', body: { nombre } });

                if (r.status !== 'success') {
                    this.error = r.message ?? 'No se pudo crear la notificación.';
                    return;
                }

                /* La versión anterior colgaba aquí una insignia en la fila del
                   usuario conectado, porque el servidor devuelve su id. Crear
                   una notificación no se la asigna a nadie: aparece en la lista
                   de disponibles al editar a alguien. */
                this.cerrar();
                window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                   title: r.message ?? 'Notificación creada',
                                   timer: 3000, showConfirmButton: false });
            } catch (e) {
                this.error = e?.data?.message ?? 'Hubo un problema al crear la notificación.';
            } finally {
                this.guardando = false;
            }
        },
    }));
});
</script>
