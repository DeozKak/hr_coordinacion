@extends('layouts.tw.app')

@section('title', 'Zonificación')

@section('content_header')
    <h1>Zonificación</h1>
@endsection

@section('subtitle', 'Municipios, barrios y su relación con grupos, subgrupos e inspectores.')

@canany(['ver_residente', 'ver_coordinacion_RP'])
    @section('actions')
        {{-- El botón vive en la cabecera, fuera del x-data de la vista: se avisa
             por evento, igual que el de "Cargar Datos OSF" del inicio. --}}
        <button type="button" class="tw-btn-secondary" @click="$dispatch('abrir-sedes')">
            <i class="fas fa-building"></i> Gestionar sedes y zonas
        </button>
    @endsection
@endcanany

@include('layouts.tw.partials.handsontable')

@php
    /* Payloads explícitos: la vista no necesita los modelos completos. Los
       identificadores de sede y zona viajan con cada municipio para poder
       editarlo sin volver a pedirlo al servidor. */
    $municipiosPayload = $municipios->map(fn ($m) => [
        'id'      => $m->id,
        'nombre'  => $m->nombre,
        'id_sede' => $m->id_sede,
        'id_zona' => $m->id_zona,
        'sede'    => $m->sede->nombre ?? 'Sin asignar',
        'zona'    => $m->zona->nombre ?? 'Sin asignar',
        'activo'  => (bool) $m->status,
    ])->values();

    $barriosPayload = $barrios->map(fn ($b) => [
        'id'        => $b->id,
        'barrio'    => $b->barrio,
        'municipio' => optional($b->municipios->first())->nombre ?? 'N/A',
    ])->values();

    $sedesPayload = $sedes->map(fn ($s) => [
        'id' => $s->id, 'nombre' => $s->nombre, 'activo' => (bool) $s->status,
    ])->values();

    $zonasPayload = $zonas->map(fn ($z) => [
        'id' => $z->id, 'nombre' => $z->nombre, 'activo' => (bool) $z->status,
    ])->values();

    /* Opciones iniciales de los filtros, con la forma { value, label } que
       espera <x-select-buscador>. */
    $opcMunicipios = $municipios->map(fn ($m) => ['value' => $m->id, 'label' => $m->nombre])->values();
    $opcBarrios    = $barrios->map(fn ($b) => ['value' => $b->id, 'label' => $b->barrio])->values();
    $opcGrupos     = $grupos->map(fn ($g) => ['value' => $g->id, 'label' => $g->grupo])->values();
    $opcSubgrupos  = $subgrupos->map(fn ($s) => ['value' => $s->id, 'label' => $s->subgrupo])->values();
    $opcInspectores = $inspectores->map(fn ($i) => [
        'value' => $i->id, 'label' => "{$i->id}. {$i->apellidos} {$i->nombres}",
    ])->values();
@endphp

@section('content')
    <div x-data="zonificacion({
            municipios: @js($municipiosPayload),
            barrios: @js($barriosPayload),
            sedes: @js($sedesPayload),
            zonas: @js($zonasPayload),
            opciones: {
                municipio:  @js($opcMunicipios),
                barrio:     @js($opcBarrios),
                grupo:      @js($opcGrupos),
                subgrupo:   @js($opcSubgrupos),
                inspector:  @js($opcInspectores),
            },
            puedeGestionar: @js((bool) auth()->user()?->canany(['ver_residente', 'ver_coordinacion_RP'])),
            urls: {
                storeMunicipio:  '{{ route('zonas.storeMunicipio') }}',
                updateMunicipio: '{{ route('zonas.updateMunicipio', ['id' => '__ID__']) }}',
                storeBarrio:     '{{ route('zonas.storeBarrio') }}',
                updateBarrio:    '{{ route('zonas.updateBarrio', ['id' => '__ID__']) }}',
                cambiarEstado:   '{{ route('zonas.changeStatusTable') }}',
                storeSede:       '{{ route('cortes_produccion.storeSede') }}',
                updateSede:      '{{ route('cortes_produccion.updateSede', ['id' => '__ID__']) }}',
                estadoSede:      '{{ route('cortes_produccion.changeStatusSede') }}',
                storeZona:       '{{ route('cortes_produccion.storeZona') }}',
                updateZona:      '{{ route('cortes_produccion.updateZona', ['id' => '__ID__']) }}',
                estadoZona:      '{{ route('cortes_produccion.changeStatusZona') }}',
                buscar:          '{{ route('zonas.buscador') }}',
                selects:         '{{ route('zonas.actualizarSelects') }}',
                asignarBarrio:   '{{ route('zonas.asignarBarrio') }}',
                inspectores:     '{{ route('zonas.responsablesInsp', ['id' => '__ID__']) }}',
            },
         })"
         @abrir-sedes.window="modal = 'sedes'"
         class="space-y-4 2xl:space-y-6">

        @canany(['ver_residente', 'ver_coordinacion_RP'])
            <div class="grid gap-4 2xl:gap-6 md:grid-cols-2">

                {{-- ============================ MUNICIPIOS ======================= --}}
                <section class="tw-card flex flex-col">
                    <div class="tw-card-header">
                        <div class="flex items-center gap-3">
                            <span class="tw-chip chip-blue"><i class="fas fa-city"></i></span>
                            <div>
                                <h2 class="tw-card-title">Municipios</h2>
                                <p class="tw-card-subtitle" x-text="`${municipios.length} registrados`"></p>
                            </div>
                        </div>
                        <button type="button" class="tw-btn-primary tw-btn-sm" @click="abrirMunicipio()">
                            <i class="fas fa-plus"></i> Crear municipio
                        </button>
                    </div>

                    <div class="border-b border-slate-200/80 p-4 dark:border-slate-700/60">
                        <label class="relative block">
                            <span class="sr-only">Buscar municipio</span>
                            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" class="tw-input pl-9" placeholder="Buscar por nombre, sede o zona…"
                                   x-model.debounce.200ms="buscaMunicipio">
                        </label>
                    </div>

                    <div class="max-h-[22rem] flex-1 tw-card-scroll">
                        <table class="tw-table tw-table-fija">
                            <thead>
                                <tr>
                                    <th scope="col">Nombre</th>
                                    <th scope="col">Sede</th>
                                    <th scope="col">Zona</th>
                                    <th scope="col">Estado</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="m in municipiosFiltrados" :key="m.id">
                                    <tr>
                                        <td class="font-medium text-slate-900 dark:text-white" x-text="m.nombre"></td>
                                        <td x-text="m.sede"></td>
                                        <td x-text="m.zona"></td>
                                        <td>
                                            <span class="tw-badge" :class="m.activo ? 'chip-emerald' : 'chip-rose'"
                                                  x-text="m.activo ? 'Activo' : 'Inactivo'"></span>
                                        </td>
                                        <td class="text-right">
                                            <div class="flex justify-end gap-2">
                                                <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                        @click="abrirMunicipio(m)">
                                                    <i class="fas fa-pen"></i> Editar
                                                </button>
                                                <button type="button" class="tw-btn-sm"
                                                        :class="m.activo ? 'tw-btn-danger' : 'tw-btn-primary'"
                                                        @click="cambiarEstado(m, 'tbl_localidades_municipios')"
                                                        x-text="m.activo ? 'Desactivar' : 'Activar'"></button>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="!municipiosFiltrados.length">
                                    <td colspan="5" class="px-4 py-10 text-center text-slate-500">
                                        Nada encontrado — lo siento.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                {{-- ============================== BARRIOS ======================== --}}
                <section class="tw-card flex flex-col">
                    <div class="tw-card-header">
                        <div class="flex items-center gap-3">
                            <span class="tw-chip chip-violet"><i class="fas fa-map-location-dot"></i></span>
                            <div>
                                <h2 class="tw-card-title">Barrios</h2>
                                <p class="tw-card-subtitle" x-text="`${barrios.length} registrados`"></p>
                            </div>
                        </div>
                        <button type="button" class="tw-btn-primary tw-btn-sm" @click="abrirBarrio()">
                            <i class="fas fa-plus"></i> Crear barrio
                        </button>
                    </div>

                    <div class="border-b border-slate-200/80 p-4 dark:border-slate-700/60">
                        <label class="relative block">
                            <span class="sr-only">Buscar barrio</span>
                            <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                            <input type="search" class="tw-input pl-9" placeholder="Buscar por barrio o municipio…"
                                   x-model.debounce.200ms="buscaBarrio">
                        </label>
                    </div>

                    <div class="max-h-[22rem] flex-1 tw-card-scroll">
                        <table class="tw-table tw-table-fija">
                            <thead>
                                <tr>
                                    <th scope="col">Barrio</th>
                                    <th scope="col">Municipio</th>
                                    <th scope="col" class="text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="b in barriosFiltrados" :key="b.id">
                                    <tr>
                                        <td class="font-medium text-slate-900 dark:text-white" x-text="b.barrio"></td>
                                        <td x-text="b.municipio"></td>
                                        <td class="text-right">
                                            <button type="button" class="tw-btn-secondary tw-btn-sm"
                                                    @click="abrirBarrio(b)">
                                                <i class="fas fa-pen"></i> Editar
                                            </button>
                                        </td>
                                    </tr>
                                </template>

                                <tr x-show="!barriosFiltrados.length">
                                    <td colspan="3" class="px-4 py-10 text-center text-slate-500">
                                        Nada encontrado — lo siento.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        @endcanany

        {{-- ========================= BUSCAR RELACIONES ======================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-diagram-project"></i></span>
                    <div>
                        <h2 class="tw-card-title">Buscar relaciones</h2>
                        <p class="tw-card-subtitle">
                            Cada filtro acota los demás. El barrio se puede asignar cuando está vacío.
                        </p>
                    </div>
                </div>
                <button type="button" class="tw-btn-secondary tw-btn-sm" @click="limpiarFiltros()"
                        x-show="hayFiltro" x-cloak>
                    <i class="fas fa-eraser"></i> Limpiar filtros
                </button>
            </div>

            {{-- Sin overflow-hidden: los desplegables de los filtros se salen de
                 la tarjeta a propósito. --}}
            <div class="p-4 2xl:p-5">
                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
                    <x-select-buscador label="Municipio" model="filtros.municipio"
                                       options="opciones.municipio" placeholder="Todos los municipios"
                                       onChange="actualizarFiltros()" />
                    <x-select-buscador label="Grupo" model="filtros.grupo"
                                       options="opciones.grupo" placeholder="Todos los grupos"
                                       onChange="actualizarFiltros()" />
                    <x-select-buscador label="Sub grupo" model="filtros.subgrupo"
                                       options="opciones.subgrupo" placeholder="Todos los sub grupos"
                                       onChange="actualizarFiltros()" />
                    <x-select-buscador label="Barrio" model="filtros.barrio"
                                       options="opciones.barrio" placeholder="Todos los barrios"
                                       onChange="actualizarFiltros()" />
                    <x-select-buscador label="Inspector" model="filtros.inspector"
                                       options="opciones.inspector" placeholder="Todos los inspectores"
                                       onChange="actualizarFiltros()" />
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-3">
                    <button type="button" class="tw-btn-primary" @click="buscar()" :disabled="buscando">
                        <i class="fas" :class="buscando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                        Buscar
                    </button>
                    <span class="text-sm text-slate-500" x-show="hayResultados" x-cloak
                          x-text="`${filas.length} relaciones`"></span>
                    <span class="text-xs text-slate-500" x-show="actualizando" x-cloak>
                        <i class="fas fa-circle-notch fa-spin"></i> Ajustando filtros…
                    </span>
                </div>

                <p x-show="errorBusqueda" x-cloak
                   class="mt-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800
                          dark:border-red-800/60 dark:bg-red-950/40 dark:text-red-200"
                   x-text="errorBusqueda"></p>
            </div>

            <div class="border-t border-slate-200/80 dark:border-slate-700/60" x-show="hayResultados" x-cloak>
                <div id="tablaRelaciones" class="ht-theme-main ht-compacta"></div>
            </div>

            <p x-show="!hayResultados" x-cloak
               class="border-t border-slate-200/80 px-5 py-10 text-center text-sm text-slate-500
                      dark:border-slate-700/60">
                Elige al menos un filtro y pulsa Buscar.
            </p>
        </section>

        @include('zonas.partials.index_modals')
    </div>
@endsection

@section('js')
    @include('zonas.partials.index-script')
@endsection
