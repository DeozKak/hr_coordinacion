@extends('layouts.tw.app')

@section('title', 'Control de Stickers')

@section('content_header')
    <h1>Control de Stickers</h1>
@endsection

@section('subtitle', 'Inventario general y asignación por inspector.')

@php
    /* Paleta heredada de indexV2.3.css: se conservan los mismos colores por tipo
       de sticker para no romper el código visual que el equipo ya reconoce. */
    $paleta = [
        'actas'          => ['color' => '#735677', 'icono' => 'fa-file-signature'],
        'amarillos'      => ['color' => '#fdbd00', 'icono' => 'fa-note-sticky'],
        'rojos'          => ['color' => '#f21b35', 'icono' => 'fa-note-sticky'],
        'suspension'     => ['color' => '#69747c', 'icono' => 'fa-ban'],
        'cons de visita' => ['color' => '#3498db', 'icono' => 'fa-clipboard-check'],
        'isometricos'    => ['color' => '#8b4513', 'icono' => 'fa-ruler-combined'],
    ];
    $porDefecto = ['color' => '#4d5863', 'icono' => 'fa-circle'];

    $tipos = $Stickers->map(function ($s) use ($paleta, $porDefecto) {
        $clave = strtolower($s->nombre);
        $meta  = $paleta[$clave] ?? $porDefecto;
        return [
            'id'          => (string) $s->id,
            'nombre'      => $s->nombre,
            'clave'       => $clave,
            // El backend distingue ACTAS por nombre; se replica el mismo criterio.
            'esActa'      => str_contains($clave, 'actas'),
            'inventario'  => (int) (optional($s->Inventario)->cantidad_disponible ?? 0),
            'color'       => $meta['color'],
            'icono'       => $meta['icono'],
        ];
    })->values();

    $inventarioInicial = $tipos->pluck('inventario', 'id');

    $filas = $inspectores->map(function ($i) use ($Stickers, $paleta, $porDefecto) {
        $asignados = [];
        foreach ($Stickers as $t) {
            $asignados[(string) $t->id] = (int) (optional(
                $i->Stickers->firstWhere('id_sticker_tipo', $t->id)
            )->cantidad_asignada ?? 0);
        }

        $historial = $i->HistoricoStickers
            ->sortByDesc('fecha_asignacion')
            ->map(function ($r) use ($Stickers, $paleta, $porDefecto) {
                $tipo  = $Stickers->firstWhere('id', $r->id_sticker_tipo);
                $clave = strtolower(optional($tipo)->nombre ?? '');
                return [
                    'fecha'    => \Carbon\Carbon::parse($r->fecha_asignacion)->format('d-m-Y'),
                    'cantidad' => (int) $r->cantidad,
                    'tipo'     => $clave !== '' ? ucfirst($clave) : '—',
                    'color'    => ($paleta[$clave] ?? $porDefecto)['color'],
                ];
            })->values();

        return [
            'id'         => (string) $i->id,
            'nombre'     => $i->nombre_completo,
            'asignados'  => $asignados,
            'total'      => array_sum($asignados),
            'historial'  => $historial,
        ];
    })->values();

    $puedeControlar = auth()->user()?->can('control_stickers') ?? false;
@endphp

@section('content')
    <div x-data="controlStickers({
            tipos: {{ Js::from($tipos) }},
            inventarioInicial: {{ Js::from($inventarioInicial) }},
            inspectores: {{ Js::from($filas) }},
            urls: {
                actualizarInventario: '{{ route('bitacora.stickers.ActualizarInventario', ['id' => ':id']) }}',
                inventario:           '{{ route('bitacora.stickers.getInventario') }}',
                asignar:              '{{ route('bitacora.stickers.asignar') }}',
                desasignar:           '{{ route('bitacora.stickers.desasignar') }}',
                stickersAsignados:    '{{ route('bitacora.stickers.getStickersAsignados', ['idInspector' => ':id']) }}',
                serialesInventario:   '{{ route('bitacora.stickers.getSerialesActas') }}',
                serialesAsignados:    '{{ route('bitacora.stickers.getSerialesAsignados', ['idInspector' => ':id']) }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== RESUMEN ============================== --}}
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="tw-card p-4 2xl:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tw-eyebrow">Disponible en inventario</p>
                        <p class="tw-metric mt-2" x-text="totalInventario.toLocaleString('es-CO')">0</p>
                    </div>
                    <span class="tw-chip chip-blue"><i class="fas fa-boxes-stacked"></i></span>
                </div>
                <p class="tw-hint mt-3">
                    <i class="fas fa-layer-group"></i>
                    {{ $tipos->count() }} {{ $tipos->count() === 1 ? 'tipo de sticker' : 'tipos de sticker' }}
                </p>
            </div>

            <div class="tw-card p-4 2xl:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tw-eyebrow">Asignado a inspectores</p>
                        <p class="tw-metric mt-2" x-text="totalAsignado.toLocaleString('es-CO')">0</p>
                    </div>
                    <span class="tw-chip chip-emerald"><i class="fas fa-user-check"></i></span>
                </div>
                <p class="tw-hint mt-3">
                    <i class="fas fa-users"></i>
                    <span x-text="conAsignacion"></span> con stickers en su poder
                </p>
            </div>

            <div class="tw-card p-4 2xl:p-5">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="tw-eyebrow">Inspectores activos</p>
                        <p class="tw-metric mt-2">{{ $filas->count() }}</p>
                    </div>
                    <span class="tw-chip chip-violet"><i class="fas fa-helmet-safety"></i></span>
                </div>
                <p class="tw-hint mt-3">
                    <i class="fas fa-circle-check"></i>
                    Con estado activo en el maestro de inspectores
                </p>
            </div>
        </section>

        {{-- =========================== INVENTARIO ============================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-warehouse"></i></span>
                    <div>
                        <h2 class="tw-card-title">Inventario general</h2>
                        <p class="tw-card-subtitle">Unidades disponibles por tipo de sticker</p>
                    </div>
                </div>
                @can('control_stickers')
                    <button type="button" class="tw-btn-primary" @click="abrirAgregar()">
                        <i class="fas fa-plus"></i> Agregar a inventario
                    </button>
                @endcan
            </div>

            <div class="tw-card-body">
                <div class="grid gap-4 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6">
                    @foreach($tipos as $t)
                        <div class="relative overflow-hidden rounded-xl border border-slate-200 bg-white p-4 shadow-sm
                                    transition hover:shadow-md dark:border-slate-700 dark:bg-slate-800/60">
                            <span class="absolute inset-x-0 top-0 h-1"
                                  style="background-color: {{ $t['color'] }}"></span>

                            <div class="flex items-center gap-2">
                                <span class="h-2.5 w-2.5 shrink-0 rounded-full"
                                      style="background-color: {{ $t['color'] }}"></span>
                                <p class="tw-eyebrow truncate" title="{{ $t['nombre'] }}">{{ $t['nombre'] }}</p>
                            </div>

                            <div class="mt-3 flex items-end justify-between gap-2">
                                <p class="text-3xl font-bold leading-none tracking-tight text-slate-900 dark:text-white"
                                   x-text="(inventario['{{ $t['id'] }}'] ?? 0).toLocaleString('es-CO')">
                                    {{ number_format($t['inventario'], 0, ',', '.') }}
                                </p>
                                @if($t['esActa'])
                                    <button type="button"
                                            class="tw-btn-secondary tw-btn-sm shrink-0"
                                            title="Ver seriales disponibles"
                                            @click="verSerialesInventario()">
                                        <i class="fas fa-list-ol"></i>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- ======================= ASIGNACIÓN POR INSPECTOR ==================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-emerald"><i class="fas fa-user-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Asignación por inspector</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de {{ $filas->count() }} inspectores
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-72">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2
                              text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Buscar inspector…"
                           x-model="busqueda" @input="pagina = 1">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                    <tr>
                        <th>
                            <button type="button" class="inline-flex items-center gap-1.5 uppercase tracking-[0.06em]"
                                    @click="ordenarPor('nombre')">
                                <i class="fas fa-user"></i> Inspector
                                <i class="fas text-[0.625rem]"
                                   :class="orden === 'nombre'
                                        ? (direccion === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short')
                                        : 'fa-sort opacity-40'"></i>
                            </button>
                        </th>
                        <th>
                            <button type="button" class="inline-flex items-center gap-1.5 uppercase tracking-[0.06em]"
                                    @click="ordenarPor('total')">
                                <i class="fas fa-note-sticky"></i> Stickers asignados
                                <i class="fas text-[0.625rem]"
                                   :class="orden === 'total'
                                        ? (direccion === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short')
                                        : 'fa-sort opacity-40'"></i>
                            </button>
                        </th>
                        <th><i class="fas fa-clock-rotate-left"></i> Último movimiento</th>
                        @can('control_stickers')
                            <th class="text-right"><i class="fas fa-gears"></i> Acciones</th>
                        @endcan
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="insp in paginados" :key="insp.id">
                        <tr>
                            {{-- Inspector --}}
                            <td class="min-w-[220px] align-top">
                                <div class="flex items-center gap-3">
                                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-full
                                                 bg-brand-100 text-xs font-bold text-brand-700
                                                 dark:bg-brand-900/60 dark:text-brand-200"
                                          x-text="iniciales(insp.nombre)"></span>
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-slate-800 dark:text-slate-100"
                                           x-text="insp.nombre"></p>
                                        <p class="text-xs text-slate-500 dark:text-slate-400">
                                            <span x-text="insp.total"></span> en total
                                        </p>
                                    </div>
                                </div>
                            </td>

                            {{-- Stickers asignados --}}
                            <td class="align-top">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    <template x-for="t in tipos" :key="t.id">
                                        <span class="inline-flex items-center gap-1.5 rounded-lg border px-2 py-1 text-xs
                                                     font-semibold transition"
                                              :class="(insp.asignados[t.id] ?? 0) > 0
                                                    ? 'border-slate-200 bg-white text-slate-700 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-200'
                                                    : 'border-transparent bg-slate-100 text-slate-400 dark:bg-slate-700/40 dark:text-slate-500'"
                                              :title="t.nombre">
                                            <span class="h-2 w-2 shrink-0 rounded-full"
                                                  :style="`background-color: ${t.color}`"
                                                  :class="(insp.asignados[t.id] ?? 0) > 0 ? '' : 'opacity-40'"></span>
                                            <span x-text="insp.asignados[t.id] ?? 0"></span>
                                            <template x-if="t.esActa">
                                                <button type="button"
                                                        class="ml-0.5 text-slate-400 transition hover:text-brand-600
                                                               dark:hover:text-brand-300"
                                                        title="Ver seriales asignados"
                                                        @click="verSerialesInspector(insp)">
                                                    <i class="fas fa-list-ol"></i>
                                                </button>
                                            </template>
                                        </span>
                                    </template>
                                </div>
                            </td>

                            {{-- Historial --}}
                            <td class="min-w-[240px] align-top">
                                <template x-if="insp.historial.length === 0">
                                    <span class="text-xs italic text-slate-400 dark:text-slate-500">Sin historial</span>
                                </template>
                                <div class="space-y-1" x-show="insp.historial.length > 0">
                                    <template x-for="(h, i) in insp.historial" :key="i">
                                        <div class="flex items-center gap-2 text-xs">
                                            <span class="font-mono text-slate-500 dark:text-slate-400" x-text="h.fecha"></span>
                                            <span class="h-2 w-2 shrink-0 rounded-full"
                                                  :style="`background-color: ${h.color}`"></span>
                                            <span class="font-semibold"
                                                  :class="h.cantidad < 0
                                                        ? 'text-rose-600 dark:text-rose-400'
                                                        : 'text-emerald-600 dark:text-emerald-400'"
                                                  x-text="(h.cantidad > 0 ? '+' : '') + h.cantidad"></span>
                                            <span class="truncate text-slate-500 dark:text-slate-400" x-text="h.tipo"></span>
                                        </div>
                                    </template>
                                </div>
                            </td>

                            @can('control_stickers')
                                <td class="align-top">
                                    <div class="flex justify-end gap-2">
                                        <button type="button" class="tw-btn-primary tw-btn-sm"
                                                @click="abrirAsignar(insp)">
                                            <i class="fas fa-plus"></i> Asignar
                                        </button>
                                        <button type="button" class="tw-btn-danger tw-btn-sm"
                                                @click="abrirDesasignar(insp)">
                                            <i class="fas fa-minus"></i> Desasignar
                                        </button>
                                    </div>
                                </td>
                            @endcan
                        </tr>
                    </template>

                    <tr x-show="filtrados.length === 0">
                        <td colspan="{{ $puedeControlar ? 4 : 3 }}" class="py-10 text-center">
                            <i class="fas fa-user-slash mb-2 block text-2xl text-slate-300 dark:text-slate-600"></i>
                            <span class="text-sm text-slate-500 dark:text-slate-400">
                                Ningún inspector coincide con la búsqueda.
                            </span>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3
                        dark:border-slate-700/60"
                 x-show="filtrados.length > porPagina">
                <p class="text-xs text-slate-500 dark:text-slate-400">
                    Página <span class="font-semibold" x-text="paginaActual"></span>
                    de <span class="font-semibold" x-text="totalPaginas"></span>
                </p>
                <div class="flex gap-2">
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            :disabled="paginaActual <= 1" @click="pagina = paginaActual - 1">
                        <i class="fas fa-chevron-left"></i> Anterior
                    </button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            :disabled="paginaActual >= totalPaginas" @click="pagina = paginaActual + 1">
                        Siguiente <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
        </section>

        @include('stickers.modales.modales')
    </div>
@endsection

@section('js')
    @include('stickers.partials.index-script')
@endsection
