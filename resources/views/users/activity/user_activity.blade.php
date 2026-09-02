@extends('layouts.tw.app')

@section('title', 'Actividad en base de datos')

@section('content_header')
    <h1>Actividad en base de datos</h1>
@endsection

@section('subtitle', $user->name . ' · ' . $user->email)

@section('actions')
    <a href="{{ route('admin.user.http_activity.show', $user) }}" class="tw-btn-secondary">
        <i class="fas fa-globe"></i> Ver actividad HTTP
    </a>
    <a href="{{ route('admin.users.activity.list') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Todos los usuarios
    </a>
@endsection

@section('content')
    <div x-data="actividadBd({
            registros: {{ Js::from($activities->map(fn ($a) => [
                'id' => $a->id,
                'event' => $a->event,
                'modelo' => $a->auditable_type ? \Illuminate\Support\Str::afterLast($a->auditable_type, '\\') : 'N/A',
                'modelo_id' => $a->auditable_id ?? 'N/A',
                'old_values' => $a->old_values ?: null,
                'new_values' => $a->new_values ?: null,
                'url' => $a->url ?? 'N/A',
                'ip' => $a->ip_address ?? 'N/A',
                'fecha' => $a->created_at->format('d/m/Y H:i:s'),
            ])->values()) }},
         })"
         class="space-y-6">

        {{-- Filtros: van por GET, así el enlace se puede compartir. --}}
        <section class="tw-card p-5">
            <form method="GET" action="{{ route('admin.user.activity.show', $user) }}"
                  class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[1fr_1fr_1fr_auto] lg:items-end">
                <div>
                    <label class="tw-label" for="event">Evento</label>
                    <select name="event" id="event" class="tw-select">
                        <option value="">Todos</option>
                        @foreach ($available_events as $evento)
                            <option value="{{ $evento }}" @selected(request('event') == $evento)>
                                {{ ucfirst($evento) }}
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
                    <a href="{{ route('admin.user.activity.show', $user) }}" class="tw-btn-secondary">
                        Limpiar
                    </a>
                </div>
            </form>
        </section>

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-database"></i></span>
                    <div>
                        <h2 class="tw-card-title">Registros</h2>
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
                        <th>Evento</th>
                        <th>Modelo</th>
                        <th>ID modelo</th>
                        <th>Valores antiguos</th>
                        <th>Valores nuevos</th>
                        <th>URL</th>
                        <th>IP</th>
                        <th>Fecha</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="r in paginados" :key="r.id">
                        <tr class="align-top">
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="r.id"></td>
                            <td>
                                <span class="tw-badge" :class="tinteEvento(r.event)"
                                      x-text="r.event.charAt(0).toUpperCase() + r.event.slice(1)"></span>
                            </td>
                            <td class="whitespace-nowrap" x-text="r.modelo"></td>
                            <td class="font-mono text-xs" x-text="r.modelo_id"></td>
                            <td>@include('users.activity.partials.celda-valores',
                                    ['expr' => 'r.old_values', 'titulo' => 'Valores antiguos'])</td>
                            <td>@include('users.activity.partials.celda-valores',
                                    ['expr' => 'r.new_values', 'titulo' => 'Valores nuevos'])</td>
                            {{-- El ancho va en un div: max-width sobre un <td> lo
                                 ignora el navegador en tablas de layout automático. --}}
                            <td>
                                <div class="max-w-xs truncate text-slate-500 dark:text-slate-400"
                                     :title="r.url" x-text="r.url"></div>
                            </td>
                            <td class="whitespace-nowrap font-mono text-xs" x-text="r.ip"></td>
                            <td class="whitespace-nowrap" x-text="r.fecha"></td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            @include('users.activity.partials.paginacion')

            <div x-show="filtrados.length === 0" x-cloak
                 class="border-t border-slate-200/80 px-5 py-16 text-center dark:border-slate-700/60">
                <i class="fas fa-clipboard-list mb-3 block text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 dark:text-slate-400"
                   x-text="registros.length === 0
                        ? 'No hay actividad registrada para este usuario con los filtros actuales.'
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
            Alpine.data('actividadBd', ({ registros }) => ({
                ...window.visorJson(),
                ...window.paginador(25),
                registros,
                busqueda: '',

                get filtrados() {
                    const q = this.busqueda.trim().toLowerCase();
                    if (q === '') return this.registros;
                    return this.registros.filter(r =>
                        String(r.id).includes(q) ||
                        r.event.toLowerCase().includes(q) ||
                        String(r.modelo).toLowerCase().includes(q) ||
                        String(r.modelo_id).includes(q) ||
                        String(r.url).toLowerCase().includes(q) ||
                        String(r.ip).includes(q) ||
                        String(r.fecha).includes(q) ||
                        this.aTexto(r.old_values).toLowerCase().includes(q) ||
                        this.aTexto(r.new_values).toLowerCase().includes(q));
                },

                get totalPaginas() { return this.totalPaginasDe(this.filtrados); },
                get paginaActual() { return this.paginaValidaDe(this.filtrados); },
                get paginados() { return this.recortar(this.filtrados); },

                tinteEvento(evento) {
                    return { created: 'chip-emerald', updated: 'chip-sky',
                             deleted: 'chip-rose', restored: 'chip-amber' }[evento] ?? 'chip-slate';
                },
            }));
        });
    </script>
@endsection
