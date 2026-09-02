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
           x-transition:enter="transition-opacity duration-200 delay-100"
           x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
           x-transition:leave="transition-opacity duration-100"
           x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
           class="px-3 pb-1.5 pt-5 text-[10px] font-bold uppercase tracking-[0.12em] text-slate-400">
            {{ $item['header'] }}
        </p>
        <div x-show="!$store.ui.expanded" class="mx-3 my-3 border-t border-slate-200 dark:border-slate-700"></div>

    @elseif ($submenu)
        @php
            /* Enlaces del submenú, ya resueltos, para el panel flotante.
               Un nivel más de anidamiento se aplana con su título como cabecera,
               que es lo que hace AdminLTE. */
            $planos = [];
            foreach ($submenu as $hijo) {
                $puedeVer = ! isset($hijo['can']) || collect((array) $hijo['can'])
                    ->contains(fn ($pp) => auth()->check() && auth()->user()->can($pp));
                if (! $puedeVer) continue;

                $hijoUrl = isset($hijo['route'])
                    ? (is_array($hijo['route']) ? route($hijo['route'][0], $hijo['route'][1] ?? []) : route($hijo['route']))
                    : (isset($hijo['url']) ? url($hijo['url']) : null);

                if (! empty($hijo['submenu'])) {
                    $planos[] = ['tipo' => 'grupo', 'texto' => $hijo['text'] ?? ''];
                    foreach ($hijo['submenu'] as $nieto) {
                        $nietoUrl = isset($nieto['route'])
                            ? (is_array($nieto['route']) ? route($nieto['route'][0], $nieto['route'][1] ?? []) : route($nieto['route']))
                            : (isset($nieto['url']) ? url($nieto['url']) : null);
                        if ($nietoUrl) {
                            $planos[] = ['tipo' => 'enlace', 'texto' => $nieto['text'] ?? '',
                                         'url' => $nietoUrl, 'activo' => url()->current() === $nietoUrl];
                        }
                    }
                } elseif ($hijoUrl) {
                    $planos[] = ['tipo' => 'enlace', 'texto' => $hijo['text'] ?? '',
                                 'url' => $hijoUrl, 'activo' => url()->current() === $hijoUrl];
                }
            }
            /* Alto aproximado del panel, para que no se salga por abajo. */
            $altoPanel = 44 + count($planos) * 36;
        @endphp

        <div x-data="{
                open: {{ $hasActiveChild ? 'true' : 'false' }},
                flotante: false,
                arriba: 0,
                cierre: null,

                /* El panel flotante sólo aplica con el menú contraído y en escritorio. */
                get puedeFlotar() {
                    return !$store.ui.expanded && window.matchMedia('(min-width: 1024px)').matches;
                },
                abrir() {
                    if (!this.puedeFlotar) return;
                    clearTimeout(this.cierre);
                    const r = $el.getBoundingClientRect();
                    this.arriba = Math.max(8, Math.min(r.top, window.innerHeight - {{ $altoPanel }} - 8));
                    this.flotante = true;
                },
                /* Pequeño retardo para poder cruzar el hueco hasta el panel. */
                cerrar() { this.cierre = setTimeout(() => { this.flotante = false; }, 140); },
             }"
             class="relative"
             @mouseenter="abrir()"
             @mouseleave="cerrar()">

            {{-- Con el menú contraído, al pulsar se expande primero. --}}
            <button type="button"
                    @click="$store.ui.expanded ? (open = !open)
                            : ($store.ui.animando(), $store.ui.sidebarOpen = true, open = true)"
                    :title="$store.ui.expanded ? null : @js($item['text'])"
                    @class([
                        'flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition',
                        'bg-ink-900 text-white shadow-sm' => $hasActiveChild,
                        'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' => ! $hasActiveChild,
                    ])
                    {{-- `gap-0` al contraer: el hueco entre icono y texto seguía
                         contando aunque el texto midiera 0, y descentraba el icono. --}}
                    :class="!$store.ui.expanded && 'lg:justify-center lg:gap-0 lg:px-0'">
                <i class="{{ $item['icon'] ?? 'far fa-circle' }} w-5 shrink-0 text-center text-[15px]"></i>
                <span class="flex-1 overflow-hidden truncate text-left transition-[opacity,max-width]
                             duration-300 ease-[cubic-bezier(.4,0,.2,1)] motion-reduce:transition-none"
                      :class="$store.ui.expanded ? 'max-w-[12rem] opacity-100' : 'max-w-0 opacity-0'"
                >{{ $item['text'] }}</span>
                <i class="fas fa-chevron-down overflow-hidden text-[10px] transition-all duration-300
                          ease-[cubic-bezier(.4,0,.2,1)] motion-reduce:transition-none"
                   :class="[
                       open && 'rotate-180',
                       $store.ui.expanded ? 'w-3 opacity-100' : 'w-0 opacity-0'
                   ]"></i>
            </button>

            <div x-show="open && $store.ui.expanded" x-collapse x-cloak class="mt-0.5 space-y-0.5 pl-4">
                @foreach ($submenu as $child)
                    @include('layouts.tw.partials.menu-item', ['item' => $child, 'depth' => $depth + 1])
                @endforeach
            </div>

            {{-- Panel flotante del menú contraído. Va en `fixed` porque el <nav>
                 recorta con overflow y lo dejaría cortado. --}}
            <div x-show="flotante && !$store.ui.expanded" x-cloak
                 x-transition:enter="transition duration-150 ease-out"
                 x-transition:enter-start="-translate-x-1 opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition duration-100 ease-in"
                 x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
                 @mouseenter="clearTimeout(cierre)" @mouseleave="cerrar()"
                 :style="`top: ${arriba}px`"
                 class="fixed left-[78px] z-50 ml-1 hidden w-60 overflow-hidden rounded-xl border
                        border-slate-200 bg-white py-1.5 shadow-2xl lg:block
                        dark:border-slate-700 dark:bg-slate-800">

                <p class="truncate px-3 pb-1.5 pt-1 text-[10px] font-bold uppercase tracking-[0.12em]
                          text-slate-400">{{ $item['text'] }}</p>

                @foreach ($planos as $entrada)
                    @if ($entrada['tipo'] === 'grupo')
                        <p class="truncate border-t border-slate-100 px-3 pb-1 pt-2 text-[10px] font-semibold
                                  uppercase tracking-wider text-slate-400 dark:border-slate-700">
                            {{ $entrada['texto'] }}
                        </p>
                    @else
                        <a href="{{ $entrada['url'] }}"
                           @class([
                               'flex items-center gap-2.5 px-3 py-2 text-sm transition',
                               'bg-ink-900 font-semibold text-white' => $entrada['activo'],
                               'text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-700' => ! $entrada['activo'],
                           ])>
                            <span @class([
                                'h-1.5 w-1.5 shrink-0 rounded-full',
                                'bg-white' => $entrada['activo'],
                                'bg-slate-300 dark:bg-slate-600' => ! $entrada['activo'],
                            ])></span>
                            <span class="truncate">{{ $entrada['texto'] }}</span>
                        </a>
                    @endif
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
           :class="!$store.ui.expanded && 'lg:justify-center lg:gap-0 lg:px-0'"
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
            <span class="overflow-hidden truncate transition-[opacity,max-width] duration-300
                         ease-[cubic-bezier(.4,0,.2,1)] motion-reduce:transition-none"
                  :class="$store.ui.expanded ? 'max-w-[12rem] opacity-100' : 'max-w-0 opacity-0'"
            >{{ $item['text'] }}</span>
        </a>
    @endif
@endif
