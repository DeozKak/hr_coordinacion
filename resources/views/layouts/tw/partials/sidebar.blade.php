<aside x-data
       {{-- lg:sticky + h-screen: en escritorio acompaña el scroll de la página.
            Con lg:static se iba hacia arriba al desplazarse. --}}
       class="fixed inset-y-0 left-0 z-40 flex w-72 shrink-0 flex-col border-r border-slate-200/80 bg-white
              transition-[width,transform] duration-200
              lg:sticky lg:inset-y-auto lg:top-0 lg:h-screen lg:translate-x-0
              dark:border-slate-700/60 dark:bg-slate-800"
       :class="[
           $store.ui.mobileOpen ? 'translate-x-0' : '-translate-x-full',
           $store.ui.sidebarOpen ? 'lg:w-72' : 'lg:w-[78px]'
       ]"
       x-cloak>

    {{-- Marca --}}
    <a href="{{ url(config('adminlte.dashboard_url', 'home')) }}"
       class="flex h-16 shrink-0 items-center gap-3 px-4"
       :class="!$store.ui.expanded && 'lg:justify-center lg:px-0'">
        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-white ring-1 ring-slate-200/80 dark:bg-slate-900 dark:ring-slate-700">
            <img src="{{ asset(config('adminlte.logo_img')) }}"
                 alt="{{ config('adminlte.logo_img_alt', '') }}" class="h-6 w-6 object-contain">
        </span>
        <span class="min-w-0 leading-tight" x-show="$store.ui.expanded" x-transition.opacity>
            <span class="block truncate text-[16px] font-bold tracking-tight text-slate-900 dark:text-white">
                E&amp;C Ingeniería
            </span>
            <span class="block truncate text-[10px] font-semibold uppercase tracking-[0.12em] text-slate-400">
                Seguimiento Operación
            </span>
        </span>
    </a>

    {{-- Menú (alimentado por config/adminlte.php) --}}
    <nav class="flex-1 space-y-0.5 overflow-y-auto overflow-x-hidden px-3 pb-4 pt-2" aria-label="Menú principal">
        @foreach (config('adminlte.menu', []) as $item)
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
