{{-- Layout de autenticación. Mantiene el contrato de secciones de
     adminlte::auth.auth-page (auth_header / auth_body / auth_footer). --}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('adminlte.title', config('app.name')))</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=plus-jakarta-sans:400,500,600,700,800&display=swap" rel="stylesheet">

    @vite(['resources/css/app.css', 'resources/js/tw.js'])
    @yield('css')
    @stack('styles')
</head>

<body class="min-h-screen bg-white font-sans text-slate-700 antialiased">

<div class="flex min-h-screen">

    {{-- ============ PANEL DE MARCA (izquierda, desde lg) ============ --}}
    <div class="relative hidden w-[46%] shrink-0 overflow-hidden bg-ink-950 lg:flex xl:w-[52%]">

        {{-- Degradado base --}}
        <div class="absolute inset-0 bg-gradient-to-br from-brand-800 via-brand-950 to-ink-950"></div>

        {{-- Blobs difuminados --}}
        <div class="absolute -left-24 -top-24 h-96 w-96 rounded-full bg-brand-500/25 blur-3xl"></div>
        <div class="absolute -bottom-32 -right-16 h-[28rem] w-[28rem] rounded-full bg-violet-600/20 blur-3xl"></div>
        <div class="absolute left-1/3 top-1/2 h-72 w-72 rounded-full bg-sky-400/10 blur-3xl"></div>

        {{-- Retícula sutil --}}
        <div class="absolute inset-0 opacity-[0.18]"
             style="background-image:
                        linear-gradient(to right, rgb(255 255 255 / 0.12) 1px, transparent 1px),
                        linear-gradient(to bottom, rgb(255 255 255 / 0.12) 1px, transparent 1px);
                    background-size: 56px 56px;
                    mask-image: radial-gradient(ellipse at 50% 40%, black 35%, transparent 75%);"></div>

        {{-- Contenido --}}
        <div class="relative z-10 flex w-full flex-col justify-between p-12 xl:p-16">

            <div class="flex items-center gap-4">
                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-white/95 shadow-lg shadow-black/20">
                    <img src="{{ asset(config('adminlte.logo_img')) }}"
                         alt="{{ config('adminlte.logo_img_alt', 'E&C Ingeniería') }}"
                         class="h-9 w-9 object-contain">
                </span>
                <span class="leading-tight">
                    <span class="block text-xl font-bold tracking-tight text-white">E&amp;C Ingeniería</span>
                    <span class="block text-[11px] font-semibold uppercase tracking-[0.16em] text-white/50">
                        Seguimiento Operación Valle
                    </span>
                </span>
            </div>

            <div class="max-w-lg">
                <h2 class="text-[42px] font-extrabold leading-[1.08] tracking-tight text-white xl:text-5xl">
                    Control operativo,<br>
                    <span class="bg-gradient-to-r from-sky-300 via-brand-200 to-violet-300 bg-clip-text text-transparent">
                        en un solo lugar.
                    </span>
                </h2>
                <p class="mt-5 text-[17px] leading-relaxed text-white/60">
                    Revisiones periódicas, bitácoras, producción y nómina — sincronizados y al día.
                </p>

                <ul class="mt-10 space-y-4">
                    @foreach ([
                        ['fa-chart-line', 'Reporte operativo diario', 'Ejecutado, pendientes y prioridades en tiempo real.'],
                        ['fa-map-location-dot', 'Cobertura por localidad', 'Fuerza de trabajo asignada y trazable.'],
                        ['fa-shield-halved', 'Acceso por permisos', 'Cada rol ve exactamente lo que le corresponde.'],
                    ] as [$icon, $titulo, $detalle])
                        <li class="flex items-start gap-4">
                            <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-white/10 text-white ring-1 ring-inset ring-white/15 backdrop-blur">
                                <i class="fas {{ $icon }}"></i>
                            </span>
                            <span>
                                <span class="block font-semibold text-white">{{ $titulo }}</span>
                                <span class="block text-sm text-white/50">{{ $detalle }}</span>
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>

            <p class="text-xs text-white/35">
                &copy; {{ date('Y') }} E&amp;C Ingeniería · Todos los derechos reservados
            </p>
        </div>
    </div>

    {{-- ==================== FORMULARIO (derecha) ==================== --}}
    <div class="flex flex-1 items-center justify-center px-5 py-12 sm:px-10">
        <div class="w-full max-w-[400px]">

            {{-- Marca compacta, sólo cuando el panel está oculto --}}
            <div class="mb-10 flex items-center gap-3 lg:hidden">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl ring-1 ring-slate-200">
                    <img src="{{ asset(config('adminlte.logo_img')) }}" alt="" class="h-8 w-8 object-contain">
                </span>
                <span class="leading-tight">
                    <span class="block text-lg font-bold tracking-tight text-slate-900">E&amp;C Ingeniería</span>
                    <span class="block text-[10px] font-semibold uppercase tracking-[0.14em] text-slate-400">
                        Seguimiento Operación Valle
                    </span>
                </span>
            </div>

            <h1 class="text-[28px] font-bold tracking-tight text-slate-900">
                @yield('auth_header', 'Bienvenido')
            </h1>
            @hasSection('auth_intro')
                <p class="mt-2 text-[15px] text-slate-500">@yield('auth_intro')</p>
            @endif

            <div class="mt-8">
                @include('layouts.tw.partials.flash')
                @yield('auth_body')
            </div>

            @hasSection('auth_footer')
                <div class="mt-8 border-t border-slate-100 pt-6 text-center text-sm text-slate-500">
                    @yield('auth_footer')
                </div>
            @endif
        </div>
    </div>
</div>

@include('layouts.tw.partials.alerts')

@yield('js')
@stack('scripts')
</body>
</html>
