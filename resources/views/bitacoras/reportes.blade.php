@extends('layouts.tw.app')

@section('title', 'Reportes de Bitácoras')
@section('content_header')
    <h1>Reportes de Bitácoras</h1>
@endsection
@section('subtitle', 'Consulta y descarga los reportes generados.')

@php
    $filas = $bitacoras->map(fn ($b) => [
        'id'      => $b->id,
        'nombre'  => $b->nombre_archivo,
        'usuario' => $b->Usuario->name ?? '—',
        'fecha'   => (string) $b->fecha_creacion,
        'urlVer'  => route('bitacoras.ver_reporte', ['id_bitacora' => $b->id]),
        'urlXlsx' => route('bitacoras.download', ['file' => $b->nombre_archivo.'.xlsx']),
    ])->values();
@endphp

@section('content')
<div x-data="reportesBitacora({
        filas: @js($filas),
        urlBuscar: '{{ route('bitacoras.buscar_por_contrato') }}',
        urlVerPlantilla: '{{ route('bitacoras.ver_reporte', ['id_bitacora' => ':id']) }}',
     })"
     class="space-y-4 2xl:space-y-6">

    {{-- Búsqueda por contrato --}}
    <section class="tw-card">
        <div class="tw-card-body">
            <label for="buscadorContrato" class="tw-label">Buscar bitácora por contrato</label>
            <div class="relative">
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" id="buscadorContrato" class="tw-input pl-10"
                       placeholder="Número de contrato…"
                       x-model.debounce.300ms="contrato">
                <i x-show="buscando" x-cloak
                   class="fas fa-circle-notch fa-spin absolute right-3.5 top-1/2 -translate-y-1/2 text-slate-400"></i>
            </div>

            <div x-show="contrato.trim()" x-cloak class="mt-3">
                <ul x-show="resultados.length"
                    class="max-h-56 divide-y divide-slate-100 overflow-y-auto rounded-xl border border-slate-200
                           dark:divide-slate-700 dark:border-slate-700">
                    <template x-for="r in resultados" :key="r.id">
                        <li>
                            <a :href="urlVer(r.id)"
                               class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm transition hover:bg-slate-50 dark:hover:bg-slate-700/40">
                                <span class="truncate font-medium text-slate-800 dark:text-slate-200" x-text="r.nombre_archivo"></span>
                                <span class="pill-slate shrink-0" x-text="`ID ${r.id}`"></span>
                            </a>
                        </li>
                    </template>
                </ul>
                <p x-show="!resultados.length && !buscando" class="px-1 py-3 text-sm text-slate-400">
                    Sin coincidencias para ese contrato.
                </p>
            </div>
        </div>
    </section>

    {{-- Listado de reportes --}}
    <section class="tw-card">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-blue"><i class="fas fa-folder-open"></i></span>
                <div>
                    <h2 class="tw-card-title">Reportes generados</h2>
                    <p class="tw-card-subtitle" x-text="`${filtradas.length} de ${filas.length} reportes`"></p>
                </div>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <label class="relative">
                    <span class="sr-only">Filtrar reportes</span>
                    <i class="fas fa-filter pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" x-model.debounce.200ms="filtro" placeholder="Filtrar…" class="tw-input w-56 py-2 pl-9">
                </label>
                <select x-model.number="porPagina" class="tw-select w-auto py-2">
                    <template x-for="n in [10, 25, 50, 100]" :key="n">
                        <option :value="n" x-text="`${n} / página`"></option>
                    </template>
                </select>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="tw-table">
                <thead>
                    <tr>
                        @foreach (['nombre' => 'Nombre reporte', 'usuario' => 'Usuario', 'fecha' => 'Fecha creación'] as $k => $label)
                            <th>
                                <button type="button" @click="ordenarPor('{{ $k }}')"
                                        class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">
                                    {{ $label }}
                                    <i class="fas text-[0.625rem]"
                                       :class="orden.key === '{{ $k }}'
                                           ? (orden.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down')
                                           : 'fa-sort opacity-30'"></i>
                                </button>
                            </th>
                        @endforeach
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="b in paginadas" :key="b.id">
                        <tr>
                            <td class="font-medium text-slate-800 dark:text-slate-200">
                                <i class="fas fa-file-excel mr-2 text-emerald-500"></i><span x-text="b.nombre"></span>
                            </td>
                            <td class="text-slate-500" x-text="b.usuario"></td>
                            <td class="tabular-nums text-slate-500" x-text="b.fecha"></td>
                            <td>
                                <div class="flex justify-end gap-2">
                                    <a :href="b.urlVer" class="tw-btn-secondary tw-btn-sm">
                                        <i class="fas fa-eye"></i> Ver reporte
                                    </a>
                                    <a :href="b.urlXlsx" class="tw-btn-primary tw-btn-sm">
                                        <i class="fas fa-download"></i> Xlsx
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!filtradas.length">
                        <td colspan="4" class="py-12 text-center text-slate-400">Nada encontrado.</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="flex items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3 text-sm dark:border-slate-700/60"
             x-show="totalPaginas > 1">
            <span class="text-slate-500" x-text="`Página ${pagina} de ${totalPaginas}`"></span>
            <div class="flex gap-2">
                <button type="button" class="tw-btn-secondary tw-btn-sm" @click="pagina--" :disabled="pagina === 1">Anterior</button>
                <button type="button" class="tw-btn-secondary tw-btn-sm" @click="pagina++" :disabled="pagina === totalPaginas">Siguiente</button>
            </div>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('reportesBitacora', ({ filas, urlBuscar, urlVerPlantilla }) => ({
        filas,

        // Búsqueda por contrato (servidor)
        contrato: '',
        resultados: [],
        buscando: false,

        // Listado local
        filtro: '',
        pagina: 1,
        porPagina: 10,
        // Por defecto, las más recientes primero (antes lo hacía un doble clic simulado en DataTables).
        orden: { key: 'fecha', dir: 'desc' },

        init() {
            this.$watch('contrato', () => this.buscar());
        },

        urlVer(id) { return urlVerPlantilla.replace(':id', id); },

        async buscar() {
            const q = this.contrato.trim();
            if (!q) { this.resultados = []; return; }

            this.buscando = true;
            try {
                this.resultados = await window.api(`${urlBuscar}?contrato=${encodeURIComponent(q)}`);
            } catch (e) {
                console.error('Error buscando por contrato', e);
                this.resultados = [];
            } finally {
                this.buscando = false;
            }
        },

        get filtradas() {
            const q = this.filtro.trim().toLowerCase();
            const out = q
                ? this.filas.filter(b => `${b.nombre} ${b.usuario} ${b.fecha}`.toLowerCase().includes(q))
                : this.filas;

            const { key, dir } = this.orden;
            return [...out].sort((a, b) =>
                String(a[key] ?? '').localeCompare(String(b[key] ?? ''), 'es', { numeric: true })
                * (dir === 'asc' ? 1 : -1));
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.filtradas.length / this.porPagina)); },

        get paginadas() {
            if (this.pagina > this.totalPaginas) this.pagina = this.totalPaginas;
            const desde = (this.pagina - 1) * this.porPagina;
            return this.filtradas.slice(desde, desde + this.porPagina);
        },

        ordenarPor(key) {
            this.orden = this.orden.key === key
                ? { key, dir: this.orden.dir === 'asc' ? 'desc' : 'asc' }
                : { key, dir: 'asc' };
            this.pagina = 1;
        },
    }));
});
</script>
@endsection
