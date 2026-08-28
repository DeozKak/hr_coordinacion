<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('controlStickers', ({ tipos, inventarioInicial, inspectores, urls }) => ({
        tipos, inspectores, urls,
        inventario: inventarioInicial,

        modal: null,

        /* Listado: reemplaza a DataTables (búsqueda + paginación de 10, como lengthChange:false). */
        busqueda: '',
        pagina: 1,
        porPagina: 10,
        orden: 'nombre',
        direccion: 'asc',

        agregar:    { tipo: '', cantidad: '', serialInicio: '', serialFin: '', error: '', enviando: false },
        asignar:    { id: null, nombre: '', cantidades: {}, serialInicio: '', serialFin: '',
                      disponible: {}, error: '', enviando: false },
        desasignar: { id: null, nombre: '', cantidades: {}, serialInicio: '', serialFin: '',
                      asignado: {}, error: '', enviando: false, cargando: false },
        seriales:   { titulo: '', subtitulo: '', vacio: '', rangos: [], cargando: false, error: '' },

        /* ------------------------------- Resumen ------------------------------ */
        get totalInventario() {
            return Object.values(this.inventario).reduce((a, b) => a + (Number(b) || 0), 0);
        },
        get totalAsignado() {
            return this.inspectores.reduce((a, i) => a + (Number(i.total) || 0), 0);
        },
        get conAsignacion() {
            return this.inspectores.filter(i => i.total > 0).length;
        },

        /* ------------------------------- Listado ------------------------------ */
        get filtrados() {
            const q = this.busqueda.trim().toLowerCase();
            const lista = q
                ? this.inspectores.filter(i => i.nombre.toLowerCase().includes(q))
                : this.inspectores.slice();

            lista.sort((a, b) => {
                const r = this.orden === 'total'
                    ? a.total - b.total
                    : a.nombre.localeCompare(b.nombre, 'es');
                return this.direccion === 'asc' ? r : -r;
            });
            return lista;
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
                this.direccion = campo === 'total' ? 'desc' : 'asc';
            }
            this.pagina = 1;
        },
        iniciales(nombre) {
            return String(nombre || '').trim().split(/\s+/).slice(0, 2)
                .map(p => p.charAt(0)).join('').toUpperCase();
        },

        /* -------------------------------- Utiles ------------------------------ */
        cerrar() { this.modal = null; },

        /* Equivale al replace(/[^0-9]/g,'') + tope de los listeners `input`/`paste`. */
        soloDigitos(valor, max = null) {
            let v = String(valor ?? '').replace(/[^0-9]/g, '');
            if (v === '') return '';
            if (max !== null && parseInt(v, 10) > Number(max)) v = String(Number(max));
            return v;
        },

        /* El backend puede responder `error` como string o como bolsa de validación. */
        mensajeError(e, respaldo) {
            const d = e?.data ?? e;
            if (d && typeof d === 'object') {
                if (typeof d.error === 'string') return d.error;
                if (d.error && typeof d.error === 'object') {
                    return Object.values(d.error).flat().join(' ');
                }
                if (typeof d.message === 'string') return d.message;
            }
            return e instanceof Error && e.message !== 'error' ? e.message : respaldo;
        },

        avisar(titulo) {
            window.Swal.fire({
                position: 'top-end', icon: 'success', title: titulo,
                showConfirmButton: false, toast: true, timer: 3000,
            });
        },

        errorRango: '',
        /* Devuelve null (ambos vacíos), el rango, o false dejando el motivo en errorRango. */
        rangoSeriales(inicio, fin) {
            const i = String(inicio ?? '').trim();
            const f = String(fin ?? '').trim();
            if (!i && !f) return null;
            if (!i || !f) {
                this.errorRango = 'Debe llenar tanto el serial inicial como el final para ACTAS, o dejar ambos vacíos.';
                return false;
            }
            if (parseInt(f, 10) < parseInt(i, 10)) {
                this.errorRango = 'El serial final de ACTA debe ser mayor o igual al inicial.';
                return false;
            }
            return { serial_inicio: i, serial_fin: f };
        },

        /* ------------------------ Agregar a inventario ------------------------ */
        get agregarEsActa() {
            const t = this.tipos.find(t => t.id === this.agregar.tipo);
            return !!t && t.esActa;
        },
        get totalSerialesAgregar() {
            const a = parseInt(this.agregar.serialInicio, 10);
            const b = parseInt(this.agregar.serialFin, 10);
            return Number.isFinite(a) && Number.isFinite(b) && b >= a ? b - a + 1 : 0;
        },

        abrirAgregar() {
            this.agregar = { tipo: '', cantidad: '', serialInicio: '', serialFin: '', error: '', enviando: false };
            this.modal = 'agregar';
        },

        async enviarAgregar() {
            const a = this.agregar;
            a.error = '';

            if (!a.tipo) { a.error = 'Debe seleccionar un tipo de sticker.'; return; }

            let payload;
            if (this.agregarEsActa) {
                if (!a.serialInicio || !a.serialFin ||
                    parseInt(a.serialFin, 10) < parseInt(a.serialInicio, 10)) {
                    a.error = 'Debe ingresar un rango de seriales válido (el final debe ser mayor o igual al inicial).';
                    return;
                }
                payload = { serial_inicio: a.serialInicio, serial_fin: a.serialFin };
            } else {
                const cantidad = parseInt(a.cantidad, 10);
                if (isNaN(cantidad) || cantidad < 1) {
                    a.error = 'Debe ingresar una cantidad válida (mayor a 0).';
                    return;
                }
                payload = { cantidad };
            }

            a.enviando = true;
            try {
                const data = await window.api(this.urls.actualizarInventario.replace(':id', a.tipo),
                    { method: 'POST', body: payload });

                if (!data.success) throw Object.assign(new Error('error'), { data });

                this.inventario[a.tipo] = Number(data.value) || 0;
                this.avisar(data.success);
                this.cerrar();
                this.recargar();
            } catch (e) {
                a.error = this.mensajeError(e, 'No se pudo actualizar el inventario');
                a.enviando = false;
            }
        },

        /* ------------------------------ Asignar ------------------------------- */
        saldoAsignar(t) {
            const inventario = Number(this.asignar.disponible[t.id] ?? 0);
            if (t.esActa) return inventario;   // el original tampoco descuenta el rango en vivo
            const cantidad = parseInt(this.asignar.cantidades[t.id], 10) || 0;
            return Math.max(0, inventario - cantidad);
        },

        async abrirAsignar(insp) {
            this.asignar = {
                id: insp.id, nombre: insp.nombre, cantidades: {},
                serialInicio: '', serialFin: '',
                disponible: { ...this.inventario },
                error: '', enviando: false,
            };
            this.modal = 'asignar';

            // El original refrescaba el inventario antes de abrir el modal; si falla,
            // se sigue trabajando con los saldos ya renderizados.
            try {
                const data = await window.api(this.urls.inventario);
                const fresco = {};
                for (const item of data) fresco[String(item.id)] = Number(item.inventario) || 0;
                this.asignar.disponible = fresco;
                this.inventario = { ...this.inventario, ...fresco };
            } catch (e) {
                console.error('No se pudo actualizar inventario', e);
            }
        },

        async enviarAsignar() {
            const a = this.asignar;
            a.error = '';

            const stickers = {};
            for (const t of this.tipos) {
                if (t.esActa) continue;                 // ACTAS viaja aparte, en seriales_acta
                const v = parseInt(a.cantidades[t.id], 10) || 0;
                if (v > 0) stickers[t.id] = v;
            }

            const seriales = this.rangoSeriales(a.serialInicio, a.serialFin);
            if (seriales === false) { a.error = this.errorRango; return; }

            if (!a.id || (Object.keys(stickers).length === 0 && !seriales)) {
                a.error = 'Debes asignar al menos un sticker o un rango de actas.';
                return;
            }

            a.enviando = true;
            try {
                const data = await window.api(this.urls.asignar, {
                    method: 'POST',
                    body: { idInspector: a.id, stickers, seriales_acta: seriales },
                });
                if (!data.success) throw Object.assign(new Error('error'), { data });

                this.avisar(data.success);
                this.cerrar();
                this.recargar();
            } catch (e) {
                a.error = this.mensajeError(e, 'Error al asignar');
                a.enviando = false;
            }
        },

        /* ----------------------------- Desasignar ----------------------------- */
        async abrirDesasignar(insp) {
            this.desasignar = {
                id: insp.id, nombre: insp.nombre, cantidades: {},
                serialInicio: '', serialFin: '',
                asignado: {}, error: '', enviando: false, cargando: true,
            };
            this.modal = 'desasignar';

            try {
                const data = await window.api(this.urls.stickersAsignados.replace(':id', insp.id));
                const mapa = {};
                for (const r of data) mapa[String(r.id_sticker_tipo)] = Number(r.cantidad_asignada) || 0;
                this.desasignar.asignado = mapa;
            } catch (e) {
                // Igual que el original: sin datos no se habilita ninguna entrada.
                console.error('No se pudieron obtener los stickers asignados', e);
                this.desasignar.error = 'No se pudieron obtener los stickers asignados del inspector.';
            } finally {
                this.desasignar.cargando = false;
            }
        },

        async enviarDesasignar() {
            const d = this.desasignar;
            d.error = '';

            const stickers = {};
            for (const t of this.tipos) {
                if (t.esActa) continue;
                const v = parseInt(d.cantidades[t.id], 10) || 0;
                if (v > 0) stickers[t.id] = v;
            }

            const seriales = this.rangoSeriales(d.serialInicio, d.serialFin);
            if (seriales === false) { d.error = this.errorRango; return; }

            if (!d.id || (Object.keys(stickers).length === 0 && !seriales)) {
                d.error = 'Debes desasignar al menos un sticker o un rango de actas.';
                return;
            }

            d.enviando = true;
            try {
                const data = await window.api(this.urls.desasignar, {
                    method: 'POST',
                    body: { idInspector: d.id, stickers, seriales_acta: seriales },
                });
                if (!data.success) throw Object.assign(new Error('error'), { data });

                this.avisar(data.success);
                this.cerrar();
                this.recargar();
            } catch (e) {
                d.error = this.mensajeError(e, 'Error al desasignar');
                d.enviando = false;
            }
        },

        /* --------------------------- Seriales de actas ------------------------ */
        verSerialesInventario() {
            this.seriales = {
                titulo: 'Seriales de actas en inventario',
                subtitulo: 'Rangos disponibles para asignar',
                vacio: 'No hay seriales de Actas en inventario.',
                rangos: [], cargando: true, error: '',
            };
            this.modal = 'seriales';
            return this.cargarSeriales(this.urls.serialesInventario);
        },

        verSerialesInspector(insp) {
            this.seriales = {
                titulo: 'Seriales asignados',
                subtitulo: insp.nombre,
                vacio: 'Este inspector no tiene seriales de Actas asignados.',
                rangos: [], cargando: true, error: '',
            };
            this.modal = 'seriales';
            return this.cargarSeriales(this.urls.serialesAsignados.replace(':id', insp.id));
        },

        async cargarSeriales(url) {
            try {
                const data = await window.api(url);
                if (!Array.isArray(data.rangos)) throw Object.assign(new Error('error'), { data });
                this.seriales.rangos = data.rangos;
            } catch (e) {
                this.seriales.error = this.mensajeError(e, 'No se recibieron datos válidos');
            } finally {
                this.seriales.cargando = false;
            }
        },

        /* Toda mutación termina recargando: el histórico y los totales los arma el servidor. */
        recargar() {
            setTimeout(() => window.location.reload(), 1500);
        },
    }));
});
</script>
