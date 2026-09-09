@extends('layouts.tw.app')

@section('title', 'Fallidas')

@section('content_header')
    <h1>Gestión de fallidas</h1>
@endsection

@section('subtitle', 'Inspecciones fallidas por inspector y día del corte.')

@section('actions')
    <button type="button" class="tw-btn-secondary" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Ir atrás
    </button>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="detallesFallidas({
            urls: {
                datos:           '{{ route('produccion.fallidas.data') }}',
                obtenerDetalles: '{{ route('obtener-url-detalles-fallidas') }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-triangle-exclamation"></i></span>
                    <div>
                        <h2 class="tw-card-title">Fallidas por día</h2>
                        <p class="tw-card-subtitle">
                            <span class="hidden lg:inline">
                                Doble clic en la esquina de una celda de día para ver sus fallidas
                            </span>
                            <span class="lg:hidden">
                                Toca un inspector para ver sus días; toca un día para ver sus fallidas
                            </span>
                        </p>
                    </div>
                </div>

                <button type="button" class="tw-btn-secondary" @click="exportar()">
                    <i class="fas fa-download"></i> Exportar
                </button>
            </div>

            {{-- Mismos colores que el diseño anterior, ahora documentados. --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 px-5 py-3
                        dark:border-slate-700/60">
                <span class="tw-eyebrow">Código de color</span>
                @foreach ([['dia', 'Día del corte'], ['total', 'Totales']] as [$clave, $texto])
                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10 leyenda-{{ $clave }}"></span>
                        {{ $texto }}
                    </span>
                @endforeach
            </div>

            {{-- Rejilla: sólo de `lg` para arriba. La construye el JS únicamente
                 cuando se ve, porque Handsontable mide el contenedor al nacer y
                 con display:none saldría con ancho cero. --}}
            <div class="hidden border-t border-slate-200/80 lg:block dark:border-slate-700/60">
                <div id="detalles" class="ht-theme-main ht-compacta"></div>
            </div>

            {{-- ===================== VARIANTE MÓVIL ====================== --}}
            {{-- Una tarjeta por inspector. Sustituye a la rejilla porque sus dos
                 columnas congeladas suman 430px —más que la pantalla— y porque el
                 detalle del día se abría con doble clic en la esquina de arrastre
                 de la celda, un gesto que en táctil no existe. --}}
            <div class="border-t border-slate-200/80 lg:hidden dark:border-slate-700/60">
                <div x-show="!cargando && tarjetas.length === 0" x-cloak
                     class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    No hay fallidas registradas en este corte.
                </div>

                <div class="divide-y divide-slate-200/80 dark:divide-slate-700/60">
                    <template x-for="fila in tarjetas" :key="fila.cedula">
                        <article>
                            <button type="button" class="flex w-full items-center gap-3 px-4 py-3 text-left"
                                    :aria-expanded="!!desplegadas[fila.cedula]"
                                    @click="alternarTarjeta(fila.cedula)">
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-sm font-semibold text-slate-800 dark:text-slate-100"
                                       x-text="fila.nombres"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        CC <span x-text="fila.cedula"></span>
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-md border border-black/5
                                             px-2 py-1 text-[11px] font-medium"
                                      :style="estiloDeClave('total')">
                                    <span class="opacity-70">Total</span>
                                    <span x-text="fila.total"></span>
                                </span>
                                <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform"
                                   :class="desplegadas[fila.cedula] && 'rotate-180'"></i>
                            </button>

                            <div x-show="desplegadas[fila.cedula]" x-collapse x-cloak>
                                <div class="grid grid-cols-3 gap-2 px-4 pb-4 sm:grid-cols-4">
                                    <template x-for="dia in diasDe(fila)" :key="dia.fecha">
                                        <button type="button"
                                                class="flex flex-col items-center gap-0.5 rounded-lg border
                                                       border-black/5 px-2 py-2 text-center active:scale-95"
                                                :style="estiloDeClave('dia')"
                                                @click="abrirDiaMovil(fila, dia)">
                                            <span class="text-[10px] uppercase opacity-70" x-text="dia.corta"></span>
                                            <span class="text-base font-semibold leading-none"
                                                  x-text="dia.valor || '—'"></span>
                                        </button>
                                    </template>
                                </div>
                            </div>
                        </article>
                    </template>
                </div>
            </div>
        </section>

        {{-- ===================== MODAL: FALLIDAS DEL DÍA ==================== --}}
        <x-modal show="modal === 'dia'" close="cerrarDia()" size="max-w-[95vw]"
                 icon="fa-calendar-day" tint="amber">
            <x-slot:titleSlot><span x-text="tituloDia"></span></x-slot:titleSlot>
            <x-slot:subtitle>Fallidas registradas en el día</x-slot:subtitle>

            <div class="px-4 py-4 2xl:px-5 2xl:py-5">
                <div x-show="sinDatosDia" x-cloak
                     class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-900
                            dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
                    <i class="fas fa-triangle-exclamation mr-1"></i>
                    No hay fallidas registradas para este día.
                </div>

                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                    <div id="contratos_dia" class="ht-theme-main ht-compacta"></div>
                </div>
            </div>

            <x-slot:footer>
                <button type="button" class="tw-btn-secondary" @click="cerrarDia()">Cerrar</button>
            </x-slot:footer>
        </x-modal>

        {{-- Velo de carga --}}
        <div x-show="cargando" x-cloak
             class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-2xl dark:bg-slate-800">
                <i class="fas fa-spinner fa-spin mb-3 block text-3xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Cargando información…</p>
            </div>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .leyenda-dia   { background: rgb(215, 232, 255); }
        .leyenda-total { background: rgb(250, 243, 152); }
        .dark .leyenda-dia   { background: #1e3a5f; }
        .dark .leyenda-total { background: #4a3a1a; }
    </style>
@endpush

@section('js')
    @include('produccion.partials.fallidas-script')
@endsection
