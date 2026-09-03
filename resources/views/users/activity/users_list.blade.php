@extends('layouts.tw.app')

@section('title', 'Actividad de usuarios')

@section('content_header')
    <h1>Actividad de usuarios</h1>
@endsection

@section('subtitle', 'Listado de usuarios y buscador global de auditoría.')

@section('content')
    <div x-data="actividadUsuarios({
            usuarios: {{ Js::from($users->map(fn ($u) => [
                'id' => $u->id, 'nombre' => $u->name, 'email' => $u->email,
                'urlBd' => route('admin.user.activity.show', $u),
                'urlHttp' => route('admin.user.http_activity.show', $u),
            ])->values()) }},
            eventos: {{ Js::from($available_events->values()) }},
            modelos: {{ Js::from($available_models->map(fn ($m) => [
                'valor' => $m, 'nombre' => \Illuminate\Support\Str::afterLast($m, '\\'),
            ])->values()) }},
            causantes: {{ Js::from($users_for_filter->map(fn ($n, $id) => ['id' => $id, 'nombre' => $n])->values()) }},
            url: '{{ route('admin.global_audit.fetch') }}',
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== USUARIOS ============================ --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-users"></i></span>
                    <div>
                        <h2 class="tw-card-title">Usuarios</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="usuariosFiltrados.length"></span> de
                            <span x-text="usuarios.length"></span>
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-72">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2
                              -translate-y-1/2 text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Buscar por nombre o email…"
                           x-model="buscarUsuario">
                </div>
            </div>

            <div class="max-h-96 tw-card-scroll">
                <table class="tw-table tw-table-fija">
                    <thead>
                    <tr>
                        <th><i class="fas fa-hashtag"></i> ID</th>
                        <th><i class="fas fa-user"></i> Nombre</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th class="text-right"><i class="fas fa-clock-rotate-left"></i> Actividad</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="u in usuariosFiltrados" :key="u.id">
                        <tr>
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="u.id"></td>
                            <td class="font-medium text-slate-800 dark:text-slate-100" x-text="u.nombre"></td>
                            <td class="text-slate-500 dark:text-slate-400" x-text="u.email"></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    <a :href="u.urlBd" class="tw-btn-secondary tw-btn-sm">
                                        <i class="fas fa-database"></i> Base de datos
                                    </a>
                                    <a :href="u.urlHttp" class="tw-btn-secondary tw-btn-sm">
                                        <i class="fas fa-globe"></i> HTTP
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="usuariosFiltrados.length === 0" x-cloak>
                        <td colspan="4" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            No se encontraron usuarios.
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
        </section>

        {{-- ========================== BUSCADOR AUDITORÍA ====================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-violet"><i class="fas fa-magnifying-glass-chart"></i></span>
                    <div>
                        <h2 class="tw-card-title">Auditoría de base de datos</h2>
                        <p class="tw-card-subtitle">Elige al menos un filtro para buscar.</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-4 border-t border-slate-200/80 px-4 py-4 2xl:px-5 2xl:py-5 dark:border-slate-700/60
                        sm:grid-cols-2 lg:grid-cols-3">
                <div>
                    <label class="tw-label" for="date_from_audit">Desde</label>
                    <input type="date" id="date_from_audit" class="tw-input"
                           x-model="filtros.date_from_audit" :max="filtros.date_to_audit || null">
                </div>
                <div>
                    <label class="tw-label" for="date_to_audit">Hasta</label>
                    <input type="date" id="date_to_audit" class="tw-input"
                           x-model="filtros.date_to_audit" :min="filtros.date_from_audit || null">
                </div>
                <div>
                    <label class="tw-label" for="event_type_audit">Tipo de evento</label>
                    <select id="event_type_audit" class="tw-select" x-model="filtros.event_type_audit">
                        <option value="">Todos</option>
                        <template x-for="e in eventos" :key="e">
                            <option :value="e" x-text="e.charAt(0).toUpperCase() + e.slice(1)"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="tw-label" for="user_id_audit">Usuario causante</label>
                    <select id="user_id_audit" class="tw-select" x-model="filtros.user_id_audit">
                        <option value="">Todos</option>
                        <template x-for="c in causantes" :key="c.id">
                            <option :value="c.id" x-text="c.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="tw-label" for="model_type_audit">Tipo de modelo</label>
                    <select id="model_type_audit" class="tw-select" x-model="filtros.model_type_audit">
                        <option value="">Todos</option>
                        <template x-for="m in modelos" :key="m.valor">
                            <option :value="m.valor" x-text="m.nombre"></option>
                        </template>
                    </select>
                </div>
                <div>
                    <label class="tw-label" for="model_id_audit">ID del registro</label>
                    <input type="text" id="model_id_audit" class="tw-input" inputmode="numeric"
                           placeholder="Solo con un tipo de modelo"
                           :disabled="!filtros.model_type_audit"
                           x-model="filtros.model_id_audit"
                           @input="filtros.model_id_audit = $event.target.value.replace(/[^0-9]/g, '')">
                </div>
            </div>

            <div class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-200/80
                        px-5 py-4 dark:border-slate-700/60">
                <p class="text-sm text-slate-500 dark:text-slate-400" x-text="resumen"></p>
                <div class="flex gap-2">
                    <button type="button" class="tw-btn-secondary" @click="limpiar()">
                        <i class="fas fa-eraser"></i> Limpiar filtros
                    </button>
                    <button type="button" class="tw-btn-primary" @click="buscar()" :disabled="buscando">
                        <i class="fas" :class="buscando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                        Buscar
                    </button>
                </div>
            </div>

            <div x-show="truncado" x-cloak
                 class="flex items-start gap-2 border-t border-amber-200 bg-amber-50 px-5 py-3 text-sm
                        text-amber-900 dark:border-amber-800/60 dark:bg-amber-950/40 dark:text-amber-200">
                <i class="fas fa-triangle-exclamation mt-0.5"></i>
                <span>
                    La búsqueda devolvió <span class="font-semibold" x-text="total"></span> registros.
                    Se muestran los <span class="font-semibold" x-text="limite"></span> más recientes;
                    acota las fechas para ver el resto.
                </span>
            </div>

            <div class="overflow-x-auto border-t border-slate-200/80 dark:border-slate-700/60"
                 x-show="registros.length > 0" x-cloak>
                <table class="tw-table">
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Usuario</th>
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
                            <td class="whitespace-nowrap" x-text="r.user_name"></td>
                            <td>
                                <span class="tw-badge" :class="tinteEvento(r.event)"
                                      x-text="r.event.charAt(0).toUpperCase() + r.event.slice(1)"></span>
                            </td>
                            <td class="whitespace-nowrap" x-text="r.auditable_model"></td>
                            <td class="font-mono text-xs" x-text="r.auditable_id"></td>
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
                            <td class="whitespace-nowrap font-mono text-xs" x-text="r.ip_address"></td>
                            <td class="whitespace-nowrap" x-text="r.created_at_formatted"></td>
                        </tr>
                    </template>
                    </tbody>
                </table>
            </div>

            @include('users.activity.partials.paginacion')

            <div x-show="registros.length === 0" x-cloak
                 class="border-t border-slate-200/80 px-5 py-16 text-center dark:border-slate-700/60">
                <i class="fas fa-clipboard-list mb-3 block text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 dark:text-slate-400" x-text="mensajeVacio"></p>
            </div>
        </section>

        @include('users.activity.partials.json-modal')
    </div>
@endsection

@section('js')
    @include('users.activity.partials.visor-json')

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('actividadUsuarios', ({ usuarios, eventos, modelos, causantes, url }) => ({
                ...window.visorJson(),
                ...window.paginador(25),
                usuarios, eventos, modelos, causantes, url,

                buscarUsuario: '',
                buscando: false,
                buscado: false,
                registros: [],
                total: 0,
                truncado: false,
                limite: 0,

                filtros: {
                    date_from_audit: '', date_to_audit: '', event_type_audit: '',
                    user_id_audit: '', model_type_audit: '', model_id_audit: '',
                },

                /* La barra de paginación trabaja sobre `filtrados`; aquí los
                   resultados ya vienen filtrados por el servidor. */
                get filtrados() { return this.registros; },
                get totalPaginas() { return this.totalPaginasDe(this.filtrados); },
                get paginaActual() { return this.paginaValidaDe(this.filtrados); },
                get paginados() { return this.recortar(this.filtrados); },

                get usuariosFiltrados() {
                    const q = this.buscarUsuario.trim().toLowerCase();
                    if (q === '') return this.usuarios;
                    return this.usuarios.filter(u =>
                        u.nombre.toLowerCase().includes(q) ||
                        u.email.toLowerCase().includes(q) ||
                        String(u.id).includes(q));
                },

                /* El servidor devuelve una lista vacía si no llega ningún filtro;
                   se avisa antes de gastar el viaje. */
                get hayFiltros() {
                    return Object.values(this.filtros).some(v => String(v).trim() !== '');
                },

                get resumen() {
                    if (!this.buscado) return 'Sin búsquedas todavía.';
                    if (this.truncado) {
                        return `${this.total} registros encontrados; se muestran los `
                             + `${this.limite} más recientes.`;
                    }
                    return `${this.registros.length} ${this.registros.length === 1 ? 'registro' : 'registros'}`;
                },

                get mensajeVacio() {
                    if (!this.buscado) return 'Elige al menos un filtro y pulsa Buscar.';
                    return 'La búsqueda no devolvió registros.';
                },

                tinteEvento(evento) {
                    return { created: 'chip-emerald', updated: 'chip-sky',
                             deleted: 'chip-rose', restored: 'chip-amber' }[evento] ?? 'chip-slate';
                },

                limpiar() {
                    for (const clave of Object.keys(this.filtros)) this.filtros[clave] = '';
                    this.registros = [];
                    this.buscado = false;
                    this.truncado = false;
                    this.total = 0;
                },

                async buscar() {
                    if (!this.hayFiltros) {
                        window.Swal.fire({ icon: 'warning', title: 'Sin filtros',
                                           text: 'Elige al menos un filtro antes de buscar.' });
                        return;
                    }

                    this.buscando = true;
                    try {
                        const parametros = new URLSearchParams(
                            Object.entries(this.filtros).filter(([, v]) => String(v).trim() !== ''));
                        const r = await window.api(`${this.url}?${parametros}`);
                        this.registros = r.data ?? [];
                        this.total = r.total ?? this.registros.length;
                        this.truncado = !!r.truncado;
                        this.limite = r.limite ?? 0;
                        this.buscado = true;
                        this.pagina = 1;
                    } catch (e) {
                        this.registros = [];
                        window.Swal.fire({ icon: 'error', title: 'Error',
                                           text: 'No se pudo consultar la auditoría.' });
                    } finally {
                        this.buscando = false;
                    }
                },
            }));
        });
    </script>
@endsection
