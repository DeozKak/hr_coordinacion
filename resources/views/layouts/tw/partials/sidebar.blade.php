<aside x-data
       {{-- lg:sticky + h-screen: en escritorio acompaña el scroll de la página.
            Con lg:static se iba hacia arriba al desplazarse. --}}
       class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col border-r border-slate-200/80 bg-white
              {{-- 300ms con la curva estándar de Material: arranca rápido y
                   frena al final. Con 200ms lineales el plegado se sentía seco. --}}
              transition-[width,transform] duration-300 ease-[cubic-bezier(.4,0,.2,1)]
              motion-reduce:transition-none
              lg:sticky lg:inset-y-auto lg:top-0 lg:h-screen lg:translate-x-0
              dark:border-slate-700/60 dark:bg-slate-800"
       :class="[
           $store.ui.mobileOpen ? 'translate-x-0' : '-translate-x-full',
           $store.ui.sidebarOpen ? 'lg:w-72' : 'lg:w-[4.875rem]'
       ]"
       x-cloak>

    {{-- Marca --}}
    <a href="{{ url(config('navegacion.inicio', 'home')) }}"
       class="flex h-16 shrink-0 items-center gap-3 px-4"
       :class="!$store.ui.expanded && 'lg:justify-center lg:gap-0 lg:px-0'">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700">
            <img src="{{ asset(config('navegacion.logo')) }}"
                 alt="{{ config('navegacion.logo_alt', '') }}" class="h-6 w-6 object-contain">
        </span>
        {{-- Se anima opacidad + ancho en vez de usar x-show: `display:none`
             desaparece de golpe y hace saltar la fila. --}}
        <span class="min-w-0 overflow-hidden leading-tight transition-[opacity,max-width]
                     duration-300 ease-[cubic-bezier(.4,0,.2,1)] motion-reduce:transition-none"
              :class="$store.ui.expanded ? 'max-w-[12rem] opacity-100' : 'max-w-0 opacity-0'">
            {{-- En minúscula a propósito, no por descuido. La "e" y la "c" van
                 a la altura de las mayúsculas, como en el logotipo: 745/546 es
                 la razón entre la altura de mayúsculas y la de la x que declara
                 Plus Jakarta Sans.

                 Y bajan a peso 500 porque agrandar la letra engorda su trazo:
                 aquí la base es peso 700, cuya asta mide 131 milésimas de em, y
                 agrandada se iría a 179. El asta del peso 500 mide 93, que
                 agrandada da 127: a un 3% de las 131 de al lado. --}}
            @php $grande = 'text-[1.3645em] font-medium leading-[0]'; @endphp
            <span class="block truncate text-[1rem] font-bold tracking-tight text-slate-900 dark:text-white">
                <span class="{{ $grande }}">e</span>&amp;<span class="{{ $grande }}">c</span> ingeniería
            </span>
            <span class="block truncate text-[0.625rem] font-semibold uppercase tracking-[0.12em] text-slate-400">
                Seguimiento Operación
            </span>
        </span>
    </a>

    {{-- Menú (alimentado por config/navegacion.php) --}}
    <nav class="flex-1 space-y-0.5 overflow-y-auto overflow-x-hidden px-3 pb-4 pt-2" aria-label="Menú principal">
        @foreach (config('navegacion.menu', []) as $item)
            @include('layouts.tw.partials.menu-item', ['item' => $item, 'depth' => 0])
        @endforeach
    </nav>

    {{-- Contraer / expandir (solo escritorio) --}}
    <button type="button" @click="$store.ui.toggleSidebar()"
            class="hidden h-11 shrink-0 items-center gap-3 border-t border-slate-200/80 px-4 text-sm font-medium
                   text-slate-500 transition hover:bg-slate-100 hover:text-slate-700
                   dark:border-slate-700/60 dark:hover:bg-slate-700 lg:flex"
            :class="!$store.ui.sidebarOpen && 'justify-center px-0'"
            :aria-label="$store.ui.sidebarOpen ? 'Contraer menú' : 'Expandir menú'">
        <i class="fas w-5 shrink-0 text-center"
           :class="$store.ui.sidebarOpen ? 'fa-angles-left' : 'fa-angles-right'"></i>
        <span x-show="$store.ui.sidebarOpen" x-transition.opacity>Contraer menú</span>
    </button>
</aside>
