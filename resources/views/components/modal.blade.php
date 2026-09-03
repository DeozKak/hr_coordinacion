@props([
    'show',                    // expresión Alpine que controla la visibilidad
    'close' => 'modal = null', // expresión Alpine para cerrar. NO se deriva de $show:
                               // "modal === 'x' = false" no es JS válido.
    'title' => '',
    'icon' => 'fa-list',
    'tint' => 'slate',
    'size' => 'max-w-3xl',
])

{{-- Hay cosas que se pintan al final del <body> y no dentro de este árbol —las
     alertas y el calendario de los campos de fecha—, porque tienen que poder
     salirse de la caja del modal. Para `click.outside` eso las convierte en
     "fuera", y pulsar "Cancelar" en una alerta o elegir un día en el calendario
     cerraba también el modal. Todas llevan `data-capa-flotante` y por ahí se
     reconocen.
     El clic se juzga por su DESTINO y no por `$store.alertas.visible`: el botón
     de la alerta ya apagó ese flag cuando este listener llega a ejecutarse.
     Para Escape sí sirve el flag, porque este handler corre antes que el de la
     alerta (Alpine registra en orden de aparición en el DOM). El calendario no
     necesita nada aquí: corta el Escape en captura antes de llegar a esta capa. --}}
{{-- La transición va también en la raíz: sin ella `x-show` la ocultaba de
     inmediato al cerrar y las transiciones de dentro no llegaban a verse.
     Salida algo más corta que la entrada, que es lo que se siente natural. --}}
<div x-show="{{ $show }}" x-cloak
     x-transition:enter="transition duration-200 ease-out"
     x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
     x-transition:leave="transition duration-150 ease-in"
     x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
     @keydown.escape.window="if (!$store.alertas?.visible) { {{ $close }} }"
     {{-- z por encima de 9999, el máximo que usa Handsontable. --}}
     class="fixed inset-0 z-[10000] overflow-y-auto" role="dialog" aria-modal="true">

    {{-- El contador de scroll necesita estado propio, pero su x-data NO puede ir
         en la raíz del modal: x-ref se registra en el x-data más cercano hacia
         arriba, así que los inputs del slot quedarían colgando de este ámbito y
         la vista que abre el modal no vería sus $refs. Va en el fondo, que no
         contiene nada. --}}
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"
         x-data="{ bloqueado: false }"
         x-effect="if ({{ $show }} && !bloqueado) { bloqueado = true; Alpine.store('scroll').bloquear(); }
                   else if (!({{ $show }}) && bloqueado) { bloqueado = false; Alpine.store('scroll').liberar(); }"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="{{ $show }}"
             x-transition:enter="transition duration-200 ease-out"
             x-transition:enter-start="opacity-0 scale-95 translate-y-2"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition duration-150 ease-in"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-95 translate-y-2"
             @click.outside="if (!$store.alertas?.visible && !$event.target.closest('[data-capa-flotante]')) { {{ $close }} }"
             x-trap="{{ $show }}"
             class="relative flex w-full {{ $size }} max-h-[88vh] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-800">

            <div class="flex shrink-0 items-start justify-between gap-4 border-b border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                <div class="flex min-w-0 items-center gap-3">
                    <span class="tw-chip chip-{{ $tint }}"><i class="fas {{ $icon }}"></i></span>
                    <div class="min-w-0">
                        <h2 class="tw-card-title truncate">{{ $title }}{{ $titleSlot ?? '' }}</h2>
                        @isset($subtitle)
                            <p class="tw-card-subtitle truncate">{{ $subtitle }}</p>
                        @endisset
                    </div>
                </div>
                <button type="button" @click="{{ $close }}"
                        class="tw-btn-ghost h-9 w-9 shrink-0 p-0" aria-label="Cerrar">
                    <i class="fas fa-xmark"></i>
                </button>
            </div>

            <div class="min-h-0 flex-1 overflow-auto">
                {{ $slot }}
            </div>

            @isset($footer)
                <div class="flex shrink-0 flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4 dark:border-slate-700/60">
                    {{ $footer }}
                </div>
            @endisset
        </div>
    </div>
</div>
