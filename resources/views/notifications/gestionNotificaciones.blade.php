@extends('layouts.tw.app')

@section('title', 'Gestión de Notificaciones')

@section('content_header')
    <h1>Gestión de Notificaciones</h1>
@endsection

@section('subtitle', 'Quién recibe por correo cada aviso del sistema.')

@php
    /* Payload explícito para Alpine: la vista sólo necesita estos campos y no
       el modelo completo de cada usuario. */
    $usuarios = $users->map(fn ($u) => [
        'id'             => $u->id,
        'name'           => $u->name,
        'email'          => $u->email,
        'roles'          => $u->roles->pluck('name')->values(),
        'notificaciones' => $u->notificationsMail->pluck('Nombre')->values(),
    ])->values();
@endphp

@section('content')
    <div x-data="gestionNotificaciones({
            usuarios: @js($usuarios),
            urls: {
                cargar: '{{ route('admin.notifications.getUserNotifications') }}',
                guardar: '{{ route('admin.notifications.update') }}',
                crear:   '{{ route('admin.notifications.store') }}',
            },
         })"
         class="tw-card">

        {{-- ============================ HERRAMIENTAS ========================= --}}
        <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-200 p-4
                    dark:border-slate-700">
            <label class="relative w-full sm:max-w-xs">
                <span class="sr-only">Buscar usuarios</span>
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="search" x-model.debounce.200ms="busqueda"
                       placeholder="Buscar por nombre, correo, rol o notificación…"
                       class="tw-input pl-9">
            </label>

            <div class="flex flex-wrap items-center gap-3 text-sm text-slate-500">
                <span x-text="`${filtrados.length} de ${usuarios.length} usuarios`"></span>
                <select x-model.number="porPagina" class="tw-select w-auto py-1.5" aria-label="Filas por página">
                    <template x-for="n in [10, 25, 50, 100]" :key="n">
                        <option :value="n" x-text="`${n} / página`"></option>
                    </template>
                </select>
                <button type="button" class="tw-btn-primary" @click="abrirCrear()">
                    <i class="fas fa-plus"></i> Nueva notificación
                </button>
            </div>
        </div>

        {{-- =============================== TABLA ============================= --}}
        <div class="overflow-x-auto">
            <table class="tw-table">
                <thead>
                    <tr>
                        <th scope="col">
                            <button type="button" @click="ordenarPor('name')"
                                    class="inline-flex items-center gap-1 hover:text-slate-800 dark:hover:text-slate-200">
                                Usuario
                                <i class="fas text-[10px]"
                                   :class="orden.campo === 'name'
                                       ? (orden.dir === 'asc' ? 'fa-arrow-up' : 'fa-arrow-down')
                                       : 'fa-sort opacity-30'"></i>
                            </button>
                        </th>
                        <th scope="col">Roles</th>
                        <th scope="col">Notificaciones</th>
                        <th scope="col" class="text-right">Acciones</th>
                    </tr>
                </thead>

                <tbody>
                    <template x-for="usuario in pagina" :key="usuario.id">
                        <tr>
                            <td>
                                <div class="font-medium text-slate-900 dark:text-white" x-text="usuario.name"></div>
                                <div class="text-xs text-slate-500" x-text="usuario.email"></div>
                            </td>

                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="rol in usuario.roles" :key="rol">
                                        <span class="tw-badge chip-blue" x-text="rol"></span>
                                    </template>
                                    <span x-show="!usuario.roles.length" class="text-xs text-slate-400">—</span>
                                </div>
                            </td>

                            <td>
                                <div class="flex flex-wrap gap-1">
                                    <template x-for="nombre in usuario.notificaciones" :key="nombre">
                                        <span class="tw-badge chip-emerald" x-text="nombre"></span>
                                    </template>
                                    <span x-show="!usuario.notificaciones.length" class="text-xs text-slate-400">
                                        Ninguna
                                    </span>
                                </div>
                            </td>

                            <td class="text-right">
                                <button type="button" class="tw-btn-secondary tw-btn-sm" @click="abrirEditar(usuario)">
                                    <i class="fas fa-pen"></i> Editar
                                </button>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="!filtrados.length">
                        <td colspan="4" class="px-4 py-10 text-center text-slate-500">
                            Nada encontrado — lo siento.
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- ============================ PAGINACIÓN ========================== --}}
        <div class="flex items-center justify-between gap-3 border-t border-slate-200 p-4 text-sm
                    dark:border-slate-700"
             x-show="totalPaginas > 1" x-cloak>
            <span class="text-slate-500" x-text="`Página ${paginaActual} de ${totalPaginas}`"></span>
            <div class="flex gap-2">
                <button type="button" class="tw-btn-secondary tw-btn-sm"
                        @click="paginaActual--" :disabled="paginaActual === 1">Anterior</button>
                <button type="button" class="tw-btn-secondary tw-btn-sm"
                        @click="paginaActual++" :disabled="paginaActual === totalPaginas">Siguiente</button>
            </div>
        </div>

        @include('notifications.partials.notificaciones-modales')
    </div>
@endsection

@section('js')
    @include('notifications.partials.notificaciones-script')
@endsection
