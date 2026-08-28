<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      x-data
      :class="{ 'dark': $store.ui.dark }">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('adminlte.title', config('app.name')))</title>

    {{-- Evita el flash de tema claro antes de que Alpine arranque. --}}
    <script>
        try {
            if (JSON.parse(localStorage.getItem('ui.dark'))) document.documentElement.classList.add('dark');
        } catch (e) {}
    </script>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/tw.js'])

    @yield('css')
    @yield('styles')
    @stack('styles')
</head>

<body class="min-h-screen bg-canvas font-sans text-slate-700 antialiased
             dark:bg-canvas-dark dark:text-slate-300 @yield('classes_body')">

<div class="flex min-h-screen">

    @include('layouts.tw.partials.sidebar')

    {{-- Backdrop del sidebar en móvil --}}
    <div x-data x-show="$store.ui.mobileOpen" x-cloak
         @click="$store.ui.mobileOpen = false" x-transition.opacity
         class="fixed inset-0 z-30 bg-slate-900/50 backdrop-blur-sm lg:hidden" aria-hidden="true"></div>

    <div class="flex min-w-0 flex-1 flex-col">

        @include('layouts.tw.partials.navbar')

        <main class="flex-1 px-4 py-8 sm:px-6 lg:px-8">
            <div class="mx-auto max-w-[1600px]">

                @hasSection('content_header')
                    <header class="mb-8 flex flex-wrap items-end justify-between gap-4">
                        <div class="min-w-0">
                            {{-- Las vistas heredadas ponen un <h1> aquí; lo estilizamos desde el contenedor. --}}
                            <div class="[&>h1]:text-3xl [&>h1]:font-bold [&>h1]:tracking-tight [&>h1]:text-slate-900
                                        dark:[&>h1]:text-white sm:[&>h1]:text-[34px]">
                                @yield('content_header')
                            </div>
                            @hasSection('subtitle')
                                <p class="mt-1.5 text-[15px] text-slate-500 dark:text-slate-400">@yield('subtitle')</p>
                            @endif
                        </div>

                        @hasSection('actions')
                            <div class="flex flex-wrap items-center gap-2">@yield('actions')</div>
                        @endif
                    </header>
                @endif

                @include('layouts.tw.partials.flash')

                @yield('content')
            </div>
        </main>

        @include('layouts.tw.partials.footer')
    </div>
</div>

{{-- Fuera del <header> sticky: ese crea contexto de apilamiento y recortaría el modal. --}}
@auth
    @include('layouts.tw.partials.config-modal')
@endauth

@include('layouts.tw.partials.alerts')

{{-- Librerías de terceros: se apilan aquí para que estén disponibles
     antes del @yield('js') de cada vista. --}}
@stack('libs')

@yield('js')
@stack('scripts')
</body>
</html>
