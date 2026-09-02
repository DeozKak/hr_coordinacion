<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('parametrizarPrecios', ({ registros, urls }) => ({
        registros, urls,

        /* Las seis zonas más la industrial, en el orden de la tabla. */
        CAMPOS: ['res_metro', 'res_norte', 'res_cauca',
                 'com_metro', 'com_norte', 'com_cauca', 'inspeccion_industrial'],

        busqueda: '',
        form: {},
        editando: false,
        enviando: false,
        error: '',
        invalidos: [],

        formato: new Intl.NumberFormat('es-CO'),

        init() {
            this.form = this.formVacio();
        },

        /* ------------------------------ Listado ------------------------------ */
        get filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            const lista = q === ''
                ? [...this.registros]
                : this.registros.filter(p =>
                    String(p.id).includes(q) ||
                    String(p.fecha_inicio).includes(q) ||
                    String(p.fecha_fin).includes(q));

            // Del periodo más reciente al más antiguo.
            return lista.sort((a, b) => String(b.fecha_inicio).localeCompare(String(a.fecha_inicio)));
        },

        /* ----------------------------- Utilidades ---------------------------- */
        formVacio() {
            return {
                id: null, fecha_inicio: '', fecha_fin: '',
                res_metro: '', res_norte: '', res_cauca: '',
                com_metro: '', com_norte: '', com_cauca: '',
                inspeccion_industrial: '',
            };
        },

        moneda(valor) {
            return valor === '' || valor === null || valor === undefined
                ? '' : this.formato.format(valor);
        },

        soloDigitos(v) {
            const limpio = String(v).replace(/[^0-9]/g, '');
            return limpio === '' ? '' : Number(limpio);
        },

        claseCampo(campo) {
            return this.invalidos.includes(campo)
                ? 'border-red-400 focus:border-red-500 focus:ring-red-500 dark:border-red-500'
                : '';
        },

        limpiar() {
            this.form = this.formVacio();
            this.editando = false;
            this.error = '';
            this.invalidos = [];
        },

        cancelarEdicion() { this.limpiar(); },

        editar(registro) {
            /* Se copia el registro tal cual. La versión anterior leía los valores
               de las celdas de la tabla y los desformateaba de "$ 50,584" a
               número, lo que ataba la edición al orden de las columnas. */
            this.form = { ...registro };
            this.editando = true;
            this.error = '';
            this.invalidos = [];
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /* ----------------------------- Validación ---------------------------- */
        /* Las mismas reglas que aplica el servidor, salvo el solapamiento de
           periodos, que solo él puede comprobar. */
        revisar() {
            this.invalidos = [];

            if (!this.form.fecha_inicio || !this.form.fecha_fin) {
                this.invalidos = ['fecha_inicio', 'fecha_fin'].filter(c => !this.form[c]);
                return 'Las fechas son obligatorias.';
            }
            if (this.form.fecha_fin < this.form.fecha_inicio) {
                this.invalidos = ['fecha_inicio', 'fecha_fin'];
                return 'La fecha de fin debe ser posterior a la de inicio.';
            }

            this.invalidos = this.CAMPOS.filter(c => this.form[c] === '' || this.form[c] === null);
            if (this.invalidos.length) return 'Todos los precios son obligatorios.';

            return '';
        },

        /* ------------------------------ Guardado ----------------------------- */
        cuerpo() {
            const datos = {
                fechaPrecioInicio: this.form.fecha_inicio,
                fechaPrecioFin: this.form.fecha_fin,
                metroRes: this.form.res_metro,
                norteRes: this.form.res_norte,
                caucaRes: this.form.res_cauca,
                metroCom: this.form.com_metro,
                norteCom: this.form.com_norte,
                caucaCom: this.form.com_cauca,
                inspeccionInd: this.form.inspeccion_industrial,
            };
            if (this.editando) datos.id = this.form.id;
            return datos;
        },

        /* El endpoint contesta siempre 200 con un `status` numérico. */
        mensajeDe(r) {
            switch (Number(r?.status)) {
                case 1: return { icon: 'warning', texto: 'Las fechas son obligatorias.' };
                case 2: return { icon: 'warning', texto: 'La fecha de inicio no puede ser mayor a la de fin.' };
                case 3: return { icon: 'warning', texto: 'Los datos ingresados no son válidos.' };
                case 4: return { icon: 'warning',
                                 texto: `Ya existe un periodo que se cruza con este: #${r.id}, `
                                      + `de ${r.fecha_inicio} a ${r.fecha_fin}.` };
                case 5: return { icon: 'success',
                                 texto: this.editando ? 'Los datos se actualizaron correctamente.'
                                                      : 'Los datos se guardaron correctamente.' };
                case 6: return { icon: 'error', texto: 'Error al guardar los datos.' };
                case 7: return { icon: 'warning', texto: 'No se realizaron cambios.' };
                default: return { icon: 'error', texto: 'Respuesta no reconocida del servidor.' };
            }
        },

        async enviar() {
            this.error = this.revisar();
            if (this.error) return;

            const confirmacion = await window.Swal.fire({
                icon: 'question',
                title: this.editando ? '¿Actualizar el periodo?' : '¿Guardar el periodo?',
                text: `${this.form.fecha_inicio} a ${this.form.fecha_fin}`,
                showCancelButton: true,
                confirmButtonText: 'Guardar',
                cancelButtonText: 'Cancelar',
            });
            if (!confirmacion.isConfirmed) return;

            this.enviando = true;
            try {
                const r = await window.api(this.editando ? this.urls.actualizar : this.urls.guardar,
                                           { method: 'POST', body: this.cuerpo() });
                const { icon, texto } = this.mensajeDe(r);

                if (icon !== 'success') {
                    // El motivo se queda a la vista, sin ventana encima.
                    this.error = texto;
                    return;
                }

                await window.Swal.fire({ icon: 'success', title: 'Listo', text: texto });
                window.location.reload();
            } catch (e) {
                this.error = 'Hubo un problema al guardar los datos.';
            } finally {
                this.enviando = false;
            }
        },
    }));
});
</script>
