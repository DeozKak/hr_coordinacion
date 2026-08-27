@php
    $item = is_string($item) ? ['header' => $item] : $item;

    /* Los items con 'type' o 'topnav_right' son widgets de la barra superior
       (fullscreen-widget, navbar-notification), no entradas del sidebar. */
    $isNavbarWidget = isset($item['type']) || ($item['topnav_right'] ?? false) || ($item['topnav'] ?? false);

    $can = $item['can'] ?? null;
    $allowed = ! $can || collect((array) $can)->contains(fn ($p) => auth()->check() && auth()->user()->can($p));

    $url = isset($item['route'])
        ? (is_array($item['route']) ? route($item['route'][0], $item['route'][1] ?? []) : route($item['route']))
        : (isset($item['url']) ? url($item['url']) : null);

    $active = $url && url()->current() === $url;
    $submenu = $item['submenu'] ?? null;
    $hasActiveChild = $submenu && collect($submenu)
        ->contains(fn ($s) => isset($s['url']) && url()->current() === url($s['url']));
@endphp

@if ($allowed && ! $isNavbarWidget)
    @if (isset($item['header']))
        {{-- Contraído: la cabecera se sustituye por un separador. --}}
        <p x-show="$store.ui.expanded"
           class="px-3 pb-1.5 pt-5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
            {{ $item['header'] }}
        </p>
        <div x-show="!$store.ui.expanded" class="mx-3 my-3 border-t border-slate-200 dark:border-slate-700"></div>

    @elseif ($submenu)
        <div x-data="{ open: {{ $hasActiveChild ? 'true' : 'false' }} }">
            {{-- Con el menú contraído, al pulsar se expande primero. --}}
            <button type="button"
                    @click="$store.ui.expanded ? (open = !open) : ($store.ui.sidebarOpen = true, open = true)"
                    :title="$store.ui.expanded ? null : @js($item['text'])"
                    @class([
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-ink-900 text-white shadow-sm' => $hasActiveChild,
                        'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' => ! $hasActiveChild,
                    ])
                    :class="!$store.ui.expanded && 'lg:justify-center lg:px-0'">
                <i class="{{ $item['icon'] ?? 'far fa-circle' }} w-5 shrink-0 text-center text-[15px]"></i>
                <span class="flex-1 truncate text-left" x-show="$store.ui.expanded">{{ $item['text'] }}</span>
                <i class="fas fa-chevron-down text-[10px] transition-transform"
                   x-show="$store.ui.expanded" :class="open && 'rotate-180'"></i>
            </button>

            <div x-show="open && $store.ui.expanded" x-collapse x-cloak class="mt-0.5 space-y-0.5 pl-4">
                @foreach ($submenu as $child)
                    @include('layouts.tw.partials.menu-item', ['item' => $child, 'depth' => $depth + 1])
                @endforeach
            </div>
        </div>

    @elseif ($url)
        <a href="{{ $url }}"
           :title="$store.ui.expanded ? null : @js($item['text'])"
           @class([
               'group flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm transition',
               'bg-ink-900 font-semibold text-white shadow-sm' => $active,
               'font-medium text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' => ! $active,
           ])
           :class="!$store.ui.expanded && 'lg:justify-center lg:px-0'"
           @if ($active) aria-current="page" @endif>
            @if ($depth > 0)
                <span @class([
                    'ml-1 h-1.5 w-1.5 shrink-0 rounded-full',
                    'bg-white' => $active,
                    'bg-slate-300 group-hover:bg-slate-400 dark:bg-slate-600' => ! $active,
                ])></span>
            @else
                <i class="{{ $item['icon'] ?? 'far fa-circle' }} w-5 shrink-0 text-center text-[15px]"></i>
            @endif
            <span class="truncate" x-show="$store.ui.expanded">{{ $item['text'] }}</span>
        </a>
    @endif
@endif
