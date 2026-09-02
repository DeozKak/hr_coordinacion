@props([
    'label',
    'model',                        // expresión Alpine (arreglo) del componente padre
    'placeholder' => 'Escribe y pulsa Enter',
])

{{-- Campo que acumula varios números en forma de etiquetas.

     Sustituye al TomSelect con `create: true` que usaban los filtros de
     recepción: se escribe un número y se confirma con Enter, coma o espacio, y
     al pegar una lista se reparte sola. Sólo admite dígitos, igual que antes
     (createFilter: /^\d+$/), porque el servidor hace intval() de cada valor. --}}

<div x-data="{
        texto: '',

        agregar(crudo) {
            const nuevos = String(crudo).split(/[\s,;]+/).filter(v => /^\d+$/.test(v));
            if (!nuevos.length) return;
            {{ $model }} = [...new Set([...{{ $model }}, ...nuevos])];
        },

        confirmar() {
            if (!this.texto.trim()) return;
            this.agregar(this.texto);
            this.texto = '';
        },

        quitar(valor) {
            {{ $model }} = {{ $model }}.filter(v => v !== valor);
        },

        alTeclear(e) {
            if (e.key === 'Enter' || e.key === ',' || e.key === ' ') {
                e.preventDefault();
                this.confirmar();
                return;
            }
            // Retroceso con el campo vacío borra la última etiqueta.
            if (e.key === 'Backspace' && !this.texto && {{ $model }}.length) {
                {{ $model }} = {{ $model }}.slice(0, -1);
            }
        },
     }">

    <span class="tw-label">{{ $label }}</span>

    <div class="tw-input flex h-auto flex-wrap items-center gap-1.5 py-1.5"
         @click="$refs.entrada.focus()">

        <template x-for="v in {{ $model }}" :key="v">
            <span class="inline-flex items-center gap-1.5 rounded-lg bg-brand-100 px-2 py-0.5 text-xs
                         font-semibold text-brand-800 dark:bg-brand-900/60 dark:text-brand-100">
                <span x-text="v"></span>
                <button type="button" class="opacity-60 hover:opacity-100" aria-label="Quitar"
                        @click.stop="quitar(v)">
                    <i class="fas fa-xmark"></i>
                </button>
            </span>
        </template>

        <input x-ref="entrada" x-model="texto" type="text" inputmode="numeric"
               class="min-w-[5rem] flex-1 border-0 bg-transparent p-0 text-sm placeholder:text-slate-400
                      focus:outline-none focus:ring-0"
               :placeholder="{{ $model }}.length ? '' : @js($placeholder)"
               @keydown="alTeclear($event)"
               @blur="confirmar()"
               @paste.prevent="agregar(($event.clipboardData || window.clipboardData).getData('text'))">
    </div>
</div>
