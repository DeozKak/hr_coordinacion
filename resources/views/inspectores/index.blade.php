@extends('layouts.tw.app')

@section('title', 'Inspectores')

@section('content_header')
    <h1>Inspectores</h1>
@endsection

@section('subtitle', 'Alta, edición y estado del personal de inspección.')

@php
    $filas = $inspectores->map(fn ($i) => [
        'id' => $i->id,
        'nombres' => $i->nombres,
        'apellidos' => $i->apellidos,
        'type_id' => $i->type_id,
        'cedula' => $i->cedula,
        'supervisor' => $i->supervisor->name ?? '—',
        'supervisor_id' => $i->SUPERVISOR,
        'aprendiz' => (int) $i->aprendiz,
    ])->values();
@endphp

@section('content')
    <div x-data="gestionInspectores({
            filas: {{ Js::from($filas) }},
            supervisores: {{ Js::from($supervisores->map(fn ($s) => ['id' => $s->id, 'nombre' => $s->name])->values()) }},
            urls: {
                datos:       '{{ route('inspector.getData') }}',
                crear:       '{{ route('inspectores.store') }}',
                actualizar:  '{{ route('inspectores.update') }}',
                desactivados:'{{ route('inspectores.show_disabled') }}',
                estado:      '{{ route('inspectores.change_state', ['inspector' => '__id__']) }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-amber"><i class="fas fa-helmet-safety"></i></span>
                    <div>
                        <h2 class="tw-card-title">Inspectores activos</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de <span x-text="filas.length"></span>
                        </p>
                    </div>
                </div>

                <div class="flex w-full flex-wrap items-center gap-2 sm:w-auto">
                    <div class="relative min-w-0 flex-1 sm:w-64 sm:flex-none">
                        <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                                  -translate-y-1/2 text-sm text-slate-400"></i>
                        <input type="search" class="tw-input pl-9" placeholder="Buscar por nombre, cédula…"
                               x-model="busqueda" @input="pagina = 1">
                    </div>
                    <button type="button" class="tw-btn-secondary" @click="abrirDesactivados()">
                        <i class="fas fa-user-slash"></i> Ver desactivados
                    </button>
                    <button type="button" class="tw-btn-primary" @click="abrirCrear()">
                        <i class="fas fa-plus"></i> Nuevo inspector
                    </button>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                    <tr>
                        @foreach ([['id', 'ID', 'fa-hashtag'], ['nombres', 'Nombres', 'fa-user'],
                                   ['apellidos', 'Apellidos', 'fa-user-tag'], ['cedula', 'Identificación', 'fa-id-card'],
                                   ['supervisor', 'Supervisor', 'fa-user-tie']] as [$campo, $texto, $icono])
                            <th>
                                <button type="button" class="inline-flex items-center gap-1.5 uppercase tracking-[0.06em]"
                                        @click="ordenarPor('{{ $campo }}')">
                                    <i class="fas {{ $icono }}"></i> {{ $texto }}
                                    <i class="fas text-[0.625rem]"
                                       :class="orden === '{{ $campo }}'
                                            ? (direccion === 'asc' ? 'fa-arrow-up-short-wide' : 'fa-arrow-down-wide-short')
                                            : 'fa-sort opacity-40'"></i>
                                </button>
                            </th>
                        @endforeach
                        <th><i class="fas fa-graduation-cap"></i> Aprendiz</th>
                        <th class="text-right"><i class="fas fa-gears"></i> Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="fila in paginados" :key="fila.id">
                        <tr>
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="fila.id"></td>
                            <td class="font-medium text-slate-800 dark:text-slate-100" x-text="fila.nombres"></td>
                            <td x-text="fila.apellidos"></td>
                            <td class="whitespace-nowrap">
                                <span class="text-slate-400" x-text="fila.type_id"></span>
                                <span x-text="fila.cedula"></span>
                            </td>
                            <td x-text="fila.supervisor"></td>
                            <td>
                                <span class="tw-badge" :class="fila.aprendiz ? 'chip-amber' : 'chip-slate'"
                                      x-text="fila.aprendiz ? 'Sí' : 'No'"></span>
                            </td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                                            @click="abrirEditar(fila)">
                                        <i class="fas fa-pen"></i> Editar
                                    </button>
                                    <button type="button" class="tw-btn-danger tw-btn-sm"
                                            @click="cambiarEstado(fila, false)">
                                        <i class="fas fa-user-slash"></i> Desactivar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filtrados.length === 0" x-cloak>
                        <td colspan="7" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-user-slash mb-2 block text-2xl opacity-40"></i>
                            No hay inspectores que coincidan.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>

            <div class="flex items-center justify-between gap-4 border-t border-slate-200/80 px-5 py-3
                        dark:border-slate-700/60"
                 x-show="filtrados.length > porPagina" x-cloak>
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

        @include('inspectores.modales.modales')
    </div>
@endsection

@section('js')
    @include('inspectores.partials.index-script')
@endsection
