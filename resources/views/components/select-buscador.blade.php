@props([
    'label' => null,
    'model',                        // expresión Alpine (valor único) del componente padre
    'options',                      // expresión Alpine que devuelve [{ value, label }, …]
    'placeholder' => 'Seleccione…',
    'onChange' => '',               // expresión Alpine a ejecutar tras elegir
    'limpiable' => true,
])

{{-- Select de un solo valor con buscador.

     Sustituye a TomSelect en las listas largas (barrios, inspectores), donde un
     <select> nativo obliga a desplazarse a ciegas. Las opciones llegan como
     EXPRESIÓN, no como arreglo de PHP, porque en la zonificación cada select se
     recalcula con lo que se elige en los demás.

     Hermano de <x-multi-select>: mismo aspecto y mismo trato del valor, pero
     aquí el modelo es un valor suelto en vez de un arreglo. --}}

<div x-data="{
        abierto: false,
        busqueda: '',

        /* Nombre distinto al de la propiedad del padre a propósito: el cuerpo
           del getter resuelve `{{ $options }}` en el ámbito de arriba, y llamarlo
           igual invitaba a leerlo como una recursión. */
        get todas() { return {{ $options }} ?? []; },

        get filtradas() {
            const q = this.busqueda.trim().toLowerCase();
            return q ? this.todas.filter(o => String(o.label).toLowerCase().includes(q))
                     : this.todas;
        },

        /* La etiqueta se busca en la lista viva: si el valor elegido deja de
           estar disponible tras recalcular, el campo se ve vacío en vez de
           mostrar un nombre que ya no corresponde. */
        get elegida() {
            const v = {{ $model }};
            return (v === '' || v === null || v === undefined)
                ? null
                : this.todas.find(o => String(o.value) === String(v)) ?? null;
        },

        abrir() {
            this.abierto = true;
            this.busqueda = '';
            this.$nextTick(() => this.$refs.buscador?.focus());
        },

        elegir(valor) {
            {{ $model }} = valor;
            this.abierto = false;
            {{ $onChange }};
        },
     }"
     class="relative"
     @keydown.escape.stop="abierto = false">

    @if ($label)
        <span class="tw-label">{{ $label }}</span>
    @endif

    <button type="button" class="tw-select flex w-full items-center gap-2 text-left"
            @click="abierto ? (abierto = false) : abrir()"
            :aria-expanded="abierto">
        <span class="min-w-0 flex-1 truncate"
              :class="elegida ? '' : 'text-slate-400'"
              x-text="elegida ? elegida.label : @js($placeholder)"></span>

        @if ($limpiable)
            {{-- Va antes del hueco que .tw-select reserva para su flecha. --}}
            <span x-show="elegida" x-cloak role="button" aria-label="Quitar selección"
                  class="shrink-0 cursor-pointer text-slate-400 hover:text-slate-600 dark:hover:text-slate-200"
                  @click.stop="elegir('')">
                <i class="fas fa-xmark"></i>
            </span>
        @endif
    </button>

    <div x-show="abierto" x-cloak @click.outside="abierto = false"
         x-transition:enter="transition duration-100 ease-out"
         x-transition:enter-start="opacity-0 -translate-y-1"
         x-transition:enter-end="opacity-100 translate-y-0"
         class="absolute left-0 right-0 z-40 mt-1.5 overflow-hidden rounded-xl border border-slate-200
                bg-white shadow-lift dark:border-slate-600 dark:bg-slate-800">

        <div class="border-b border-slate-100 p-2 dark:border-slate-700">
            <input type="search" x-ref="buscador" x-model="busqueda" placeholder="Buscar…"
                   class="tw-input py-1.5 text-sm"
                   @keydown.enter.prevent="filtradas.length === 1 && elegir(filtradas[0].value)">
        </div>

        <ul class="max-h-64 overflow-y-auto p-1" role="listbox">
            <template x-for="o in filtradas" :key="o.value">
                <li>
                    <button type="button" @click="elegir(o.value)" x-text="o.label"
                            class="w-full truncate rounded-lg px-3 py-2 text-left text-sm
                                   hover:bg-slate-100 dark:hover:bg-slate-700"
                            :class="String(o.value) === String({{ $model }})
                                ? 'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-900/50 dark:text-brand-100'
                                : ''"></button>
                </li>
            </template>

            <li x-show="!filtradas.length" class="px-3 py-4 text-center text-sm text-slate-500">
                Sin resultados
            </li>
        </ul>
    </div>
</div>
