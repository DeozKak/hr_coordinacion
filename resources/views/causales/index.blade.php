@extends('layouts.tw.app')

@section('title', 'Causales de legalización')

@section('content_header')
    <h1>Causales de legalización</h1>
@endsection

@section('subtitle', 'Qué cierres de GDO cuentan como orden legalizada.')

@section('actions')
    <a href="{{ route('home') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Ir al inicio
    </a>
@endsection

@section('content')
<div x-data="causales({
        iniciales: @js($causales),
        sueltasIniciales: @js($sueltas),
        urls: {
            crear:    '{{ route('causales.store') }}',
            alternar: '{{ url('causales-legalizacion') }}',
        },
     })"
     class="space-y-4 2xl:space-y-6">

    {{-- Explicación: sin esto la pantalla no se entiende sola. --}}
    <div class="tw-card tint-blue p-4 2xl:p-5">
        <div class="flex items-start gap-3">
            <span class="tw-chip chip-blue shrink-0"><i class="fas fa-circle-info"></i></span>
            <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                Una orden se da por <strong>legalizada</strong> cuando aparece en el archivo de cerradas
                <em>y</em> su causal está activa en esta lista. Las que no estén aquí se siguen cargando,
                pero no legalizan: el contrato continúa contando como pendiente por legalizar.
            </p>
        </div>
    </div>

    {{-- ============ CAUSALES SIN REGISTRAR ============ --}}
    <template x-if="sueltas.length">
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <h2 class="tw-card-title">Causales que aún no has clasificado</h2>
                        <p class="tw-card-subtitle">
                            Están en los datos cargados pero no en la lista. Hoy no legalizan.
                        </p>
                    </div>
                </div>
                <span class="pill-amber" x-text="`${sueltas.length} sin clasificar`"></span>
            </div>

            <div class="tw-card-body space-y-2">
                <template x-for="s in sueltas" :key="s.causal">
                    <div class="flex flex-wrap items-center justify-between gap-3 rounded-xl border border-slate-200
                                px-4 py-3 dark:border-slate-700">
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-slate-800 dark:text-slate-200"
                               x-text="s.causal"></p>
                            <p class="text-xs text-slate-500"
                               x-text="`${formatoMiles(s.filas)} ${s.filas === 1 ? 'orden cargada' : 'órdenes cargadas'}`"></p>
                        </div>
                        <button type="button" class="tw-btn-secondary tw-btn-sm shrink-0"
                                @click="agregar(s.causal)" :disabled="guardando">
                            <i class="fas fa-plus"></i> Cuenta como legalización
                        </button>
                    </div>
                </template>
            </div>
        </section>
    </template>

    {{-- ============ LISTA ============ --}}
    <section class="tw-card">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-emerald"><i class="fas fa-file-circle-check"></i></span>
                <div>
                    <h2 class="tw-card-title">Causales configuradas</h2>
                    <p class="tw-card-subtitle" x-text="`${activas} activas de ${causales.length}`"></p>
                </div>
            </div>
        </div>

        {{-- Alta --}}
        <div class="border-b border-slate-200/80 px-4 py-4 dark:border-slate-700/60 2xl:px-5">
            <form @submit.prevent="agregar(nueva)" class="flex flex-wrap items-end gap-3">
                <label class="min-w-[16rem] flex-1">
                    <span class="tw-label">Añadir una causal</span>
                    <input type="text" class="tw-input" x-model="nueva" :disabled="guardando"
                           placeholder="Escríbela igual que viene en el archivo de GDO">
                </label>
                <button type="submit" class="tw-btn-primary" :disabled="guardando || !nueva.trim()">
                    <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-plus'"></i> Añadir
                </button>
            </form>

            <p x-show="error" x-cloak x-transition
               class="mt-3 rounded-xl border border-red-300 bg-red-50 px-4 py-2.5 text-sm text-red-800
                      dark:border-red-800 dark:bg-red-950 dark:text-red-200"
               role="alert" x-text="error"></p>

            <p class="tw-hint mt-2">
                No importa si la escribes con tildes o con espacios de más: se compara sin
                distinguir mayúsculas ni acentos.
            </p>
        </div>

        <div class="max-h-[30rem] tw-card-scroll">
            <table class="tw-table tw-table-fija">
                <thead class="sticky top-0 z-10">
                    <tr>
                        <th>Causal</th>
                        <th class="text-right">Órdenes</th>
                        <th class="text-right">Estado</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <template x-for="c in causales" :key="c.id">
                        <tr :class="!c.activa && 'opacity-60'">
                            <td class="font-medium text-slate-800 dark:text-slate-200" x-text="c.causal"></td>
                            <td class="text-right tabular-nums" x-text="formatoMiles(c.filas)"></td>
                            <td class="text-right">
                                <span :class="c.activa ? 'pill-emerald' : 'pill-slate'">
                                    <i class="fas text-[0.625rem]"
                                       :class="c.activa ? 'fa-circle-check' : 'fa-circle-minus'"></i>
                                    <span x-text="c.activa ? 'Legaliza' : 'No legaliza'"></span>
                                </span>
                            </td>
                            <td>
                                <div class="flex justify-end gap-1.5">
                                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                                            @click="alternar(c)" :disabled="guardando">
                                        <i class="fas" :class="c.activa ? 'fa-toggle-off' : 'fa-toggle-on'"></i>
                                        <span x-text="c.activa ? 'Desactivar' : 'Activar'"></span>
                                    </button>
                                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                                            @click="eliminar(c)" :disabled="guardando"
                                            aria-label="Eliminar causal">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!causales.length">
                        <td colspan="4" class="py-12 text-center text-slate-400">
                            Sin causales configuradas: ahora mismo nada legaliza.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </section>
</div>
@endsection

@section('js')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('causales', ({ iniciales, sueltasIniciales, urls }) => ({
        causales: iniciales ?? [],
        sueltas: sueltasIniciales ?? [],
        urls,
        nueva: '',
        guardando: false,
        error: '',

        get activas() {
            return this.causales.filter(c => c.activa).length;
        },

        formatoMiles(v) {
            return new Intl.NumberFormat('es-CO').format(Number(v) || 0);
        },

        /* Las tres acciones responden lo mismo —la lista ya rehecha—, así que
           comparten el envío y el repintado. */
        async enviar(url, opciones, exito) {
            this.guardando = true;
            this.error = '';
            try {
                const r = await window.api(url, opciones);
                this.causales = r.causales ?? this.causales;
                this.sueltas = r.sueltas ?? this.sueltas;
                if (exito) window.Swal.fire({ icon: 'success', title: 'Listo', text: r.mensaje ?? '' });
                return true;
            } catch (e) {
                this.error = e?.data?.message
                    ?? e?.data?.errors?.causal?.[0]
                    ?? 'No se pudo guardar.';
                return false;
            } finally {
                this.guardando = false;
            }
        },

        async agregar(causal) {
            const texto = String(causal ?? '').trim();
            if (!texto) { this.error = 'Escribe la causal.'; return; }

            if (await this.enviar(this.urls.crear, { method: 'POST', body: { causal: texto } }, true)) {
                this.nueva = '';
            }
        },

        alternar(c) {
            return this.enviar(`${this.urls.alternar}/${c.id}/alternar`, { method: 'POST' }, true);
        },

        async eliminar(c) {
            const r = await window.Swal.fire({
                icon: 'warning',
                title: '¿Eliminar la causal?',
                text: `"${c.causal}" dejará de legalizar. Si sólo quieres apagarla sin perderla, usa Desactivar.`,
                showCancelButton: true,
                confirmButtonText: 'Eliminar',
                cancelButtonText: 'Cancelar',
            });
            if (!r.isConfirmed) return;

            return this.enviar(`${this.urls.alternar}/${c.id}`, { method: 'DELETE' }, true);
        },
    }));
});
</script>
@endsection
