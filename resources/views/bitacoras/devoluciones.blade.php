@extends('layouts.tw.app')

@section('title', 'Seguimiento de Devoluciones')
@section('content_header')
    <h1>Seguimiento de Devoluciones</h1>
@endsection
@section('subtitle', 'Devoluciones pendientes de gestión e histórico.')

@php
    $mapa = fn ($d) => [
        'id'          => $d->id,
        'supervisor'  => $d->Supervisor->name ?? '—',
        'inspector'   => trim(($d->Inspector->nombres ?? '').' '.($d->Inspector->apellidos ?? '')) ?: '—',
        'fecha_insp'  => (string) $d->FECHA_INSP,
        'tipo'        => $d->TIPO_TRABAJO,
        'contrato'    => $d->CONTRATO,
        'orden'       => $d->ORDEN_TRABAJO,
        'orden_ext'   => $d->ORDEN_EXT,
        'resultado'   => $d->RESULTADO_CIERRE,
        'causal'      => $d->CAUSAL,
        'fecha_dv'    => (string) $d->FECHA_DV,
        'gestionado'  => (int) $d->GESTIONADO === 1,
        'fecha_gest'  => (string) $d->FECHA_GESTION,
        'observacion' => $d->OBSERVACION_GESTION,
        'dias'        => (int) $d->DIAS_SIN_GESTION,
        'vence60'     => $d->vence === '60 meses',
        'urlCambiar'  => route('bitacoras.actualizar_devolucion', ['id' => $d->id]),
    ];

    $columnas = [
        'supervisor'  => 'Supervisor',
        'inspector'   => 'Inspector',
        'fecha_insp'  => 'Fecha Inspección',
        'tipo'        => 'Tipo Trabajo',
        'contrato'    => 'Contrato',
        'orden'       => 'Orden de trabajo',
        'orden_ext'   => 'Orden Externa',
        'resultado'   => 'Resultado',
        'causal'      => 'Causal',
        'fecha_dv'    => 'Fecha devolución',
        'gestionado'  => 'Gestionado',
        'fecha_gest'  => 'Fecha gestión',
        'observacion' => 'Observación Gestión',
        'dias'        => 'Días sin Gestionar',
    ];

    $puedeModificar = auth()->user()->can('mod_devoluciones');
@endphp

@section('content')
<div x-data="devoluciones({
        pendientes: @js($devoluciones->map($mapa)->values()),
        historico:  @js($gestionados->map($mapa)->values()),
        columnas:   @js($columnas),
        urlExportar:'{{ route('bitacora.exportar_devoluciones') }}',
     })"
     class="tw-card">

    {{-- Pestañas --}}
    <div class="tw-card-header">
        <div class="flex rounded-xl bg-slate-100 p-1 dark:bg-slate-900/50" role="tablist">
            @foreach (['pendientes' => 'Devoluciones', 'historico' => 'Histórico'] as $k => $etiqueta)
                <button type="button" role="tab" @click="cambiarPestana('{{ $k }}')"
                        :aria-selected="pestana === '{{ $k }}'"
                        class="flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-sm font-semibold transition"
                        :class="pestana === '{{ $k }}'
                            ? 'bg-white text-slate-900 shadow-sm dark:bg-slate-700 dark:text-white'
                            : 'text-slate-500 hover:text-slate-700'">
                    {{ $etiqueta }}
                    <span class="pill-slate" x-text="{{ $k }}.length"></span>
                </button>
            @endforeach
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <label class="relative">
                <span class="sr-only">Buscar</span>
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3.5 top-1/2 -translate-y-1/2 text-sm text-slate-400"></i>
                <input type="search" x-model.debounce.200ms="busqueda" placeholder="Buscar…" class="tw-input w-56 py-2 pl-9">
            </label>
            <select x-model.number="porPagina" class="tw-select w-auto py-2">
                <template x-for="n in [25, 50, 100, 200]" :key="n">
                    <option :value="n" x-text="`${n} / página`"></option>
                </template>
            </select>
            <button type="button" @click="exportar()" :disabled="exportando" class="tw-btn-primary tw-btn-sm">
                <i class="fas" :class="exportando ? 'fa-spinner fa-spin' : 'fa-file-excel'"></i>
                <span x-text="exportando ? 'Exportando…' : 'Exportar'"></span>
            </button>
        </div>
    </div>

    {{-- Leyenda de resaltados --}}
    <div class="flex flex-wrap items-center gap-4 border-b border-slate-200/80 px-5 py-2.5 text-xs text-slate-500 dark:border-slate-700/60">
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-fuchsia-300"></span> Vence a 60 meses</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span> 2 o más días sin gestionar</span>
        <span class="flex items-center gap-1.5"><span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span> Gestionado</span>
    </div>

    <div class="overflow-x-auto">
        <table class="tw-table whitespace-nowrap">
            <thead>
                <tr>
                    @foreach ($columnas as $k => $label)
                        <th>
                            <button type="button" @click="ordenarPor('{{ $k }}')"
                                    class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">
                                {{ $label }}
                                <i class="fas text-[10px]"
                                   :class="orden.key === '{{ $k }}'
                                       ? (orden.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down')
                                       : 'fa-sort opacity-30'"></i>
                            </button>
                        </th>
                    @endforeach
                    @if ($puedeModificar)
                        <th x-show="pestana === 'pendientes'" class="text-right">Acciones</th>
                    @endif
                </tr>
            </thead>

            <tbody>
                <template x-for="d in paginadas" :key="d.id">
                    <tr :class="d.vence60 && 'bg-fuchsia-50 dark:bg-fuchsia-950/30'">
                        <td x-text="d.supervisor"></td>
                        <td class="font-medium text-slate-800 dark:text-slate-200" x-text="d.inspector"></td>
                        <td class="tabular-nums" x-text="d.fecha_insp"></td>
                        <td x-text="d.tipo"></td>
                        <td class="tabular-nums" x-text="d.contrato"></td>
                        <td class="tabular-nums" x-text="d.orden"></td>
                        <td class="tabular-nums" x-text="d.orden_ext"></td>
                        <td x-text="d.resultado"></td>
                        <td class="max-w-[16rem] truncate" :title="d.causal" x-text="d.causal"></td>
                        <td class="tabular-nums" x-text="d.fecha_dv"></td>
                        <td>
                            <span :class="d.gestionado ? 'pill-emerald' : 'pill-slate'"
                                  x-text="d.gestionado ? 'SÍ' : 'NO'"></span>
                        </td>
                        <td class="tabular-nums" x-text="d.fecha_gest"></td>
                        <td class="max-w-[18rem] truncate" :title="d.observacion" x-text="d.observacion"></td>
                        <td>
                            <span :class="d.dias >= 2 ? 'pill-amber' : ''" class="tabular-nums" x-text="d.dias"></span>
                        </td>

                        @if ($puedeModificar)
                            <td x-show="pestana === 'pendientes'" class="text-right">
                                <button type="button" x-show="!d.gestionado" @click="cambiarEstado(d)"
                                        class="tw-btn-secondary tw-btn-sm">
                                    <i class="fas fa-rotate"></i> Cambiar
                                </button>
                                <span x-show="d.gestionado" class="text-slate-300">—</span>
                            </td>
                        @endif
                    </tr>
                </template>

                <tr x-show="!filtradas.length">
                    <td :colspan="totalColumnas" class="py-12 text-center text-slate-400">
                        Nada encontrado — lo siento.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>

    <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3 text-sm dark:border-slate-700/60">
        <span class="text-slate-500" x-text="`${filtradas.length} registros · página ${pagina} de ${totalPaginas}`"></span>
        <div class="flex gap-2">
            <a href="javascript:history.go(-1)" class="tw-btn-secondary tw-btn-sm"><i class="fas fa-arrow-left"></i> Ir atrás</a>
            <button type="button" class="tw-btn-secondary tw-btn-sm" @click="pagina--" :disabled="pagina === 1">Anterior</button>
            <button type="button" class="tw-btn-secondary tw-btn-sm" @click="pagina++" :disabled="pagina === totalPaginas">Siguiente</button>
        </div>
    </div>

    {{-- Formulario oculto: el cambio de estado sigue siendo POST normal, como antes. --}}
    <form x-ref="formCambiar" method="POST" class="hidden">
        @csrf
        <input type="hidden" name="observacion" x-ref="inputObservacion">
        <input type="hidden" name="agregar_produccion" x-ref="inputProduccion">
    </form>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('devoluciones', ({ pendientes, historico, columnas, urlExportar }) => ({
        pendientes, historico, columnas,

        pestana: 'pendientes',
        busqueda: '',
        pagina: 1,
        porPagina: 25,
        orden: { key: 'dias', dir: 'desc' },
        exportando: false,

        get filas() { return this.pestana === 'pendientes' ? this.pendientes : this.historico; },

        get totalColumnas() {
            return Object.keys(this.columnas).length + (this.pestana === 'pendientes' ? 1 : 0);
        },

        get filtradas() {
            const q = this.busqueda.trim().toLowerCase();
            const claves = Object.keys(this.columnas);
            const out = q
                ? this.filas.filter(d => claves.map(k => d[k]).join(' ').toLowerCase().includes(q))
                : this.filas;

            const { key, dir } = this.orden;
            return [...out].sort((a, b) => {
                const x = a[key] ?? '', y = b[key] ?? '';
                const cmp = (typeof x === 'number' && typeof y === 'number')
                    ? x - y
                    : String(x).localeCompare(String(y), 'es', { numeric: true });
                return cmp * (dir === 'asc' ? 1 : -1);
            });
        },

        get totalPaginas() { return Math.max(1, Math.ceil(this.filtradas.length / this.porPagina)); },

        get paginadas() {
            if (this.pagina > this.totalPaginas) this.pagina = this.totalPaginas;
            const desde = (this.pagina - 1) * this.porPagina;
            return this.filtradas.slice(desde, desde + this.porPagina);
        },

        cambiarPestana(p) { this.pestana = p; this.pagina = 1; this.busqueda = ''; },

        ordenarPor(key) {
            this.orden = this.orden.key === key
                ? { key, dir: this.orden.dir === 'asc' ? 'desc' : 'asc' }
                : { key, dir: 'asc' };
            this.pagina = 1;
        },

        /* El backend parsea el HTML con DOMDocument y arma una hoja por tabla,
           así que se construyen las dos tablas completas desde los datos
           (no desde el DOM, que sólo tiene la página visible). */
        tablaHtml(filas) {
            const esc = (v) => String(v ?? '')
                .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

            const claves = Object.keys(this.columnas);
            const encabezados = claves.map(k => `<th>${esc(this.columnas[k])}</th>`).join('');
            const cuerpo = filas.map(d => {
                const celdas = claves.map(k => {
                    const v = k === 'gestionado' ? (d[k] ? 'SI' : 'NO') : d[k];
                    return `<td>${esc(v)}</td>`;
                }).join('');
                return `<tr>${celdas}</tr>`;
            }).join('');

            return `<table><thead><tr>${encabezados}</tr></thead><tbody>${cuerpo}</tbody></table>`;
        },

        async exportar() {
            this.exportando = true;
            try {
                const res = await window.api(urlExportar, {
                    method: 'POST',
                    body: {
                        codigoHTMLdev: this.tablaHtml(this.pendientes),
                        codigoHTMLges: this.tablaHtml(this.historico),
                    },
                });

                if (res.ruta) {
                    window.location.href = res.ruta;
                } else {
                    throw new Error('sin ruta');
                }
            } catch (e) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error al exportar archivo, intente de nuevo o contacte al administrador del sistema',
                });
            } finally {
                this.exportando = false;
            }
        },

        cambiarEstado(d) {
            Swal.fire({
                title: '¿Estás seguro?',
                html: '¿Quieres cambiar el estado de la devolución?<br><br>'
                    + '<div style="display:flex;align-items:center;justify-content:center;margin-bottom:1em;">'
                    + '<input type="checkbox" id="chk_produccion" style="margin-right:10px;transform:scale(1.1);cursor:pointer;">'
                    + '<label for="chk_produccion" style="margin-bottom:0;cursor:pointer;font-weight:normal;">Agregar a producción</label>'
                    + '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, cambiar estado',
                input: 'textarea',
                inputPlaceholder: 'Escriba la observación de la gestión',
                inputAttributes: { 'aria-label': 'Escriba la observación de la gestión' },
                preConfirm: (observacion) => {
                    if (!observacion) {
                        Swal.showValidationMessage('Por favor, ingrese una observación.');
                        return false;
                    }
                    return {
                        observacion,
                        agregarProduccion: Swal.getPopup().querySelector('#chk_produccion').checked,
                    };
                },
            }).then((r) => {
                if (!r.isConfirmed) return;

                const form = this.$refs.formCambiar;
                form.action = d.urlCambiar;
                this.$refs.inputObservacion.value = r.value.observacion;
                this.$refs.inputProduccion.value = r.value.agregarProduccion ? 1 : 0;
                form.submit();
            });
        },
    }));
});
</script>
@endsection
