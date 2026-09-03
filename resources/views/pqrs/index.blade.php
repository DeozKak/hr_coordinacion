{{-- =====================================================================
     OJO: esta vista no tiene ruta registrada. PQRSImportController::index,
     getQuejas e import no están en routes/pqrs/pqrs.php, así que /pqrs,
     pqrs/quejas y pqrs/importar responden 404. Se migra el diseño para
     dejarla lista, pero hoy es inalcanzable.
     ===================================================================== --}}
@extends('layouts.tw.app')

@section('title', 'Quejas')

@section('content_header')
    <h1>Quejas</h1>
@endsection

@section('subtitle', 'Carga de la macro PQR y tiempos de gestión.')

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="quejasIndex()" class="space-y-4 2xl:space-y-6">

        @can('cargar_PQRS')
            <section class="tw-card mx-auto max-w-2xl">
                <div class="tw-card-header">
                    <div class="flex items-center gap-3">
                        <span class="tw-chip chip-blue"><i class="fas fa-file-arrow-up"></i></span>
                        <div>
                            <h2 class="tw-card-title">Cargar macro PQR</h2>
                            <p class="tw-card-subtitle">Archivo de Excel con las quejas del periodo</p>
                        </div>
                    </div>
                </div>

                <form @submit.prevent="subir()" enctype="multipart/form-data">
                    <div class="space-y-4 px-4 py-4 2xl:px-5 2xl:py-5">
                        <x-file-input label="Archivo" hint="Excel .xlsx, .xls o .xlsm" tint="sky"
                                      ref="archivo" model="nombreArchivo"
                                      accept=".xlsx,.xls,.xlsm" />

                        <div x-show="resultado" x-cloak
                             class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm
                                    text-emerald-800 dark:border-emerald-800/60 dark:bg-emerald-950/40
                                    dark:text-emerald-200">
                            <i class="fas fa-circle-check mr-1"></i>
                            Archivo subido exitosamente. Registros procesados:
                            <strong x-text="resultado"></strong>.
                        </div>

                        <div x-show="errores.length > 0" x-cloak
                             class="rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm
                                    text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40
                                    dark:text-amber-200">
                            <strong class="mb-1 block">Hubo errores en las siguientes filas:</strong>
                            <ul class="list-inside list-disc space-y-0.5">
                                <template x-for="(e, i) in errores" :key="i">
                                    <li><span x-text="`Fila ${e.fila}: ${e.error}`"></span></li>
                                </template>
                            </ul>
                        </div>

                        <x-pqrs.error message="error" />
                    </div>

                    <div class="flex justify-end border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                        <button type="submit" class="tw-btn-primary" :disabled="subiendo">
                            <i class="fas" :class="subiendo ? 'fa-spinner fa-spin' : 'fa-upload'"></i>
                            <span x-text="subiendo ? 'Subiendo…' : 'Subir archivo'"></span>
                        </button>
                    </div>
                </form>
            </section>
        @endcan

        <section class="tw-card overflow-hidden">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-violet"><i class="fas fa-stopwatch"></i></span>
                    <div>
                        <h2 class="tw-card-title">Tiempos de gestión</h2>
                        <p class="tw-card-subtitle"><span x-text="totalFilas"></span> quejas registradas</p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-2">
                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="h-3.5 w-3.5 rounded border border-black/10" style="background-color:#feefc3"></span>
                        3 a 4 días
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="h-3.5 w-3.5 rounded border border-black/10" style="background-color:#fecaca"></span>
                        5 días o más
                    </span>
                </div>
            </div>

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="table" class="ht-theme-main"></div>
            </div>
        </section>
    </div>
@endsection

@section('js')
    <script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('quejasIndex', () => ({
            hot: null,
            totalFilas: 0,
            subiendo: false,
            nombreArchivo: '',
            resultado: 0,
            errores: [],
            error: '',

            init() {
                this.registrarRenderer();
                this.cargarQuejas();

                // El original redibujaba en cada resize; se mantiene con debounce.
                let t;
                window.addEventListener('resize', () => {
                    clearTimeout(t);
                    t = setTimeout(() => this.hot && this.hot.render(), 250);
                });
            },

            registrarRenderer() {
                /* Igual que en coordinación: HOT marca como .htDimmed toda celda
                   de solo lectura y su CSS trae background-color !important, así
                   que el color hay que escribirlo también con prioridad. */
                Handsontable.renderers.registerRenderer('diasClassRenderer',
                    function (instance, TD, row, col, prop, value, cellProperties) {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);

                        TD.style.removeProperty('background-color');
                        const dias = Number(value);
                        if (isNaN(dias)) return;

                        if (dias >= 3 && dias <= 4) {
                            TD.style.setProperty('background-color', '#feefc3', 'important');
                            TD.style.setProperty('color', '#b45309', 'important');
                        } else if (dias >= 5) {
                            TD.style.setProperty('background-color', '#fecaca', 'important');
                            TD.style.setProperty('color', '#b40000', 'important');
                        }
                    });
            },

            async cargarQuejas() {
                try {
                    const json = await window.api('pqrs/quejas');
                    if (json.data) this.construirTabla(json.data);
                } catch (e) {
                    console.error('No se pudieron cargar las quejas', e);
                }
            },

            construirTabla(datos) {
                const contenedor = document.getElementById('table');
                if (!contenedor) return;
                if (this.hot) this.hot.destroy();

                const cabeceras = datos.length ? Object.keys(datos[0]) : [];
                this.totalFilas = datos.length;

                this.hot = new Handsontable(contenedor, {
                    data: datos.map(obj => cabeceras.map(c => obj[c])),
                    colHeaders: cabeceras,
                    rowHeaders: true,
                    readOnly: true,
                    height: 'auto',
                    width: '100%',
                    licenseKey: 'non-commercial-and-evaluation',
                    columns: cabeceras.map(h =>
                        h.toLowerCase() === 'dias'
                            ? { renderer: 'diasClassRenderer', readOnly: true }
                            : { readOnly: true }),

                    cells: (row) => {
                        const fila = datos[row];
                        if (!fila) return {};
                        const recepcion = fila.recepcion ?? fila.RECEPCION;
                        if (recepcion === 'MACRO') return { className: 'filaRecepcionMacro' };
                        if (recepcion === 'GDW')   return { className: 'filaRecepcionGDW' };
                        return {};
                    },
                });
                window.registrarHot?.(this.hot);
            },

            async subir() {
                const archivo = this.$refs.archivo.files[0];
                this.error = '';
                this.errores = [];
                this.resultado = 0;

                const datos = new FormData();
                if (archivo) datos.append('macroPQR', archivo);

                this.subiendo = true;
                try {
                    const res = await window.api('pqrs/importar', { method: 'POST', body: datos });
                    this.resultado = res.procesados ?? 0;
                    this.errores = res.errores ?? [];
                    this.cargarQuejas();
                } catch (e) {
                    const d = e?.data;
                    this.error = (typeof d?.error === 'string' && d.error)
                        || 'Ocurrió un error al subir el archivo.';
                } finally {
                    this.subiendo = false;
                }
            },
        }));
    });
    </script>
    <style>
        /* Las clases de fila del original: se mantienen los mismos colores.
           El !important es necesario por la regla .htDimmed de handsontable.css. */
        #table .filaRecepcionMacro { background-color: #dcfce7 !important; color: #166534 !important; }
        #table .filaRecepcionGDW   { background-color: #dbeafe !important; color: #1e40af !important; }
    </style>
@endsection
