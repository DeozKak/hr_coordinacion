@props([
    'label',
    'options',              // [['value' => .., 'label' => ..], …]
    'model',                // expresión Alpine (array) del componente padre
    'max' => null,          // tope fijo de selección; null = sin tope
    'maxExpr' => null,      // tope dinámico: expresión Alpine del padre (gana sobre `max`)
    'placeholder' => 'Seleccione…',
    'buscador' => true,
])

@php
    /* El tope puede ser fijo o una expresión que el padre recalcula (p. ej. cuando
       depende de otro filtro). Se evalúa en cada uso, no una sola vez al montar. */
    $tope = $maxExpr ?: ($max === null ? 'null' : (int) $max);
@endphp

<div x-data="{
        abierto: false,
        busqueda: '',
        opciones: {{ Js::from($options) }},
        topeMax() { return {{ $tope }}; },
        get filtradas() {
            const q = this.busqueda.trim().toLowerCase();
            return q ? this.opciones.filter(o => o.label.toLowerCase().includes(q)) : this.opciones;
        },
        etiqueta(v) { return (this.opciones.find(o => String(o.value) === String(v)) || {}).label ?? v; },
        lleno(v) {
            const tope = this.topeMax();
            return tope !== null && {{ $model }}.length >= tope && !{{ $model }}.includes(v);
        },
        alternar(v) {
            if ({{ $model }}.includes(v)) {
                {{ $model }} = {{ $model }}.filter(x => x !== v);
            } else if (!this.lleno(v)) {
                {{ $model }} = [...{{ $model }}, v];
            }
        },
     }"
     class="relative"
     @keydown.escape.window="abierto = false">

    <span class="tw-label">{{ $label }}</span>

    <button type="button" class="tw-select flex min-h-[2.625rem] w-full items-center gap-2 text-left"
            @click="abierto = !abierto" :aria-expanded="abierto">
        <span class="flex min-w-0 flex-1 flex-wrap gap-1.5">
            <template x-if="{{ $model }}.length === 0">
                <span class="text-slate-400">{{ $placeholder }}</span>
            </template>
            <template x-for="v in {{ $model }}" :key="v">
                <span class="inline-flex max-w-full items-center gap-1.5 rounded-lg bg-brand-100 px-2 py-0.5
                             text-xs font-semibold text-brand-800
                             dark:bg-brand-900/60 dark:text-brand-100">
                    <span class="truncate" x-text="etiqueta(v)"></span>
                    <span class="cursor-pointer opacity-60 hover:opacity-100"
                          @click.stop="alternar(v)" role="button" aria-label="Quitar">
                        <i class="fas fa-xmark"></i>
                    </span>
                </span>
            </template>
        </span>
    </button>

    <div x-show="abierto" x-cloak x-transition.opacity @click.outside="abierto = false"
         class="absolute z-30 mt-1 w-full overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl
                dark:border-slate-700 dark:bg-slate-800">

        @if($buscador)
            <div class="border-b border-slate-200/80 p-2 dark:border-slate-700/60">
                <input type="search" class="tw-input py-1.5 text-sm" placeholder="Buscar…" x-model="busqueda">
            </div>
        @endif

        <div class="max-h-64 overflow-y-auto p-1">
            <template x-for="op in filtradas" :key="op.value">
                <label class="flex cursor-pointer items-start gap-2.5 rounded-lg px-2.5 py-2 text-sm
                              hover:bg-slate-50 dark:hover:bg-slate-700/50"
                       :class="lleno(op.value) && 'cursor-not-allowed opacity-40'">
                    <input type="checkbox" class="mt-0.5 h-4 w-4 shrink-0 rounded border-slate-300
                                                  text-brand-600 focus:ring-brand-500"
                           :checked="{{ $model }}.includes(op.value)"
                           :disabled="lleno(op.value)"
                           @change="alternar(op.value)">
                    <span class="text-slate-700 dark:text-slate-200" x-text="op.label"></span>
                </label>
            </template>

            <p x-show="filtradas.length === 0" class="px-2.5 py-6 text-center text-sm text-slate-400">
                Sin coincidencias.
            </p>
        </div>

        <template x-if="topeMax() !== null">
            <p class="border-t border-slate-200/80 px-3 py-2 text-xs dark:border-slate-700/60"
               :class="{{ $model }}.length >= topeMax()
                    ? 'border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-900/50 dark:bg-amber-950/30 dark:text-amber-300'
                    : 'border-slate-200/80 text-slate-400'">
                <span x-text="{{ $model }}.length"></span> de <span x-text="topeMax()"></span> seleccionados
                <span x-show="{{ $model }}.length >= topeMax()">· límite alcanzado</span>
            </p>
        </template>
    </div>
</div>
