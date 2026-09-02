<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('programacionIndex', ({ filas, urls }) => ({
        filas, urls,

        /* --------------------------- Listado --------------------------- */
        busqueda: '',
        orden: 'creado',
        direccion: 'desc',
        pagina: 1,
        porPagina: 10,

        get filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            const lista = q === ''
                ? [...this.filas]
                : this.filas.filter(f =>
                    String(f.id).includes(q) ||
                    f.usuario.toLowerCase().includes(q) ||
                    f.tipo.toLowerCase().includes(q) ||
                    f.creado.includes(q));

            const signo = this.direccion === 'asc' ? 1 : -1;
            return lista.sort((a, b) => {
                const x = a[this.orden], y = b[this.orden];
                // El ID es numérico; el resto ordena como texto y las fechas
                // vienen en Y-m-d, así que el orden alfabético ya es cronológico.
                if (this.orden === 'id') return (x - y) * signo;
                return String(x).localeCompare(String(y), 'es') * signo;
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
            if (this.orden === campo) {
                this.direccion = this.direccion === 'asc' ? 'desc' : 'asc';
            } else {
                this.orden = campo;
                this.direccion = campo === 'creado' ? 'desc' : 'asc';
            }
            this.pagina = 1;
        },

        /* ---------------------- Búsqueda por contrato ------------------- */
        contrato: '',
        resultados: [],
        buscando: false,
        temporizador: null,

        urlVer(id) { return this.urls.verBase.replace('__id__', id) + '?action=view'; },

        buscarConRetraso() {
            clearTimeout(this.temporizador);
            if (this.contrato.trim() === '') {
                this.resultados = [];
                this.buscando = false;
                return;
            }
            this.temporizador = setTimeout(() => this.buscar(), 300);
        },

        async buscar() {
            const consulta = this.contrato.trim();
            this.buscando = true;
            try {
                const datos = await window.api(
                    `${this.urls.buscar}?contrato=${encodeURIComponent(consulta)}`);
                // Una respuesta que llega tarde no debe pisar una búsqueda más nueva.
                if (consulta !== this.contrato.trim()) return;
                this.resultados = Array.isArray(datos) ? datos : [];
            } catch (e) {
                this.resultados = [];
            } finally {
                if (consulta === this.contrato.trim()) this.buscando = false;
            }
        },

        /* ----------------------------- Cargues -------------------------- */
        modal: null,
        enviando: false,
        errores: [],
        estado5: false,
        nombreBase: '',
        nombreTecnicos: '',
        nombreGdo: '',

        cerrarModal() {
            this.modal = null;
            this.errores = [];
        },

        /* Los tres endpoints no devuelven los errores igual: `errors` en base y
           técnicos, `error` en GDO, y unas veces es una cadena y otras el objeto
           del validador. Se normaliza aquí en vez de en cada llamada. */
        mensajesDeError(datos) {
            const bolsa = datos?.errors ?? datos?.error ?? datos;
            if (typeof bolsa === 'string') return [bolsa];
            if (Array.isArray(bolsa)) return bolsa.map(String);
            if (bolsa && typeof bolsa === 'object') {
                return Object.values(bolsa).flat().map(String);
            }
            return ['Ocurrió un error al procesar la solicitud.'];
        },

        async enviarArchivo({ url, ref, tipo, extra = {}, alTerminar }) {
            const archivo = this.$refs[ref]?.files?.[0];
            this.errores = [];

            if (!archivo) {
                this.errores = ['Selecciona un archivo antes de continuar.'];
                return;
            }

            const cuerpo = new FormData();
            cuerpo.append('archivo', archivo);
            cuerpo.append('type', tipo);
            for (const [k, v] of Object.entries(extra)) cuerpo.append(k, v);

            this.enviando = true;
            try {
                const r = await window.api(url, { method: 'POST', body: cuerpo });
                this.cerrarModal();
                await alTerminar(r);
            } catch (e) {
                this.errores = this.mensajesDeError(e.data);
            } finally {
                this.enviando = false;
                // El input es el mismo elemento entre envíos: sin limpiarlo,
                // volver a elegir el mismo archivo no dispararía el change.
                if (this.$refs[ref]) this.$refs[ref].value = '';
            }
        },

        subirBase() {
            return this.enviarArchivo({
                url: this.urls.base, ref: 'archivoBase', tipo: 'base',
                extra: this.estado5 ? { check_estado5: 1 } : {},
                alTerminar: (r) => {
                    this.nombreBase = '';
                    this.estado5 = false;
                    window.Swal.fire({ toast: true, position: 'top-end', icon: 'success',
                                       title: r?.message ?? 'Base cargada', timer: 4000,
                                       showConfirmButton: false });
                },
            });
        },

        subirTecnicos() {
            return this.enviarArchivo({
                url: this.urls.masivos, ref: 'archivoTecnicos', tipo: 'programacion_tec',
                alTerminar: () => { this.nombreTecnicos = ''; window.location.reload(); },
            });
        },

        subirGdo() {
            return this.enviarArchivo({
                url: this.urls.gdo, ref: 'archivoGdo', tipo: 'gdo',
                alTerminar: async (r) => {
                    this.nombreGdo = '';
                    await window.Swal.fire({
                        icon: 'info', title: 'Procesando en segundo plano',
                        text: r?.message ?? 'El archivo se está procesando.',
                    });
                    window.location.reload();
                },
            });
        },
    }));
});
</script>
