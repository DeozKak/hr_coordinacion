{{-- Layout de autenticación. Mantiene el contrato de secciones que heredamos
     de la plantilla del paquete retirado: auth_header, auth_intro, auth_body y
     auth_footer, para que las vistas de acceso no tengan que cambiar.

     Distribución: el fondo animado ocupa toda la pantalla y encima, centrados,
     van la marca y una tarjeta con el formulario. --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('navegacion.titulo', config('app.name')))</title>

    {{-- Icono de la pestaña. Sin declararlo, el navegador se descarga por su
         cuenta /favicon.ico y ahí seguía el logotipo anterior. Se apunta al
         isotipo actual y se le cuelga la versión: los navegadores guardan el
         favicon en una caché aparte, muy insistente, y sin cambiar la URL se
         quedan con el viejo durante días. --}}
    <link rel="icon" type="image/png" href="{{ asset('img/logo-ec-isotipo.png') }}?v=2">
    <link rel="apple-touch-icon" href="{{ asset('img/logo-ec-isotipo.png') }}?v=2">

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/tw.js'])
    @yield('css')
    @stack('styles')
</head>

<body class="min-h-screen bg-ink-950 font-sans text-slate-700 antialiased">

{{-- ============================ FONDO ANIMADO ============================ --}}
{{-- Va en `fixed` y recorta lo que se sale: las manchas cruzan la pantalla
     entera y, sin el recorte, empujarían las barras de desplazamiento. --}}
<div class="pointer-events-none fixed inset-0 z-0 overflow-hidden" aria-hidden="true">

    <div class="tw-auth-fondo absolute inset-0 bg-gradient-to-br from-brand-800 via-brand-950 to-ink-950"></div>

    {{-- Cada mancha lleva su propio recorrido, duración y desfase para que no
         vayan las tres a la vez y el conjunto no se sienta cíclico. --}}
    <div class="tw-auth-mancha absolute -left-40 -top-40 h-[34rem] w-[34rem] rounded-full bg-brand-500/30 blur-3xl"
         style="--dx: 62vw; --dy: 48vh; --esc: 1.3; --dur: 15s;"></div>
    <div class="tw-auth-mancha absolute -bottom-48 -right-32 h-[38rem] w-[38rem] rounded-full bg-violet-600/25 blur-3xl"
         style="--dx: -70vw; --dy: -40vh; --esc: 1.2; --dur: 19s; --esperar: -7s;"></div>
    <div class="tw-auth-mancha absolute left-1/3 top-1/2 h-[26rem] w-[26rem] rounded-full bg-sky-400/20 blur-3xl"
         style="--dx: -48vw; --dy: 34vh; --esc: 1.45; --dur: 12s; --esperar: -5s;"></div>

    {{-- Retícula sutil, atenuada hacia los bordes. --}}
    <div class="absolute inset-0 opacity-[0.14]"
         style="background-image:
                    linear-gradient(to right, rgb(255 255 255 / 0.12) 1px, transparent 1px),
                    linear-gradient(to bottom, rgb(255 255 255 / 0.12) 1px, transparent 1px);
                background-size: 56px 56px;
                mask-image: radial-gradient(ellipse at 50% 45%, black 30%, transparent 78%);"></div>
</div>

{{-- =============================== CONTENIDO ============================= --}}
<div class="relative z-10 flex min-h-screen flex-col items-center justify-center px-5 py-12">

    {{-- Marca --}}
    <div class="mb-8 flex flex-col items-center gap-4">
        {{-- El logotipo tal cual, no una reconstrucción tipográfica.

             Va sobre placa blanca porque su azul sobre el fondo oscuro de esta
             pantalla se quedaría en 1,8:1. `width`/`height` llevan el tamaño
             real del archivo para que el navegador reserve el hueco antes de
             descargarlo y la marca no dé un salto al aparecer. --}}
        <div class="rounded-2xl bg-white px-7 py-5 shadow-2xl shadow-black/30">
            <img src="{{ asset('img/logo-ec.png') }}" alt="E&amp;C Ingeniería SAS"
                 width="1080" height="385"
                 class="h-auto w-[240px] max-w-full">
        </div>

        <p class="text-[0.6875rem] font-semibold uppercase tracking-[0.18em] text-white/50">
            Seguimiento Operación Valle
        </p>
    </div>

    {{-- Tarjeta --}}
    {{-- Blanca y sin variante oscura a propósito: esta pantalla no tiene el
         interruptor de tema y siempre va sobre el fondo oscuro. --}}
    <div class="w-full max-w-[420px] rounded-2xl border border-white/10 bg-white p-8 shadow-2xl shadow-black/40">

        <h1 class="text-[1.625rem] font-bold tracking-tight text-slate-900">
            @yield('auth_header', 'Bienvenido')
        </h1>
        @hasSection('auth_intro')
            <p class="mt-2 text-[0.9375rem] text-slate-500">@yield('auth_intro')</p>
        @endif

        <div class="mt-7">
            @include('layouts.tw.partials.flash')
            @yield('auth_body')
        </div>

        @hasSection('auth_footer')
            <div class="mt-7 border-t border-slate-100 pt-5 text-center text-sm text-slate-500">
                @yield('auth_footer')
            </div>
        @endif
    </div>

    <p class="mt-8 text-xs text-white/40">
        &copy; {{ date('Y') }} e&amp;c ingeniería · Todos los derechos reservados
    </p>
</div>

@include('layouts.tw.partials.alerts')

@yield('js')
@stack('scripts')
</body>
</html>
