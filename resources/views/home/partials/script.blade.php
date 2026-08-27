<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('dashboard', ({ detalles, programaciones, meses, tecnicos }) => ({
        // Fuentes de datos (inyectadas desde Blade)
        detalles, programaciones, meses, tecnicos,

        modal: null,

        // --- Estado de la tabla de los modales de detalle ---
        // Solo hay un modal abierto a la vez, así que comparten estado.
        // Vive aquí y no en un x-data anidado: así no depende de $parent.
        filas: [],
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

        // --- Asignación ---
        asigLocalidad: '',
        asigSeleccionados: [],
        asigBusqueda: '',

        // --- Cargue de archivos ---
        archivos: { archivo_asignacion: '', archivo_cerradas: '' },
        subiendo: false,

        init() {
            this.dibujarGrafica();
            // El botón "Cargar Datos OSF" vive en la cabecera, fuera de este componente.
            window.addEventListener('abrir-cargue', () => (this.modal = 'cargue'));
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

        abrirAsignacion(localidad = '', ids = []) {
            this.asigLocalidad = localidad;
            this.asigSeleccionados = ids.map(Number);
            this.asigBusqueda = '';
            this.modal = 'asignacion';
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
            if (!canvas || !this.tieneMeses || typeof Chart === 'undefined') return;

            // "Sin mes" siempre al final; el resto en orden numérico.
            const etiquetas = Object.keys(this.meses).sort((a, b) => {
                if (a === 'Sin mes') return 1;
                if (b === 'Sin mes') return -1;
                return parseInt(a) - parseInt(b);
            });

            const oscuro = document.documentElement.classList.contains('dark');
            const rejilla = oscuro ? 'rgba(148,163,184,0.15)' : 'rgba(100,116,139,0.12)';
            const texto = oscuro ? '#94a3b8' : '#64748b';

            new Chart(canvas.getContext('2d'), {
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
