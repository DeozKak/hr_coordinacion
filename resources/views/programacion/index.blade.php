@extends('layouts.tw.app')

@section('title', 'Programación')

@section('content_header')
    <h1>Programación</h1>
@endsection

@section('subtitle', 'Tablas de programación generadas y cargues de base.')

@section('actions')
    <a href="{{ route('programacion.create') }}" class="tw-btn-primary">
        <i class="fas fa-plus"></i> Generar nueva tabla
    </a>
@endsection

@section('content')
    @php
        /* El aviso de "tabla en curso" se resuelve con un diálogo de tres
           opciones al final de la vista, así que se retira del flash para que el
           layout no lo pinte además como banner. Esto corre mientras se captura
           la sección, es decir antes de que el layout imprima los flashes. */
        $tablaEnCurso = session('warning') ? ($temp ?? null) : null;
        if ($tablaEnCurso) {
            session()->forget('warning');
        }

        /* El nombre guarda el origen del cargue: "Programación tecnicos …",
           "Programación GDO …", o solo la fecha cuando se generó a mano. */
        $filas = $datos->map(function ($dato) {
            $partes = explode(' ', $dato->nombre);
            $tipo = in_array($partes[1] ?? '', ['tecnicos', 'GDO'], true)
                ? $partes[0] . ' ' . $partes[1]
                : '';

            return [
                'id' => $dato->id,
                'usuario' => $dato->usuario->name ?? '—',
                'tipo' => $tipo,
                'creado' => explode(' ', (string) $dato->created_at)[0],
                'urlVer' => route('programacion.show', $dato->id) . '?action=view',
                'urlEditar' => route('programacion.show', $dato->id) . '?action=edit',
            ];
        })->values();
    @endphp

    <div x-data="programacionIndex({
            filas: {{ Js::from($filas) }},
            urls: {
                base:     '{{ route('programacion.base') }}',
                masivos:  '{{ route('programacion.masivos') }}',
                gdo:      '{{ route('programacion.callCenterGDO') }}',
                buscar:   '{{ route('programacion.buscar_por_contrato') }}',
                verBase:  '{{ route('programacion.show', ['id' => '__id__']) }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== CARGUES ============================= --}}
        @haspermission('ver_programacion')
            <section class="tw-card p-4 2xl:p-5">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div class="min-w-0">
                        <span class="tw-eyebrow">Cargues</span>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                            Sube un archivo de Excel para alimentar la base o registrar programadas.
                        </p>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <button type="button" class="tw-btn-secondary" @click="modal = 'base'">
                            <i class="fas fa-database"></i> Añadir a base
                        </button>
                        <button type="button" class="tw-btn-secondary" @click="modal = 'tecnicos'">
                            <i class="fas fa-helmet-safety"></i> Programadas técnicos
                        </button>
                        <button type="button" class="tw-btn-secondary" @click="modal = 'gdo'">
                            <i class="fas fa-headset"></i> Programadas GDO
                        </button>
                    </div>
                </div>
            </section>
        @endhaspermission

        {{-- =========================== BUSCAR CONTRATO ======================== --}}
        <section class="tw-card p-4 2xl:p-5">
            <label class="tw-label" for="buscadorContrato">Buscar por contrato</label>
            <div class="relative sm:max-w-md">
                <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2
                          text-sm text-slate-400"></i>
                <input type="search" id="buscadorContrato" class="tw-input pl-9"
                       placeholder="Número de contrato…" x-model="contrato" @input="buscarConRetraso()">
                <i class="fas fa-spinner fa-spin absolute right-3 top-1/2 -translate-y-1/2 text-sm text-slate-400"
                   x-show="buscando" x-cloak></i>
            </div>

            <p class="tw-hint" x-show="contrato.trim() !== '' && !buscando && resultados.length === 0" x-cloak>
                Ninguna programación contiene ese contrato.
            </p>

            <ul class="mt-3 divide-y divide-slate-100 overflow-hidden rounded-xl border border-slate-200
                       dark:divide-slate-700/50 dark:border-slate-700"
                x-show="resultados.length > 0" x-cloak>
                <template x-for="r in resultados" :key="r.id">
                    <li>
                        <a :href="urlVer(r.id)"
                           class="flex items-center justify-between gap-4 px-4 py-3 text-sm transition
                                  hover:bg-slate-50 dark:hover:bg-slate-700/40">
                            <span class="min-w-0 truncate font-medium text-slate-700 dark:text-slate-200">
                                <span x-text="r.nombre"></span>
                                <span class="text-slate-400" x-text="'· ' + r.usuario"></span>
                            </span>
                            <span class="tw-badge chip-slate" x-text="'ID ' + r.id"></span>
                        </a>
                    </li>
                </template>
            </ul>
        </section>

        {{-- ============================== LISTADO ============================= --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-table-list"></i></span>
                    <div>
                        <h2 class="tw-card-title">Tablas de programación</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="filtrados.length"></span> de {{ $filas->count() }} tablas
                        </p>
                    </div>
                </div>

                <div class="relative w-full sm:w-72">
                    <i class="fas fa-magnifying-glass pointer-events-none absolute left-3 top-1/2 -translate-y-1/2
                              text-sm text-slate-400"></i>
                    <input type="search" class="tw-input pl-9" placeholder="Filtrar por usuario, tipo o ID…"
                           x-model="busqueda" @input="pagina = 1">
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="tw-table">
                    <thead>
                    <tr>
                        @foreach ([['id', 'ID', 'fa-hashtag'], ['usuario', 'Usuario', 'fa-user'],
                                   ['tipo', 'Tipo de programación', 'fa-tags'], ['creado', 'Creado', 'fa-calendar']] as [$campo, $texto, $icono])
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
                        <th class="text-right"><i class="fas fa-gears"></i> Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    <template x-for="fila in paginados" :key="fila.id">
                        <tr>
                            <td class="font-mono text-xs text-slate-500 dark:text-slate-400" x-text="fila.id"></td>
                            <td class="font-medium text-slate-800 dark:text-slate-100" x-text="fila.usuario"></td>
                            <td>
                                <span class="tw-badge"
                                      :class="fila.tipo ? 'chip-sky' : 'chip-slate'"
                                      x-text="fila.tipo || 'Manual'"></span>
                            </td>
                            <td class="whitespace-nowrap text-slate-600 dark:text-slate-300" x-text="fila.creado"></td>
                            <td class="text-right">
                                <div class="flex justify-end gap-2">
                                    @haspermission('ver_programacion')
                                        <a :href="fila.urlEditar" class="tw-btn-secondary tw-btn-sm">
                                            <i class="fas fa-pen"></i> Editar
                                        </a>
                                    @endhaspermission
                                    <a :href="fila.urlVer" class="tw-btn-primary tw-btn-sm">
                                        <i class="fas fa-eye"></i> Ver
                                    </a>
                                </div>
                            </td>
                        </tr>
                    </template>

                    <tr x-show="filtrados.length === 0" x-cloak>
                        <td colspan="5" class="px-4 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                            <i class="fas fa-inbox mb-2 block text-2xl opacity-40"></i>
                            No hay tablas que coincidan.
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

        @haspermission('ver_programacion')
            @include('programacion.partials.index-modales')
        @endhaspermission
    </div>
@endsection

@section('js')
    @include('programacion.partials.index-script')

    @if ($tablaEnCurso)
        <script>
            /* Aviso de tabla en curso: continuar, empezar de cero (borrándola) o
               cancelar. Antes vivía suelto en la vista con jQuery. */
            (function () {
                const seguir = @js(route('programacion.show', ['id' => $tablaEnCurso->id]) . '?action=edit');
                const borrar = @js(route('programacion.erase', ['id' => $tablaEnCurso->id]));

                const preguntar = async () => {
                    const r = await window.Swal.fire({
                        icon: 'question',
                        title: 'Ya tienes una tabla de programación en curso',
                        text: '¿Deseas continuar con ella?',
                        allowOutsideClick: false,
                        showDenyButton: true,
                        showCancelButton: true,
                        confirmButtonText: 'Sí, continuar',
                        denyButtonText: 'No',
                        cancelButtonText: 'Cancelar',
                    });

                    if (r.isConfirmed) { window.location.href = seguir; return; }
                    if (!r.isDenied) return;

                    const s = await window.Swal.fire({
                        icon: 'warning',
                        title: '¡Se perderán los cambios!',
                        allowOutsideClick: false,
                        showDenyButton: true,
                        confirmButtonText: 'Quiero generar una tabla nueva',
                        denyButtonText: 'Mantener cambios',
                    });

                    if (s.isConfirmed) {
                        try {
                            await window.api(borrar, { method: 'DELETE' });
                        } catch (e) {
                            window.Swal.fire({ icon: 'error', title: 'Error',
                                               text: 'No se pudo descartar la tabla en curso.' });
                        }
                    } else if (s.isDenied) {
                        window.location.href = seguir;
                    }
                };

                /* window.Swal lo define el bundle justo antes de arrancar Alpine.
                   Si este script corre antes (lo normal, es inline y el bundle va
                   diferido) se espera al evento; si corriera después, el evento ya
                   no volvería a dispararse y hay que lanzarlo a mano. */
                if (window.Swal) queueMicrotask(preguntar);
                else document.addEventListener('alpine:init',
                                               () => queueMicrotask(preguntar), { once: true });
            })();
        </script>
    @endif
@endsection
