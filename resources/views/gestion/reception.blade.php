@extends('layouts.tw.app')

@section('title', 'Recepción')

@section('content_header')
    <h1>Recepción</h1>
@endsection

@section('subtitle', 'Consulta de las órdenes recibidas.')

@include('layouts.tw.partials.handsontable')

@php
    $opcInspectores = $inspectors->map(fn ($i) => [
        'value' => $i->id, 'label' => "{$i->id}. {$i->apellidos} {$i->nombres}",
    ])->values();
@endphp

@section('content')
    <div x-data="recepcion({
            urls: {
                todo:    '{{ route('management.reception') }}',
                filtrar: '{{ route('management.filterDataReception') }}',
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
                            Los campos de número admiten varios valores; pega una lista y se reparte sola.
                        </p>
                    </div>
                </div>
                <i class="fas fa-chevron-down text-sm text-slate-400 transition"
                   :class="filtrosAbiertos && 'rotate-180'"></i>
            </button>

            {{-- Sin x-collapse: aquí no hay ventana de por medio, pero se mantiene
                 la misma forma que en el resto de la aplicación. --}}
            <div x-show="filtrosAbiertos" x-cloak>
                <div class="grid gap-4 p-4 2xl:p-5 sm:grid-cols-2 lg:grid-cols-4">
                    <x-lista-numeros label="Orden principal" model="filtros.ordenTrabajo" />
                    <x-lista-numeros label="Orden secundaria" model="filtros.ordenExterna" />
                    <x-lista-numeros label="Número de solicitud" model="filtros.numeroSolicitud" />
                    <x-lista-numeros label="Contrato" model="filtros.contrato" />

                    <div class="sm:col-span-2">
                        <label class="tw-label" for="direccion">Dirección</label>
                        <input type="text" id="direccion" class="tw-input" x-model="filtros.direccion">
                    </div>

                    <div class="sm:col-span-2">
                        <x-multi-select label="Código del técnico"
                                        :options="$opcInspectores"
                                        model="filtros.ccOperario"
                                        placeholder="Todos los técnicos" />
                    </div>

                    <div>
                        <label class="tw-label" for="tipo">Tipo</label>
                        <select id="tipo" class="tw-select" x-model="filtros.tipo">
                            <option value="">Todos</option>
                            <option value="Existe efe">Existe efe</option>
                            <option value="No existe efe">No existe efe</option>
                        </select>
                    </div>

                    <div>
                        <label class="tw-label" for="estadoRecepcion">Estado de recepción</label>
                        <select id="estadoRecepcion" class="tw-select" x-model="filtros.estadoRecepcion">
                            <option value="">Todos</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>
                    </div>

                    <div>
                        <label class="tw-label" for="created_at">Fecha de recepción</label>
                        <input type="date" id="created_at" class="tw-input" x-model="filtros.created_at">
                    </div>

                    <x-lista-numeros label="Acta" model="filtros.numActa" />
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
                    <span class="tw-chip chip-violet"><i class="fas fa-inbox"></i></span>
                    <div>
                        <h2 class="tw-card-title">Recepción</h2>
                        <p class="tw-card-subtitle" x-text="`${total} registros`"></p>
                    </div>
                </div>

                {{-- Paginación explícita. La versión anterior cargaba páginas al
                     llegar al borde de la rejilla leyendo el interior de
                     Handsontable (view._wt), con un contador de sentido de
                     desplazamiento que se descuadraba y repetía o saltaba
                     páginas. El servidor ya pagina de cien en cien. --}}
                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500" x-text="`Página ${pagina} de ${totalPaginas}`"></span>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina - 1)" :disabled="pagina === 1 || cargando">Anterior</button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina + 1)" :disabled="pagina >= totalPaginas || cargando">Siguiente</button>
                </div>
            </div>

            <div class="relative border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaRecepcion" class="ht-theme-main ht-compacta"></div>

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
    </div>
@endsection

@section('js')
    @include('gestion.partials.reception-script')
@endsection
