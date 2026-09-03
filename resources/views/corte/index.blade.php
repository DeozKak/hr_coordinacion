@extends('layouts.tw.app')

@section('title', 'Cortes de producción')

@section('content_header')
    <h1>Cortes de producción</h1>
@endsection

@section('subtitle', 'Periodos de corte y causales de devolución.')

@php
    /* La primera causal es la de por defecto y no se toca: el listado original
       escondía sus botones comparando el índice. */
    $causalesPayload = $causales->values()->map(fn ($c, $i) => [
        'id' => $c->id,
        'nombre' => $c->nom_causal,
        'activa' => (bool) $c->status,
        'fija' => $i === 0,
    ]);
@endphp

@section('content')
    <div x-data="cortesProduccion({
            cortes: {{ Js::from($cortes->map(fn ($c) => [
                'id' => $c->id,
                'nombre' => $c->nombre,
                'fecha_inicio' => $c->fecha_inicio,
                'fecha_fin' => $c->fecha_fin,
                'meta' => $c->meta,
                'dobles' => $c->dobles,
            ])->values()) }},
            causales: {{ Js::from($causalesPayload) }},
            urls: {
                crearCorte:    '{{ route('cortes_produccion.store') }}',
                editarCorte:   '{{ route('cortes_produccion.editCorte', ['id' => '__id__']) }}',
                guardarCorte:  '{{ route('cortes_produccion.updateCorte', ['id' => '__id__']) }}',
                crearCausal:   '{{ route('cortes_produccion.storeCausal') }}',
                editarCausal:  '{{ route('cortes_produccion.editCausal', ['id' => '__id__']) }}',
                guardarCausal: '{{ route('cortes_produccion.updateCausal', ['id' => '__id__']) }}',
                estadoCausal:  '{{ route('cortes_produccion.changeStatusCausal') }}',
                detalles:      '{{ route('produccion.detallesCorte', ['id' => '__id__']) }}',
                fallidas:      '{{ route('produccion.fallidas.detalles', ['id' => '__id__']) }}',
                graficos:      '{{ route('produccion.index') }}',
            },
         })"
         class="grid gap-4 2xl:gap-6 lg:grid-cols-[minmax(0,7fr)_minmax(0,5fr)]">

        {{-- ============================== CORTES ============================= --}}
        <section class="tw-card self-start">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-calendar-days"></i></span>
                    <div>
                        <h2 class="tw-card-title">Cortes</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="cortesFiltrados.length"></span> de
                            <span x-text="cortes.length"></span> periodos
                        </p>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="relative min-w-0 flex-1 sm:w-52 sm:flex-none">
                        <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                                  -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search" class="tw-input pl-9" placeholder="Buscar corte…"
                               x-model="buscarCorte">
                    </div>
                    <button type="button" class="tw-btn-primary" @click="abrirCorte()">
                        <i class="fas fa-plus"></i> Crear corte
                    </button>
                </div>
            </div>

            <div class="max-h-[26rem] tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> Nombre</th>
                        <th><i class="fas fa-calendar-day"></i> Inicio</th>
                        <th><i class="fas fa-calendar-check"></i> Fin</th>
                        <th class="text-right"><i class="fas fa-bullseye"></i> Meta</th>
                        <th class="text-right"><i class="fas fa-layer-group"></i> Dobles</th>
                        <th class="text-right"><i class="fas fa-gears"></i> Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="corte in cortesFiltrados" :key="corte.id">
                        <tr>
                            <td class="font-medium text-slate-800 dark:text-slate-100" x-text="corte.nombre"></td>
                            <td class="whitespace-nowrap" x-text="corte.fecha_inicio"></td>
                            <td class="whitespace-nowrap" x-text="corte.fecha_fin"></td>
                            <td class="text-right tabular-nums" x-text="corte.meta"></td>
                            <td class="text-right tabular-nums" x-text="corte.dobles"></td>
                            <td class="text-right">
                                <div class="flex flex-wrap justify-end gap-1.5">
                                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                                            @click="abrirCorte(corte)" title="Editar el corte">
                                        <i class="fas fa-pen"></i>
                                    </button>
                                    <a :href="urls.detalles.replace('__id__', corte.id)"
                                       class="tw-btn-secondary tw-btn-sm" title="Detalles del corte">
                                        <i class="fas fa-list"></i>
                                    </a>
                                    <a :href="urls.fallidas.replace('__id__', corte.id)"
                                       class="tw-btn-secondary tw-btn-sm" title="Fallidas del corte">
                                        <i class="fas fa-triangle-exclamation"></i>
                                    </a>
                                    <a :href="`${urls.graficos}?id=${corte.id}`"
                                       class="tw-btn-secondary tw-btn-sm" title="Gráficos del corte">
                                        <i class="fas fa-chart-column"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="cortesFiltrados.length === 0" x-cloak>
                        <td colspan="6" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-calendar-xmark mb-2 block text-2xl opacity-40"></i>
                            No hay cortes que coincidan.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ============================= CAUSALES ============================ --}}
        <section class="tw-card self-start">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-rotate-left"></i></span>
                    <div>
                        <h2 class="tw-card-title">Causales de devolución</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="causalesFiltradas.length"></span> de
                            <span x-text="causales.length"></span> causales
                        </p>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="relative min-w-0 flex-1 sm:w-44 sm:flex-none">
                        <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                                  -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search" class="tw-input pl-9" placeholder="Buscar causal…"
                               x-model="buscarCausal">
                    </div>
                    <button type="button" class="tw-btn-primary" @click="abrirCausal()">
                        <i class="fas fa-plus"></i> Crear causal
                    </button>
                </div>
            </div>

            <div class="max-h-[26rem] tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead>
                    <tr>
                        <th><i class="fas fa-tag"></i> Nombre</th>
                        <th><i class="fas fa-toggle-on"></i> Estado</th>
                        <th class="text-right"><i class="fas fa-gears"></i> Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="causal in causalesFiltradas" :key="causal.id">
                        <tr>
                            <td class="font-medium text-slate-800 dark:text-slate-100" x-text="causal.nombre"></td>
                            <td>
                                <span class="tw-badge" :class="causal.activa ? 'chip-emerald' : 'chip-rose'"
                                      x-text="causal.activa ? 'Activa' : 'Inactiva'"></span>
                            </td>
                            <td class="text-right">
                                {{-- La causal por defecto no se edita ni se desactiva. --}}
                                <div class="flex justify-end gap-2" x-show="!causal.fija">
                                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                                            @click="abrirCausal(causal)">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                    <button type="button" class="tw-btn-sm"
                                            :class="causal.activa ? 'tw-btn-danger' : 'tw-btn-primary'"
                                            @click="cambiarEstadoCausal(causal)"
                                            x-text="causal.activa ? 'Desactivar' : 'Activar'"></button>
                                </div>
                                <span class="text-xs text-slate-400" x-show="causal.fija" x-cloak>
                                    Por defecto
                                </span>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="causalesFiltradas.length === 0" x-cloak>
                        <td colspan="3" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-inbox mb-2 block text-2xl opacity-40"></i>
                            No hay causales que coincidan.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        @include('corte.partials.modales')
    </div>
@endsection

@section('js')
    @include('corte.partials.index-script')
@endsection
