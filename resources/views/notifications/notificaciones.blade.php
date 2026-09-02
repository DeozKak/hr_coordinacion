@extends('layouts.tw.app')

@section('title', 'Mis notificaciones')

@section('content_header')
    <h1>Mis notificaciones</h1>
@endsection

@section('subtitle', 'Avisos que te ha enviado el sistema, del más reciente al más antiguo.')

@section('content')
    <div class="mx-auto max-w-3xl">
        <section class="tw-card">
            @if ($notifications->count() > 0)
                @php
                    /* Se agrupan por día para poner una sola etiqueta de fecha,
                       que es lo que hacía la línea de tiempo anterior. */
                    $porDia = $notifications->groupBy(fn ($n) => $n->created_at->format('Y-m-d'));
                @endphp

                <div class="divide-y divide-slate-200/80 dark:divide-slate-700/60">
                    @foreach ($porDia as $dia => $delDia)
                        <div class="p-5">
                            <p class="mb-4 text-[11px] font-semibold uppercase tracking-[0.08em] text-slate-500
                                      dark:text-slate-400">
                                {{-- El idioma se pide explícito: la aplicación corre con locale 'en'. --}}
                                {{ \Carbon\Carbon::parse($dia)->locale('es')->translatedFormat('d \d\e F \d\e Y') }}
                            </p>

                            <ul class="space-y-3">
                                @foreach ($delDia as $notificacion)
                                    @php
                                        $datos = $notificacion->data;
                                        $enlace = $datos['link'] ?? null;
                                        $texto = trim(($datos['text'] ?? 'Notificación') . ' ' . ($datos['user'] ?? ''));
                                    @endphp

                                    <li @class([
                                            'flex items-start gap-3 rounded-xl border border-slate-200/80 p-3.5',
                                            'dark:border-slate-700/60',
                                            'transition hover:border-brand-300 hover:bg-slate-50 dark:hover:bg-slate-700/40' => $enlace,
                                        ])>
                                        <span class="tw-chip chip-blue h-9 w-9 shrink-0 text-sm">
                                            <i class="{{ $datos['icon'] ?? 'fas fa-bell' }}"></i>
                                        </span>

                                        <div class="min-w-0 flex-1">
                                            {{-- Enlazado sólo el texto, no la tarjeta entera: así el
                                                 destino se lee en la barra de estado y se puede abrir
                                                 en otra pestaña. --}}
                                            @if ($enlace)
                                                <a href="{{ $enlace }}"
                                                   class="block text-sm font-medium text-slate-800 hover:text-brand-700
                                                          dark:text-slate-100 dark:hover:text-brand-300">
                                                    {{ $texto }}
                                                    <i class="fas fa-arrow-up-right-from-square ml-1 text-[10px] opacity-50"></i>
                                                </a>
                                            @else
                                                <span class="block text-sm font-medium text-slate-800 dark:text-slate-100">
                                                    {{ $texto }}
                                                </span>
                                            @endif

                                            <span class="mt-0.5 block text-xs text-slate-500">
                                                <i class="far fa-clock"></i>
                                                {{ $notificacion->created_at->diffForHumans() }}
                                            </span>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>

                @if ($notifications->hasPages())
                    {{-- Paginación propia: la vista que trae Laravel vive en vendor/,
                         que Tailwind no rastrea, así que sus clases no se compilan
                         y saldría sin estilos. --}}
                    <div class="flex items-center justify-between gap-3 border-t border-slate-200/80 p-4 text-sm
                                dark:border-slate-700/60">
                        <span class="text-slate-500">
                            Página {{ $notifications->currentPage() }} de {{ $notifications->lastPage() }}
                        </span>
                        <div class="flex gap-2">
                            @if ($notifications->onFirstPage())
                                <span class="tw-btn-secondary tw-btn-sm cursor-not-allowed opacity-50">Anterior</span>
                            @else
                                <a href="{{ $notifications->previousPageUrl() }}"
                                   class="tw-btn-secondary tw-btn-sm">Anterior</a>
                            @endif

                            @if ($notifications->hasMorePages())
                                <a href="{{ $notifications->nextPageUrl() }}"
                                   class="tw-btn-secondary tw-btn-sm">Siguiente</a>
                            @else
                                <span class="tw-btn-secondary tw-btn-sm cursor-not-allowed opacity-50">Siguiente</span>
                            @endif
                        </div>
                    </div>
                @endif
            @else
                <div class="px-5 py-16 text-center">
                    <i class="fas fa-bell-slash mb-3 block text-3xl text-slate-300 dark:text-slate-600"></i>
                    <p class="text-sm text-slate-500">No tienes notificaciones.</p>
                </div>
            @endif
        </section>
    </div>
@endsection
