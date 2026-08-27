@props([
    'show',                    // expresión Alpine que controla la visibilidad
    'close' => 'modal = null', // expresión Alpine para cerrar. NO se deriva de $show:
                               // "modal === 'x' = false" no es JS válido.
    'title' => '',
    'icon' => 'fa-list',
    'tint' => 'slate',
    'size' => 'max-w-3xl',
])

<div x-show="{{ $show }}" x-cloak
     @keydown.escape.window="{{ $close }}"
     class="fixed inset-0 z-50 overflow-y-auto" role="dialog" aria-modal="true">

    <div x-show="{{ $show }}" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    <div class="flex min-h-full items-center justify-center p-4">
        <div x-show="{{ $show }}" x-transition.scale.95
             @click.outside="{{ $close }}"
             x-trap.noscroll="{{ $show }}"
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
