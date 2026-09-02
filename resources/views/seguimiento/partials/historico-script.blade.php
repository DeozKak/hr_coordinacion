<script>
/* La instancia de Handsontable vive fuera del estado de Alpine: el proxy
   reactivo rompe la identidad que la librería usa internamente. */
let hotHistorico = null;

const HIST_POR_PAGINA = 100;   // lo que devuelve el servidor por página

/* Orden de las columnas. La respuesta viene como objeto con los campos por
   nombre, así que esta lista es la que decide qué se ve y en qué orden. */
const HIST_CAMPOS = [
    'indice', 'orden', 'contrato', 'producto', 'numero_solicitud', 'tipo_solicitud', 'NIT_CC',
    'nombre_lugar', 'departamento', 'localidad', 'sector_operativo', 'direccion', 'consecutivo_ruta',
    'telefono', 'medidor', 'categoria', 'unidad_operativa', 'tipo_trabajo', 'fecha_asignacion',
    'observacion_solicitud',
    'orden_solicitud_externa', 'tipo_solicitud_externa', 'fecha_solicitud_externa',
    'observacion_externa', 'fecha_reasignacion_externa',
    'FECHA_AGENDAMIENTO', 'jornada', 'CELULAR', 'OBSERVACIONES', 'estado_programacion',
    'codigo_tecnico', 'fecha_asignacion_inspector',
    'estado_recepcion', 'fecha_recepcion', 'cantidad_vne', 'ultima_vne', 'fecha_ultima_vne',
    'inspector_ultima_vne', 'compilado_observacion', 'causa_cierre', 'fecha_solicitud_cierre',
    'num_acta', 'validacion_formato', 'observacion_rechazo',
    'dia_ingreso', 'tipo_orden', 'fecha_legalizacion', 'des_causal', 'observacion_legalizacion',
    'cod_causal', 'dias_proceso', 'sede', 'grupo', 'subgrupo', 'meses', 'fecha_vence_certificado',
    'dias_ejecutar', 'cumplimiento_politicas', 'cartera', 'consumo', 'fecha_ult_cert',
    'estado_gestion', 'ult_comentario', 'nom_inspector', 'dias_gestion_actual', 'fecha_actual',
];

const HIST_TITULOS = [
    '#', 'Orden', 'Contrato', 'Producto', 'Numero solicitud', 'Tipo solicitud', 'Cedula', 'Nombre',
    'Departamento', 'Localidad', 'Barrio', 'Dirección', 'Consecutivo Ruta', 'Telefono', 'Medidor',
    'Categoria', 'Unidad', 'Tipo trabajo', 'Fecha asignación', 'Observación solicitud',
    'Orden externa', 'Tipo solicitud', 'Fecha solicitud', 'Observación externa', 'Fecha reasignación',
    'Fecha programación', 'Jornada', 'Telefono usuario', 'Descripción programacion',
    'Estado programación', 'Asignación inspector', 'Fecha asignación inspector',
    'Estado recepción', 'Fecha recepción', '# VNE', 'Estado ultima VNE', 'Fecha ultima VNE',
    'Inspector ultima VNE', 'Compilado observación', 'Causa cierre', 'Fecha solicitud de cierre',
    'Acta real', 'Validación formato', 'Observacion rechazo',
    'Día ingreso', 'Tipo orden', 'Fecha legalización', 'Causal legalización',
    'Observación legalización', 'Consecutivo legalización', 'Días en proceso', 'Sede', 'Grupo',
    'Sub grupo', 'Meses', 'Fecha vence certificado', 'Días para ejecutar', 'Cumplimiento politicas',
    'Cartera', 'Consumo', 'Fecha ultimo certificado', 'Estado gestion', 'Observacion OSF',
    'Nombre inspector', 'Días gestion actual', 'Fecha actual',
];

/* Bloques de la cabecera superior. `hasta` es la última columna de cada bloque.
   El último llegaba sólo hasta la 56 y dejaba nueve columnas sin cabecera de
   grupo: los colspan sumaban 57 de 66. Aquí se derivan de estos rangos, así que
   no se pueden descuadrar. */
const HIST_BLOQUES = [
    { hasta: 19, clave: 'g1', titulo: '1. ASIGNACIÓN BASE OSF' },
    { hasta: 24, clave: 'g2', titulo: '2. INFORMACIÓN COMPLEMENTARIA 12161' },
    { hasta: 29, clave: 'g3', titulo: '3. PROGRAMACIÓN DE ÓRDENES' },
    { hasta: 31, clave: 'g4', titulo: '4. ASIGNACIÓN INSPECTOR' },
    { hasta: 40, clave: 'g5', titulo: '5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO' },
    { hasta: 43, clave: 'g6', titulo: '6. GESTIÓN REALIZADA OFICINA' },
    { hasta: HIST_CAMPOS.length - 1, clave: 'g7', titulo: '7. FORMULACIÓN Y CÁLCULO' },
];

document.addEventListener('alpine:init', () => {
    Alpine.data('historico', ({ url }) => ({
        url,
        cargando: false,
        pagina: 1,
        total: 0,

        init() {
            this.construirTabla();
            this.$watch('$store.ui.dark', () => hotHistorico?.render());
            this.cargar();
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.total / HIST_POR_PAGINA)); },

        /* ------------------------------ Colores ------------------------------ */
        /* Los tonos originales venían de una plantilla de Excel y llevaban el
           texto en blanco a la fuerza: sobre el verde #C4D79B eso daba 1.7:1.
           Aquí son tintes de la paleta y el color del texto se elige por
           contraste, como en el resto de las rejillas. */
        paleta() {
            return Alpine.store('ui').dark ? {
                g1: '#15803d', g2: '#6d28d9', g3: '#9a3412', g4: '#1d4ed8',
                g5: '#b91c1c', g6: '#7e22ce', g7: '#0e7490',
            } : {
                g1: '#bbf7d0', g2: '#ddd6fe', g3: '#fed7aa', g4: '#bfdbfe',
                g5: '#fecaca', g6: '#e9d5ff', g7: '#a5f3fc',
            };
        },

        luminancia(hex) {
            const c = [1, 3, 5].map(i => parseInt(hex.slice(i, i + 2), 16) / 255)
                .map(v => (v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4)));
            return 0.2126 * c[0] + 0.7152 * c[1] + 0.0722 * c[2];
        },
        razon(a, b) {
            const [x, y] = [this.luminancia(a), this.luminancia(b)].sort((p, q) => q - p);
            return (x + 0.05) / (y + 0.05);
        },
        contraste(fondo) {
            return this.razon('#ffffff', fondo) >= this.razon('#0f172a', fondo) ? '#ffffff' : '#0f172a';
        },

        claveDe(columna) {
            return (HIST_BLOQUES.find(b => columna <= b.hasta) ?? HIST_BLOQUES.at(-1)).clave;
        },

        /* ------------------------------- Tabla ------------------------------- */
        cabeceras() {
            let desde = 0;
            const superior = HIST_BLOQUES.map((b) => {
                const fila = { label: b.titulo, colspan: b.hasta - desde + 1 };
                desde = b.hasta + 1;
                return fila;
            });
            return [superior, [...HIST_TITULOS]];
        },

        construirTabla() {
            const self = this;

            hotHistorico = new Handsontable(document.getElementById('tablaHistorico'), {
                data: [],
                nestedHeaders: this.cabeceras(),
                colHeaders: true,
                rowHeaders: false,
                readOnly: true,
                height: '65vh',
                rowHeights: 26,
                wordWrap: false,
                autoWrapRow: false,
                autoWrapCol: false,
                fixedColumnsStart: 5,
                manualColumnResize: true,
                manualRowResize: true,
                licenseKey: 'non-commercial-and-evaluation',

                afterGetColHeader(columna, TH) {
                    if (columna < 0) return;
                    const fondo = self.paleta()[self.claveDe(columna)];
                    /* Con prioridad: el tema de Handsontable fija el fondo de las
                       cabeceras desde su hoja de estilos. */
                    TH.style.setProperty('background-color', fondo, 'important');
                    TH.style.setProperty('color', self.contraste(fondo), 'important');
                    TH.style.fontWeight = '600';
                },
            });
            window.registrarHot?.(hotHistorico);
        },

        /* ------------------------------ Consulta ----------------------------- */
        /* La respuesta trae los registros como objeto con los campos por nombre;
           se aplanan al orden de HIST_CAMPOS. */
        aFilas(datos) {
            return Object.values(datos ?? {}).map(fila => HIST_CAMPOS.map(campo => fila[campo] ?? ''));
        },

        async cargar() {
            this.cargando = true;
            try {
                const r = await window.api(`${this.url}?pagina=${this.pagina}`);
                this.total = Number(r.totalResults) || 0;
                hotHistorico.loadData(this.aFilas(r.data));
                hotHistorico.render();
            } catch (e) {
                console.error('Error al consultar el histórico:', e);
                window.Swal.fire({ icon: 'error', title: 'Error',
                                   text: 'No se pudo obtener el histórico.' });
            } finally {
                this.cargando = false;
            }
        },

        irA(pagina) {
            if (pagina < 1 || pagina > this.totalPaginas || this.cargando) return;
            this.pagina = pagina;
            return this.cargar();
        },
    }));
});
</script>
