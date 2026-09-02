<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('parametrosNomina', ({ registros, urls }) => ({
        registros, urls,

        DINERO: ['salario_minimo', 'auxilio_transporte'],
        PORCENTAJES: ['salud', 'pension', 'arl', 'caja', 'prima',
                      'cesantias', 'intCesantias', 'vacaciones'],

        busqueda: '',
        form: {},
        editando: false,
        enviando: false,
        error: '',
        invalidos: [],

        formato: new Intl.NumberFormat('es-CO'),

        init() { this.form = this.formVacio(); },

        /* ------------------------------ Listado ------------------------------ */
        get filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            const lista = q === ''
                ? [...this.registros]
                : this.registros.filter(p =>
                    String(p.id).includes(q) ||
                    String(p.fecha_inicio).includes(q) ||
                    String(p.fecha_fin).includes(q));

            return lista.sort((a, b) => String(b.fecha_inicio).localeCompare(String(a.fecha_inicio)));
        },

        /* ----------------------------- Utilidades ---------------------------- */
        get CAMPOS() { return [...this.DINERO, ...this.PORCENTAJES]; },

        formVacio() {
            const vacio = { id: null, fecha_inicio: '', fecha_fin: '' };
            for (const c of this.CAMPOS) vacio[c] = '';
            return vacio;
        },

        moneda(v) {
            return v === '' || v === null || v === undefined ? '' : this.formato.format(v);
        },

        porcentaje(v) {
            return v === '' || v === null || v === undefined ? '' : `${v} %`;
        },

        soloDigitos(v) {
            const limpio = String(v).replace(/[^0-9]/g, '');
            return limpio === '' ? '' : Number(limpio);
        },

        /* Los porcentajes admiten decimales, con un solo punto. */
        soloDecimal(v) {
            let limpio = String(v).replace(/[^0-9.]/g, '');
            const partes = limpio.split('.');
            if (partes.length > 2) limpio = partes[0] + '.' + partes.slice(1).join('');
            return limpio;
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
               de las celdas de la tabla y los desformateaba de "$ 1,423,500" y
               "%8.5", lo que ataba la edición al orden de las columnas. */
            this.form = { ...registro };
            this.editando = true;
            this.error = '';
            this.invalidos = [];
            window.scrollTo({ top: 0, behavior: 'smooth' });
        },

        /* ----------------------------- Validación ---------------------------- */
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

            const vacio = (v) => v === '' || v === null || v === undefined;
            this.invalidos = this.CAMPOS.filter(c => vacio(this.form[c]));
            if (this.invalidos.length) return 'Todos los valores son obligatorios.';

            // Un porcentaje fuera de 0–100 es siempre un error de tecleo.
            this.invalidos = this.PORCENTAJES.filter(c => Number(this.form[c]) > 100);
            if (this.invalidos.length) return 'Los porcentajes no pueden superar 100.';

            return '';
        },

        /* ------------------------------ Guardado ----------------------------- */
        cuerpo() {
            const datos = {
                fechaSalAuxInicio: this.form.fecha_inicio,
                fechaSalAuxFin: this.form.fecha_fin,
                salMin: this.form.salario_minimo,
                auxTrans: this.form.auxilio_transporte,
            };
            for (const c of this.PORCENTAJES) datos[c] = this.form[c];
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
