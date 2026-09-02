@extends('layouts.tw.app')

@section('title', 'Ver programación')

@section('content_header')
    <h1>Ver programación</h1>
@endsection

@section('subtitle', 'Consulta lo agendado por fecha y genera las plantillas de salida.')

@section('actions')
    <a href="{{ route('programacion.index') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Ir al listado
    </a>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="verProgramacion({
            puedeCambiarTecnico: {{ auth()->user()->can('mod_tecnicos') ? 'true' : 'false' }},
            tecnicos: {{ Js::from($tecnicos->map(fn ($t) => $t->id . '. ' . $t->apellidos . ' ' . $t->nombres)->values()) }},
            urls: {
                buscar:      '{{ route('programacion.agendamiento') }}',
                actualizar:  '{{ route('programacion.update', ['id' => '__id__']) }}',
                exportarGdw: '{{ route('programacion.exportar') }}',
                exportarSup: '{{ route('programacion.exportarSup') }}',
                reasignar:   '{{ route('programacion.reAsignar', ['fecha' => '__fecha__']) }}',
                trabajos:    '{{ route('jobs.pnd') }}',
            },
         })"
         class="space-y-6">

        {{-- ============================== BÚSQUEDA ============================ --}}
        <section class="tw-card p-5">
            {{-- Los campos crecen a la izquierda y el grupo de acciones queda
                 anclado a la derecha con ml-auto. Antes esto era una rejilla de
                 tres columnas: al ocultar la fecha final su celda desaparecía
                 del flujo y la casilla y el botón saltaban a la columna libre. --}}
            <div class="flex flex-wrap items-end gap-4">
                <div class="w-full sm:w-52">
                    <label class="tw-label" for="fechaInicio">
                        <span x-text="rango ? 'Desde' : 'Fecha de agendamiento'"></span>
                    </label>
                    <input type="date" id="fechaInicio" class="tw-input" x-model="fechaInicio" required>
                </div>

                {{-- La segunda fecha solo aparece al pedir un rango. --}}
                <div class="w-full sm:w-52" x-show="rango" x-cloak x-transition.opacity>
                    <label class="tw-label" for="fechaFin">Hasta</label>
                    <input type="date" id="fechaFin" class="tw-input" x-model="fechaFin" :min="fechaInicio">
                </div>

                <div class="flex w-full flex-wrap items-center gap-4 sm:ml-auto sm:w-auto">
                    <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300">
                        <input type="checkbox" x-model="rango" @change="if (!rango) fechaFin = ''"
                               class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500
                                      dark:border-slate-600 dark:bg-slate-700">
                        Buscar un rango de fechas
                    </label>

                    <button type="button" class="tw-btn-primary" @click="buscar()"
                            :disabled="buscando || sincronizando">
                        <i class="fas" :class="buscando ? 'fa-spinner fa-spin' : 'fa-magnifying-glass'"></i>
                        Buscar
                    </button>
                </div>
            </div>

            {{-- Sincronización de técnicos en curso: mientras corre, no se busca. --}}
            <div x-show="sincronizando" x-cloak
                 class="mt-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900
                        dark:border-sky-800/60 dark:bg-sky-950/40 dark:text-sky-200">
                <p class="flex items-center gap-2">
                    <i class="fas fa-rotate fa-spin"></i>
                    Sincronizando asignaciones de técnicos
                    (<span class="font-semibold" x-text="porcentaje + '%'"></span>). Por favor, espera…
                </p>
                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-sky-200 dark:bg-sky-900">
                    <div class="h-full rounded-full bg-sky-500 transition-all duration-500"
                         :style="{ width: porcentaje + '%' }"></div>
                </div>
            </div>
        </section>

        {{-- ============================= RESULTADOS =========================== --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-list-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Resultados</h2>
                        <p class="tw-card-subtitle">
                            <span x-show="!hayResultados">Elige una fecha y pulsa Buscar.</span>
                            <span x-show="hayResultados" x-cloak>
                                <span x-text="total"></span>
                                <span x-text="total === 1 ? 'programación' : 'programaciones'"></span>
                                <span x-text="descripcionRango"></span>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="tw-btn-secondary" @click="asignar()"
                            :disabled="ocupado" title="Descarga el archivo de reasignación de la fecha inicial">
                        <i class="fas fa-people-arrows"></i> Asignar programaciones
                    </button>
                    <button type="button" class="tw-btn-secondary" @click="exportar('sup')"
                            :disabled="ocupado || !hayResultados">
                        <i class="fas fa-user-tie"></i> Plantilla supervisores
                    </button>
                    <button type="button" class="tw-btn-primary" @click="exportar('gdw')"
                            :disabled="ocupado || !hayResultados">
                        <i class="fas fa-file-excel"></i> Plantilla GDW
                    </button>
                </div>
            </div>

            <div x-show="hayResultados" x-cloak>
                <x-color-legend :items="[['plantilla', 'Registro de plantilla (sin orden de trabajo)']]" />

                <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                    <div id="buscador" class="ht-theme-main ht-compacta"></div>
                </div>
            </div>

            {{-- Estado vacío --}}
            <div x-show="!hayResultados" x-cloak
                 class="border-t border-slate-200/80 px-5 py-16 text-center dark:border-slate-700/60">
                <i class="fas fa-calendar-day mb-3 block text-3xl text-slate-300 dark:text-slate-600"></i>
                <p class="text-sm text-slate-500 dark:text-slate-400" x-text="mensajeVacio"></p>
            </div>
        </section>

        {{-- ===================== OBSERVACIÓN COMPLETA ======================== --}}
        <x-modal show="modal === 'verMas'" close="modal = null" size="max-w-2xl"
                 icon="fa-circle-info" tint="sky" title="Información completa">
            <div class="px-5 py-5">
                <p class="whitespace-pre-wrap break-words rounded-xl border border-slate-200 bg-slate-50 p-4
                          text-sm leading-relaxed text-slate-700
                          dark:border-slate-700 dark:bg-slate-900/50 dark:text-slate-300"
                   x-text="verMas"></p>
            </div>

            <x-slot:footer>
                <button type="button" class="tw-btn-secondary" @click="modal = null">Cerrar</button>
            </x-slot:footer>
        </x-modal>

        {{-- Velo de exportación --}}
        <div x-show="ocupado" x-cloak
             class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-2xl dark:bg-slate-800">
                <i class="fas fa-spinner fa-spin mb-3 block text-3xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300" x-text="mensajeOcupado"></p>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('programacion.partials.ver-script')
@endsection
