@extends('layouts.tw.app')

@section('title', 'Parametrizar precios')

@section('content_header')
    <h1>Parametrizar precios</h1>
@endsection

@section('subtitle', 'Precios por zona y periodo, usados por los reportes de producción.')

@section('actions')
    <a href="{{ route('produccion.reporteConsolidado') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Ir al consolidado
    </a>
@endsection

@section('content')
    <div x-data="parametrizarPrecios({
            registros: {{ Js::from($fechaPrecios->map(fn ($p) => [
                'id' => $p->id,
                'fecha_inicio' => $p->fecha_inicio,
                'fecha_fin' => $p->fecha_fin,
                'res_metro' => (int) $p->res_metro,
                'res_norte' => (int) $p->res_norte,
                'res_cauca' => (int) $p->res_cauca,
                'com_metro' => (int) $p->com_metro,
                'com_norte' => (int) $p->com_norte,
                'com_cauca' => (int) $p->com_cauca,
                'inspeccion_industrial' => (int) $p->inspeccion_industrial,
            ])->values()) }},
            urls: {
                guardar:    '{{ route('fechasParametro.guardar') }}',
                actualizar: '{{ route('fechasParametro.actualizar') }}',
            },
         })"
         class="space-y-6">

        {{-- =============================== FORMULARIO ======================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip" :class="editando ? 'chip-amber' : 'chip-blue'">
                        <i class="fas" :class="editando ? 'fa-pen' : 'fa-tags'"></i>
                    </span>
                    <div>
                        <h2 class="tw-card-title"
                            x-text="editando ? `Editar el periodo #${form.id}` : 'Nuevo periodo'"></h2>
                        <p class="tw-card-subtitle">
                            Todos los campos son obligatorios. El periodo no puede solaparse con otro.
                        </p>
                    </div>
                </div>

                <button type="button" class="tw-btn-secondary" x-show="editando" x-cloak
                        @click="cancelarEdicion()">
                    <i class="fas fa-xmark"></i> Cancelar edición
                </button>
            </div>

            <div class="space-y-5 px-5 py-5">
                <template x-if="error">
                    <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                                dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                         x-text="error"></div>
                </template>

                {{-- Periodo e inspección industrial --}}
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <label class="tw-label" for="fechaPrecioInicio">Desde</label>
                        <input type="month" id="fechaPrecioInicio" class="tw-input"
                               :class="claseCampo('fecha_inicio')"
                               x-model="form.fecha_inicio" :max="form.fecha_fin || null">
                    </div>
                    <div>
                        <label class="tw-label" for="fechaPrecioFin">Hasta</label>
                        <input type="month" id="fechaPrecioFin" class="tw-input"
                               :class="claseCampo('fecha_fin')"
                               x-model="form.fecha_fin" :min="form.fecha_inicio || null">
                    </div>
                    <div>
                        <label class="tw-label" for="inspeccionInd">Inspección industrial</label>
                        @include('reporteProduccion.partials.campo-precio',
                                 ['id' => 'inspeccionInd', 'campo' => 'inspeccion_industrial'])
                    </div>
                </div>

                @foreach ([
                    ['Residencial', 'fa-house', 'chip-blue', 'res'],
                    ['Comercial', 'fa-shop', 'chip-emerald', 'com'],
                ] as [$grupo, $icono, $tinte, $pre])
                    <div class="rounded-xl border border-slate-200 p-4 dark:border-slate-700">
                        <div class="mb-3 flex items-center gap-2">
                            <span class="tw-badge {{ $tinte }}">
                                <i class="fas {{ $icono }}"></i> {{ $grupo }}
                            </span>
                        </div>
                        <div class="grid gap-4 sm:grid-cols-3">
                            @foreach ([
                                ['Metropolitano', 'metro'],
                                ['Norte del Valle', 'norte'],
                                ['Cauca / Buenaventura', 'cauca'],
                            ] as [$etiqueta, $zona])
                                <div>
                                    <label class="tw-label" for="{{ $zona }}{{ ucfirst($pre) }}">{{ $etiqueta }}</label>
                                    @include('reporteProduccion.partials.campo-precio', [
                                        'id' => $zona . ucfirst($pre),
                                        'campo' => $pre . '_' . $zona,
                                    ])
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="flex flex-wrap justify-end gap-2 border-t border-slate-200/80 px-5 py-4
                        dark:border-slate-700/60">
                <button type="button" class="tw-btn-secondary" @click="limpiar()">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
                <button type="button" class="tw-btn-primary" :disabled="enviando" @click="enviar()">
                    <i class="fas" :class="enviando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                    <span x-text="editando ? 'Guardar cambios' : 'Guardar'"></span>
                </button>
            </div>
        </section>

        {{-- ================================ LISTADO ========================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-slate"><i class="fas fa-list"></i></span>
                    <div>
                        <h2 class="tw-card-title">Periodos parametrizados</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de
                            <span x-text="registros.length"></span>
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-64">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                              -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Buscar por periodo…"
                           x-model="busqueda">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Periodo</th>
                        <th class="text-right">Res. metro</th>
                        <th class="text-right">Res. norte</th>
                        <th class="text-right">Res. cauca</th>
                        <th class="text-right">Com. metro</th>
                        <th class="text-right">Com. norte</th>
                        <th class="text-right">Com. cauca</th>
                        <th class="text-right">Insp. industrial</th>
                        <th class="text-right">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="p in filtrados" :key="p.id">
                        {{-- La fila en edición queda marcada: antes solo se sabía
                             por el título del formulario, arriba del todo. --}}
                        <tr :class="editando && form.id === p.id
                                ? 'bg-amber-50 dark:bg-amber-950/30' : ''">
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="p.id"></td>
                            <td class="whitespace-nowrap font-medium text-slate-800 dark:text-slate-100"
                                x-text="`${p.fecha_inicio} → ${p.fecha_fin}`"></td>
                            <template x-for="campo in CAMPOS" :key="campo">
                                <td class="whitespace-nowrap text-right tabular-nums"
                                    x-text="moneda(p[campo])"></td>
                            </template>
                            <td class="text-right">
                                <button type="button" class="tw-btn-secondary tw-btn-sm"
                                        @click="editar(p)">
                                    <i class="fas fa-pen"></i> Editar
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filtrados.length === 0" x-cloak>
                        <td colspan="10" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-tags mb-2 block text-2xl opacity-40"></i>
                            No hay periodos parametrizados.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection

@section('js')
    @include('reporteProduccion.partials.precios-script')
@endsection
