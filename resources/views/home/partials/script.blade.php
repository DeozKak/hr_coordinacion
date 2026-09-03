<script>
document.addEventListener('alpine:init', () => {

    /* El filtro de fecha y municipio se pinta en la cabecera, fuera del x-data
       del tablero, así que no pueden verse entre ellos. El store es el punto de
       encuentro: la cabecera escribe lo elegido y el tablero lo lee. */
    Alpine.store('reporte', {
        fecha: @js($fechaReporte),
        localidad: @js($localidadSeleccionada),
        localidades: @js($localidadesDisponibles),
        cargando: false,
    });

    /* Ojo al desestructurar: lo que no se nombre aquí se pierde por el camino,
       aunque la vista lo mande. Cada clave nueva del payload tiene que
       aparecer en las dos líneas. */
    Alpine.data('dashboard', ({ detalles, programaciones, meses, tecnicos,
                                progInicial, urlProgramaciones, metricas, urlReporte,
                                fuerzaInicial, urlAsignacion }) => ({
        // Fuentes de datos (inyectadas desde Blade)
        detalles, programaciones, meses, tecnicos, progInicial, urlProgramaciones,
        metricas, urlReporte, fuerzaInicial, urlAsignacion,

        modal: null,

        // --- Estado de la tabla de los modales de detalle ---
        // Solo hay un modal abierto a la vez, así que comparten estado.
        // Vive aquí y no en un x-data anidado: así no depende de $parent.
        filas: [],

        // Instancia de Chart.js, fuera del proxy reactivo de Alpine.
        grafica: null,

        /* ---- Filtro propio de la tarjeta de programaciones ----
           Va aparte del de la cabecera: consulta por FECHA_AGENDAMIENTO y
           CIUDAD de tbl_programacion_contratos y refresca sólo esta tabla. */
        progFecha: '',
        progCiudad: 'TODAS',
        progCiudades: [],
        progFilas: [],
        progTotales: { programadas: 0, ejecutadas: 0, pendientes: 0 },
        progFechaAplicada: '',
        progCiudadAplicada: 'TODAS',
        progCargando: false,
        progError: '',
        tablaBusqueda: '',
        tablaPagina: 1,
        tablaPorPagina: 15,
        tablaOrden: { key: null, dir: 'asc' },

        // --- Detalle del reporte diario ---
        detalleTitulo: '',

        // --- Detalle de programaciones ---
        progTitulo: '',
        progEstado: 'ejecutadas',

        // --- Técnicos por localidad ---
        tecnicosLocalidad: '',
        tecnicosLista: [],

        // --- Fuerza de trabajo por localidad ---
        fuerza: [],

        // --- Asignación ---
        asigLocalidad: '',
        asigSeleccionados: [],
        asigBusqueda: '',
        asigGuardando: false,

        // --- Cargue de archivos ---
        archivos: { archivo_asignacion: '', archivo_cerradas: '' },
        subiendo: false,

        init() {
            this.dibujarGrafica();
            // El botón "Cargar Datos OSF" vive en la cabecera, fuera de este componente.
            window.addEventListener('abrir-cargue', () => (this.modal = 'cargue'));

            /* La tarjeta de programaciones arranca con lo que pintó el servidor
               para el filtro principal; a partir de ahí va por su cuenta. */
            this.progFilas = this.progInicial.filas;
            this.progTotales = this.progInicial.totales;
            this.progFecha = this.progFechaAplicada = this.progInicial.fecha;
            this.progCiudad = this.progCiudadAplicada = this.progInicial.ciudad;
            this.progCiudades = this.progInicial.ciudades ?? [];

            /* La tarjeta de fuerza de trabajo arranca con lo que pintó el
               servidor y a partir de ahí la reemplaza cada guardado. */
            this.fuerza = this.fuerzaInicial ?? [];
        },

        get filasFiltradas() {
            const q = this.tablaBusqueda.trim().toLowerCase();
            const out = q
                ? this.filas.filter(f => Object.values(f).join(' ').toLowerCase().includes(q))
                : this.filas;

            if (!this.tablaOrden.key) return out;

            const { key, dir } = this.tablaOrden;
            return [...out].sort((a, b) => {
                const x = a[key] ?? '', y = b[key] ?? '';
                const n = Number(x), m = Number(y);
                const cmp = (x !== '' && y !== '' && !isNaN(n) && !isNaN(m))
                    ? n - m
                    : String(x).localeCompare(String(y), 'es', { numeric: true });
                return cmp * (dir === 'asc' ? 1 : -1);
            });
        },

        get tablaTotalPaginas() {
            return Math.max(1, Math.ceil(this.filasFiltradas.length / this.tablaPorPagina));
        },

        get filasPaginadas() {
            if (this.tablaPagina > this.tablaTotalPaginas) this.tablaPagina = this.tablaTotalPaginas;
            const desde = (this.tablaPagina - 1) * this.tablaPorPagina;
            return this.filasFiltradas.slice(desde, desde + this.tablaPorPagina);
        },

        ordenarPor(key) {
            this.tablaOrden = this.tablaOrden.key === key
                ? { key, dir: this.tablaOrden.dir === 'asc' ? 'desc' : 'asc' }
                : { key, dir: 'asc' };
            this.tablaPagina = 1;
        },

        cargarTabla(filas, { orden = null, porPagina = 15 } = {}) {
            this.filas = filas;
            this.tablaBusqueda = '';
            this.tablaPagina = 1;
            this.tablaPorPagina = porPagina;
            this.tablaOrden = { key: orden, dir: 'asc' };
        },

        get tieneMeses() { return Object.keys(this.meses ?? {}).length > 0; },

        get tecnicosFiltrados() {
            const q = this.asigBusqueda.trim().toLowerCase();
            if (!q) return this.tecnicos;
            return this.tecnicos.filter(t =>
                `${t.nombre} ${t.id}`.toLowerCase().includes(q));
        },

        verDetalle(tipo, titulo) {
            const filas = this.detalles?.[tipo] ?? [];
            this.cargarTabla(filas, { orden: 'operario', porPagina: 15 });
            this.detalleTitulo = `${titulo} (${filas.length} registros)`;
            this.modal = 'detalle';
        },

        /* ---- Filtro de la cabecera, sin recargar ---- */

        get fechaReporteMostrada() {
            const f = Alpine.store('reporte').fecha;
            if (!f) return '';
            // Sin new Date(f): esa cadena se lee en UTC y aquí resta un día.
            const [a, m, d] = f.split('-');
            return `${d}/${m}/${a}`;
        },

        async filtrarReporte() {
            const store = Alpine.store('reporte');

            // Las mismas reglas que valida el servidor, avisadas al momento.
            if (!store.fecha || !/^\d{4}-\d{2}-\d{2}$/.test(store.fecha)) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la fecha',
                                   text: 'Selecciona una fecha para filtrar el reporte.' });
                return;
            }

            store.cargando = true;
            try {
                const p = new URLSearchParams({ fecha: store.fecha, localidad: store.localidad });
                const r = await window.api(`${this.urlReporte}?${p.toString()}`);

                this.metricas = r.metricas ?? {};
                /* Las ventanas de detalle leen de aquí: si no se reemplazaran,
                   al pulsar un indicador saldrían las filas del filtro anterior. */
                this.detalles = r.detalles ?? {};
                this.meses = r.mesesData ?? {};

                /* La lista de municipios depende del día, pero se conserva la
                   elección si sigue existiendo; si no, se vuelve a TODAS. */
                store.localidades = r.localidadesDisponibles ?? [];
                if (store.localidad !== 'TODAS' && !store.localidades.includes(store.localidad)) {
                    store.localidad = 'TODAS';
                }

                this.dibujarGrafica();
            } catch (e) {
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: e?.data?.message ?? 'No se pudo actualizar el reporte.' });
            } finally {
                store.cargando = false;
            }
        },

        formatoMiles(valor) {
            return new Intl.NumberFormat('es-CO').format(Number(valor) || 0);
        },

        get progFechaMostrada() {
            const f = this.progFechaAplicada;
            if (!f) return '';
            // Sin new Date(f): esa cadena se interpreta en UTC y aquí resta un día.
            const [a, m, d] = f.split('-');
            return `${d}/${m}/${a}`;
        },

        // ¿El filtro de la tarjeta se ha separado ya del de la cabecera?
        get progFiltroTocado() {
            return this.progFechaAplicada !== this.progInicial.fecha
                || this.progCiudadAplicada !== this.progInicial.ciudad;
        },

        /* Las mismas reglas que el filtro principal, comprobadas antes de salir:
           el servidor las repite, pero así el aviso es inmediato. */
        revisarProgramaciones() {
            if (!this.progFecha) return 'Selecciona una fecha para filtrar las programaciones.';
            if (!/^\d{4}-\d{2}-\d{2}$/.test(this.progFecha)) {
                return 'La fecha del filtro no tiene un formato válido.';
            }
            return '';
        },

        async filtrarProgramaciones() {
            this.progError = this.revisarProgramaciones();
            if (this.progError) return;

            this.progCargando = true;
            try {
                const p = new URLSearchParams({ fecha: this.progFecha, ciudad: this.progCiudad });
                const r = await window.api(`${this.urlProgramaciones}?${p.toString()}`);

                this.progFilas = r.estadisticas ?? [];
                this.progTotales = r.totales ?? { programadas: 0, ejecutadas: 0, pendientes: 0 };
                /* Las ventanas de detalle leen de aquí, así que se reemplazan
                   también: si no, al pulsar una cifra saldrían las filas del
                   filtro anterior. */
                this.programaciones = r.detalles ?? {};
                this.progFechaAplicada = r.fecha;
                this.progCiudadAplicada = r.ciudad;

                /* La lista depende del día. Se conserva lo elegido si ese
                   municipio sigue teniendo programación; si no, vuelve a TODAS
                   en vez de quedarse filtrando por algo que ya no aparece. */
                this.progCiudades = r.ciudades ?? [];
                if (this.progCiudad !== 'TODAS' && !this.progCiudades.includes(this.progCiudad)) {
                    this.progCiudad = 'TODAS';
                }
            } catch (e) {
                this.progError = e?.data?.message
                    ?? e?.data?.errors?.fecha?.[0]
                    ?? 'No se pudieron obtener las programaciones.';
            } finally {
                this.progCargando = false;
            }
        },

        // Devuelve la tarjeta a lo que muestra el filtro de la cabecera.
        limpiarProgramaciones() {
            this.progFecha = this.progInicial.fecha;
            this.progCiudad = this.progInicial.ciudad;
            this.progError = '';
            return this.filtrarProgramaciones();
        },

        verProgramacion(tipo, estado) {
            const filas = this.programaciones?.[tipo]?.[estado] ?? [];
            this.cargarTabla(filas, { orden: 'tecnico', porPagina: 10 });
            this.progEstado = estado;
            const etiqueta = estado === 'ejecutadas' ? 'Ejecutadas' : 'Pendientes';
            this.progTitulo = `Tareas ${etiqueta} · ID ${tipo} (${filas.length} registros)`;
            this.modal = 'programacion';
        },

        verTecnicos(localidad, lista) {
            this.tecnicosLocalidad = localidad;
            this.tecnicosLista = lista;
            this.modal = 'tecnicos';
        },

        get fuerzaTotal() {
            return this.fuerza.reduce((suma, loc) => suma + Number(loc.total ?? 0), 0);
        },

        abrirAsignacion(localidad = '', ids = []) {
            this.asigLocalidad = localidad;
            this.asigSeleccionados = ids.map(Number);
            this.asigBusqueda = '';
            this.modal = 'asignacion';
        },

        /* Guarda sin recargar: el servidor devuelve la fuerza de trabajo ya
           recalculada y el catálogo de técnicos, que también cambia —mover a
           alguien de localidad cambia su etiqueta de "actualmente en"—. */
        async guardarAsignacion() {
            const localidad = this.asigLocalidad.trim().toUpperCase();

            // La misma regla que valida el servidor, avisada al momento.
            if (!localidad) {
                window.Swal.fire({ icon: 'warning', title: 'Falta la localidad',
                                   text: 'Escribe el nombre de la localidad o municipio.' });
                return;
            }

            this.asigGuardando = true;
            try {
                /* FormData y no JSON: con la lista vacía no se manda ninguna
                   clave `tecnicos[]`, que es justo lo que espera el controlador
                   para dejar la localidad sin nadie. */
                const datos = new FormData();
                datos.append('localidad', localidad);
                this.asigSeleccionados.forEach((id) => datos.append('tecnicos[]', id));

                const r = await window.api(this.urlAsignacion, { method: 'POST', body: datos });

                this.fuerza = r.localidades ?? [];
                this.tecnicos = r.tecnicos ?? this.tecnicos;
                this.modal = null;

                window.Swal.fire({ icon: 'success', title: 'Asignación guardada',
                                   text: r.mensaje ?? '' });
            } catch (e) {
                window.Swal.fire({
                    icon: 'error',
                    title: 'No se pudo guardar',
                    text: e?.data?.message
                        ?? e?.data?.errors?.localidad?.[0]
                        ?? 'Inténtalo de nuevo.',
                });
            } finally {
                this.asigGuardando = false;
            }
        },

        validarCargue(e) {
            const form = e.target;
            const faltantes = ['archivo_asignacion', 'archivo_cerradas']
                .filter(n => !form.elements[n]?.files?.length);

            if (faltantes.length) {
                e.preventDefault();
                Swal.fire({
                    title: 'Archivos incompletos',
                    text: 'Debes seleccionar tanto el archivo de Asignación como el de Cerradas para continuar.',
                    icon: 'warning',
                    confirmButtonText: 'Entendido',
                });
                return;
            }
            this.subiendo = true;   // evita el doble envío
        },

        dibujarGrafica() {
            const canvas = this.$refs.chartMeses;
            if (!canvas || typeof Chart === 'undefined') return;

            /* Se destruye la anterior antes de crear la nueva: Chart.js deja la
               instancia enganchada al canvas y, al refiltrar, se irían apilando
               unas sobre otras con sus escuchas de ratón. */
            this.grafica?.destroy();
            this.grafica = null;

            if (!this.tieneMeses) return;

            // "Sin mes" siempre al final; el resto en orden numérico.
            const etiquetas = Object.keys(this.meses).sort((a, b) => {
                if (a === 'Sin mes') return 1;
                if (b === 'Sin mes') return -1;
                return parseInt(a) - parseInt(b);
            });

            const oscuro = document.documentElement.classList.contains('dark');
            const rejilla = oscuro ? 'rgba(148,163,184,0.15)' : 'rgba(100,116,139,0.12)';
            const texto = oscuro ? '#94a3b8' : '#64748b';

            this.grafica = new Chart(canvas.getContext('2d'), {
                type: 'bar',
                data: {
                    labels: etiquetas,
                    datasets: [{
                        label: 'Cantidad ejecutada',
                        data: etiquetas.map(e => this.meses[e]),
                        backgroundColor: '#10b981',
                        hoverBackgroundColor: '#059669',
                        borderRadius: 6,
                        maxBarThickness: 44,
                    }],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            backgroundColor: '#1a1622',
                            padding: 12,
                            cornerRadius: 10,
                            displayColors: false,
                        },
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            border: { display: false },
                            grid: { color: rejilla },
                            ticks: { precision: 0, color: texto },
                        },
                        x: {
                            border: { display: false },
                            grid: { display: false },
                            ticks: { color: texto },
                        },
                    },
                },
            });
        },
    }));
});
</script>

{{-- Avisos de sesión --}}
@if (session('error') || session('success') || $errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            @if ($errors->any())
                Swal.fire({ title: 'Faltan datos', text: @js($errors->all()).join('\n'), icon: 'warning' });
            @elseif (session('error'))
                Swal.fire({ title: 'Error', text: @js(session('error')), icon: 'error' });
            @else
                Swal.fire({
                    toast: true, position: 'top-end', icon: 'success',
                    title: @js(session('success')),
                    showConfirmButton: false, timer: 4000, timerProgressBar: true,
                });
            @endif
        });
    </script>
@endif
