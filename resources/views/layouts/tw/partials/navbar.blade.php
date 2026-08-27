<header x-data
        class="sticky top-0 z-30 flex h-16 items-center gap-1 border-b border-slate-200/80 bg-white/85 px-3
               backdrop-blur-md dark:border-slate-700/60 dark:bg-slate-800/85 sm:px-4 lg:px-6">

    {{-- Hamburguesa: cajón en móvil, contraer/expandir en escritorio (como AdminLTE) --}}
    <button type="button"
            @click="window.innerWidth < 1024 ? $store.ui.toggleMobile() : $store.ui.toggleSidebar()"
            class="tw-btn-ghost h-10 w-10 p-0" aria-label="Alternar menú lateral">
        <i class="fas fa-bars"></i>
    </button>

    <span class="ml-1 text-base font-bold tracking-tight text-slate-900 dark:text-white lg:hidden">
        E&amp;C Ingeniería
    </span>

    <div class="flex-1"></div>

    @hasSection('toolbar')
        @yield('toolbar')
    @endif

    <button type="button" @click="$store.ui.toggleDark()"
            class="tw-btn-ghost h-10 w-10 p-0" aria-label="Cambiar tema">
        <i class="fas" :class="$store.ui.dark ? 'fa-sun' : 'fa-moon'"></i>
    </button>

    {{-- Menú de usuario --}}
    @auth
        <div x-data="{ open: false }" @keydown.escape.window="open = false" class="relative ml-1">
            <button type="button" @click="open = !open" :aria-expanded="open"
                    class="flex items-center gap-2.5 rounded-xl py-1.5 pl-1.5 pr-2 transition hover:bg-slate-100 dark:hover:bg-slate-700">
                <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-brand-600 text-sm font-bold text-white">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <span class="hidden text-left leading-tight sm:block">
                    <span class="block max-w-[11rem] truncate text-sm font-semibold text-slate-900 dark:text-white">
                        {{ auth()->user()->name }}
                    </span>
                    <span class="block max-w-[11rem] truncate text-xs text-slate-500">
                        {{ auth()->user()->roles->first()->name ?? auth()->user()->email }}
                    </span>
                </span>
                <i class="fas fa-chevron-down text-[10px] text-slate-400 transition-transform" :class="open && 'rotate-180'"></i>
            </button>

            <div x-show="open" x-cloak x-transition.origin.top.right
                 @click.outside="open = false"
                 class="absolute right-0 z-50 mt-2 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xl
                        dark:border-slate-700 dark:bg-slate-800">

                <div class="flex items-center gap-3 border-b border-slate-100 px-4 py-3.5 dark:border-slate-700">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-600 text-base font-bold text-white">
                        {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                    </span>
                    <span class="min-w-0 leading-tight">
                        <span class="block truncate text-sm font-semibold text-slate-900 dark:text-white">{{ auth()->user()->name }}</span>
                        <span class="block truncate text-xs text-slate-500">{{ auth()->user()->email }}</span>
                    </span>
                </div>

                <div class="py-1">
                    <a href="{{ route('profile.show') }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fas fa-user w-4 text-slate-400"></i> Mi perfil
                    </a>
                    <a href="{{ route('changePassword', auth()->id()) }}"
                       class="flex items-center gap-3 px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-100 dark:text-slate-200 dark:hover:bg-slate-700">
                        <i class="fas fa-key w-4 text-slate-400"></i> Cambiar contraseña
                    </a>
                </div>

                <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 py-1 dark:border-slate-700">
                    @csrf
                    <button type="submit"
                            class="flex w-full items-center gap-3 px-4 py-2.5 text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/40">
                        <i class="fas fa-right-from-bracket w-4"></i> Cerrar sesión
                    </button>
                </form>
            </div>
        </div>
    @endauth
</header>
