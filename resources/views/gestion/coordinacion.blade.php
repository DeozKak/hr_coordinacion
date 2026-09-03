@extends('layouts.tw.app')

@section('title', 'Coordinación RP')

@section('content_header')
    <h1>Coordinación RP</h1>
@endsection

@section('subtitle', 'Programación, asignación y cierre de las órdenes activas.')

@include('layouts.tw.partials.handsontable')

@php
    $opcInspectores = $inspectors->map(fn ($i) => [
        'value' => $i->id, 'label' => "{$i->id} - {$i->nombres} {$i->apellidos}",
    ])->values();

    /* Las sedes del filtro estaban escritas a mano en el HTML. */
    $opcSedes = [
        ['value' => 1, 'label' => 'Capital'],
        ['value' => 2, 'label' => 'Norte'],
        ['value' => 3, 'label' => 'Sur'],
    ];
@endphp

@section('content')
    <div x-data="coordinacion({
            urls: {
                todo:        '{{ route('getdataCoordinacionRP') }}',
                filtrar:     '{{ route('filterData') }}',
                programacion:'{{ route('guardarProgramacionTecnico') }}',
                grupos:      '{{ route('getGroupsForSede') }}',
                subgrupos:   '{{ route('getDataSubGroups') }}',
                excel:       '{{ route('descargarExcelCoordination') }}',
                causaCierre: '{{ route('guardarCausaCierre') }}',
                fechaCierre: '{{ route('guardarFechaSolicitudCierre') }}',
                marca:       '{{ route('marcaOrden') }}',
                marcaMasiva: '{{ route('marcaOrdenMasiva') }}',
                cercania:    '{{ route('asignarOrdCercania') }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- =============================== FILTROS =========================== --}}
        <section class="tw-card">
            <button type="button" class="tw-card-header w-full text-left" @click="filtrosAbiertos = !filtrosAbiertos">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-filter"></i></span>
                    <div>
                        <h2 class="tw-card-title">Filtros</h2>
                        <p class="tw-card-subtitle">
                            Grupo y sub grupo se cargan según la sede y el grupo elegidos.
                        </p>
                    </div>
                </div>
                <i class="fas fa-chevron-down text-sm text-slate-400 transition"
                   :class="filtrosAbiertos && 'rotate-180'"></i>
            </button>

            <div x-show="filtrosAbiertos" x-cloak>
                <div class="grid gap-4 p-4 2xl:p-5 sm:grid-cols-2 lg:grid-cols-3">
                    <x-lista-numeros label="Orden" model="filtros.orden" />
                    <x-lista-numeros label="Orden externa" model="filtros.orden_solicitud_externa" />
                    <x-lista-numeros label="Contrato" model="filtros.contrato" />

                    <x-multi-select label="Inspector" :options="$opcInspectores"
                                    model="filtros.codigo_tecnico" placeholder="Todos los inspectores" />

                    <div>
                        <label class="tw-label" for="localidad">Localidad</label>
                        <input type="text" id="localidad" class="tw-input" x-model="filtros.localidad">
                    </div>

                    <div>
                        <label class="tw-label" for="sector_operativo">Barrio</label>
                        <input type="text" id="sector_operativo" class="tw-input" x-model="filtros.sector_operativo">
                    </div>

                    <x-multi-select label="Sede" :options="$opcSedes"
                                    model="filtros.id_sede" placeholder="Todas las sedes" />

                    {{-- Grupo y sub grupo dependen de lo anterior: sus opciones son
                         estado de Alpine, no <option> escritos en el HTML. --}}
                    <x-select-buscador label="Grupo" model="filtros.id_grupo"
                                       options="opcionesGrupo" placeholder="Todos los grupos"
                                       onChange="cargarSubgrupos()" />

                    <x-select-buscador label="Sub grupo" model="filtros.id_subGrupo"
                                       options="opcionesSubgrupo" placeholder="Todos los sub grupos" />

                    <div class="grid grid-cols-2 gap-4 sm:col-span-2 lg:col-span-1">
                        <div>
                            <label class="tw-label" for="diasInicio">Días desde</label>
                            <input type="text" id="diasInicio" class="tw-input" inputmode="numeric"
                                   x-model="filtros.diasInicio"
                                   @input="filtros.diasInicio = soloEnteros($event.target.value)">
                        </div>
                        <div>
                            <label class="tw-label" for="diasFin">Días hasta</label>
                            <input type="text" id="diasFin" class="tw-input" inputmode="numeric"
                                   x-model="filtros.diasFin"
                                   @input="filtros.diasFin = soloEnteros($event.target.value)">
                        </div>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2 border-t border-slate-200/80 px-5 py-4
                            dark:border-slate-700/60">
                    <button type="button" class="tw-btn-primary" @click="buscar()" :disabled="cargando">
                        <i class="fas" :class="cargando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                        Buscar
                    </button>
                    <button type="button" class="tw-btn-secondary" @click="limpiar()" x-show="hayFiltro" x-cloak>
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                </div>
            </div>
        </section>

        {{-- ============================== RESULTADOS ========================= --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-diagram-project"></i></span>
                    <div>
                        <h2 class="tw-card-title">Coordinación RP</h2>
                        <p class="tw-card-subtitle" x-text="`${total} registros`"></p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <label class="flex cursor-pointer items-center gap-2 rounded-xl border border-slate-200 px-3 py-2
                                  text-sm dark:border-slate-600">
                        <input type="checkbox" x-model="marcarTodos" @change="marcarTodas()"
                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                        Marcar todas
                    </label>

                    <a :href="urls.excel" class="tw-btn-secondary tw-btn-sm">
                        <i class="fas fa-file-excel"></i> Excel
                    </a>
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="modal = 'impresion'">
                        <i class="fas fa-print"></i> Impresión masiva
                    </button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="asignarPorCercania()"
                            :disabled="asignando">
                        <i class="fas" :class="asignando ? 'fa-spinner fa-spin' : 'fa-location-arrow'"></i>
                        Asignar por cercanía
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80 px-5 py-3
                        text-sm dark:border-slate-700/60">
                <x-color-legend :items="[
                    ['g1', 'Base OSF'],
                    ['g2', 'Complementaria'],
                    ['g3', 'Programación'],
                    ['g4', 'Inspector'],
                    ['g5', 'Recepción'],
                    ['g6', 'Oficina'],
                    ['g7', 'Formulación'],
                ]" />

                <div class="flex items-center gap-2">
                    <span class="text-slate-500" x-text="`Página ${pagina} de ${totalPaginas}`"></span>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina - 1)" :disabled="pagina === 1 || cargando">Anterior</button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina + 1)" :disabled="pagina >= totalPaginas || cargando">Siguiente</button>
                </div>
            </div>

            <div class="relative border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaCoordinacion" class="ht-theme-main ht-compacta"></div>

                <div x-show="cargando" x-cloak
                     class="absolute inset-0 z-[900] flex items-center justify-center bg-white/70
                            backdrop-blur-[1px] dark:bg-slate-800/70">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
                </div>
            </div>

            <p x-show="!total && !cargando" x-cloak
               class="border-t border-slate-200/80 px-5 py-10 text-center text-sm text-slate-500
                      dark:border-slate-700/60">
                No se encontraron datos con los filtros seleccionados.
            </p>
        </section>

        @include('gestion.partials.coordinacion-modales')
    </div>
@endsection

@section('js')
    @include('gestion.partials.coordinacion-script')
@endsection
