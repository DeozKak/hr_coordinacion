@extends('layouts.tw.app')

@section('title', 'Actividad HTTP')

@section('content_header')
    <h1>Actividad HTTP</h1>
@endsection

@section('subtitle', $user->name . ' · ' . $user->email)

@section('actions')
    <a href="{{ route('admin.user.activity.show', $user) }}" class="tw-btn-secondary">
        <i class="fas fa-database"></i> Ver actividad en base de datos
    </a>
    <a href="{{ route('admin.users.activity.list') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Todos los usuarios
    </a>
@endsection

@section('content')
    <div x-data="actividadHttp({
            registros: {{ Js::from($activities->map(fn ($a) => [
                'id' => $a->id,
                'log' => $a->log_name,
                'descripcion' => $a->description,
                'propiedades' => ($a->properties && $a->properties->isNotEmpty()) ? $a->properties : null,
                'fecha' => $a->created_at->format('d/m/Y H:i:s'),
            ])->values()) }},
         })"
         class="space-y-6">

        {{-- Filtros por GET, igual que la vista de base de datos. --}}
        <section class="tw-card p-5">
            <form method="GET" action="{{ route('admin.user.http_activity.show', $user) }}"
                  class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                <div>
                    <label class="tw-label" for="log_name_filter">Tipo de registro</label>
                    <select name="log_name_filter" id="log_name_filter" class="tw-select">
                        <option value="">Todos</option>
                        @foreach ($available_log_names as $log)
                            <option value="{{ $log }}" @selected(request('log_name_filter') == $log)>
                                {{ $log }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="tw-label" for="date_from">Desde</label>
                    <input type="date" name="date_from" id="date_from" class="tw-input"
                           value="{{ request('date_from') }}" max="{{ request('date_to') }}">
                </div>
                <div>
                    <label class="tw-label" for="date_to">Hasta</label>
                    <input type="date" name="date_to" id="date_to" class="tw-input"
                           value="{{ request('date_to') }}" min="{{ request('date_from') }}">
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="tw-btn-primary">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <a href="{{ route('admin.user.http_activity.show', $user) }}" class="tw-btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-sky"><i class="fas fa-globe"></i></span>
                    <div>
                        <h2 class="tw-card-title">Peticiones</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de
                            <span x-text="registros.length"></span>
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-72">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                              -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Buscar en la tabla…"
                           x-model="busqueda" @input="pagina = 1">
                </div>
            </div>

            <div class="overflow-x-auto" x-show="filtrados.length > 0" x-cloak>
                <table class="tw-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tipo</th>
                        <th>Descripción</th>
                        <th>Propiedades</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="r in paginados" :key="r.id">
                        <tr class="align-top">
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="r.id"></td>
                            <td><span class="tw-badge chip-slate" x-text="r.log"></span></td>
                            {{-- El ancho va en un div: en una tabla de layout
                                 automático el navegador ignora max-width sobre la
                                 propia celda y la deja crecer con el contenido. --}}
                            <td>
                                <div class="max-w-sm truncate" :title="r.descripcion"
                                     x-text="r.descripcion"></div>
                            </td>
                            <td>
                                <template x-if="!r.propiedades">
                                    <span class="text-slate-400">N/A</span>
                                </template>
                                <template x-if="r.propiedades">
                                    <div class="flex min-w-0 items-baseline gap-1.5 whitespace-nowrap">
                                        <span class="max-w-sm truncate text-slate-500 dark:text-slate-400"
                                              :title="aTexto(r.propiedades)"
                                              x-text="fragmento(r.propiedades, 150)"></span>
                                        <button type="button"
                                                class="shrink-0 text-xs font-medium text-brand-600
                                                       hover:underline dark:text-brand-300"
                                                @click="abrirJson('Propiedades', r.propiedades)">ver más</button>
                                    </div>
                                </template>
                            </td>
                            <td class="whitespace-nowrap" x-text="r.fecha"></td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            @include('users.activity.partials.paginacion')

            <div x-show="filtrados.length === 0" x-cloak
                 class="border-t border-slate-200/80 px-5 py-16 text-center dark:border-slate-700/60">
                <i class="fas fa-globe mb-3 block text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 dark:text-slate-400"
                   x-text="registros.length === 0
                        ? 'No hay actividad HTTP registrada para este usuario con los filtros actuales.'
                        : 'Ningún registro coincide con la búsqueda.'"></p>
            </div>
        </section>

        @include('users.activity.partials.json-modal')
    </div>
@endsection

@section('js')
    @include('users.activity.partials.visor-json')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('actividadHttp', ({ registros }) => ({
                ...window.visorJson(),
                ...window.paginador(25),
                registros,
                busqueda: '',

                get totalPaginas() { return this.totalPaginasDe(this.filtrados); },
                get paginaActual() { return this.paginaValidaDe(this.filtrados); },
                get paginados() { return this.recortar(this.filtrados); },

                get filtrados() {
                    const q = this.busqueda.trim().toLowerCase();
                    if (q === '') return this.registros;
                    return this.registros.filter(r =>
                        String(r.id).includes(q) ||
                        String(r.log).toLowerCase().includes(q) ||
                        String(r.descripcion).toLowerCase().includes(q) ||
                        String(r.fecha).includes(q) ||
                        this.aTexto(r.propiedades).toLowerCase().includes(q));
                },
            }));
        });
    </script>
@endsection
