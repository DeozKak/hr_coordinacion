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
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== BÚSQUEDA ============================ --}}
        <section class="tw-card p-4 2xl:p-5">
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

        {{-- ========================= CARGA POR TÉCNICO ========================= --}}
        {{-- Sale de las mismas filas que la tabla (el servidor la calcula en
             AgendamientoService), así que listado y tabla nunca cuentan distinto.
             Límite: 7 por jornada; las de «todo el día» rellenan el hueco y sólo
             alertan cuando no caben ni repartiéndolas (más de 14 en el día). --}}
        <section class="tw-card" x-show="hayResultados" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip" :class="conAlerta > 0 ? 'chip-rose' : 'chip-emerald'">
                        <i class="fas" :class="conAlerta > 0 ? 'fa-triangle-exclamation' : 'fa-user-check'"></i>
                    </span>
                    <div>
                        <h2 class="tw-card-title">Carga por técnico</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="tecnicosConCarga"></span>
                            <span x-text="tecnicosConCarga === 1 ? 'técnico' : 'técnicos'"></span> ·
                            <span x-show="conAlerta === 0">ninguno pasa del límite de 7 por jornada</span>
                            <span x-show="conAlerta > 0" class="font-semibold text-rose-600 dark:text-rose-400">
                                <span x-text="conAlerta"></span> con sobrecarga
                            </span>
                        </p>
                    </div>
                </div>

                <label class="flex items-center gap-2.5 text-sm text-slate-700 dark:text-slate-300"
                       x-show="conAlerta > 0">
                    <input type="checkbox" x-model="soloAlertas"
                           class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500
                                  dark:border-slate-600 dark:bg-slate-700">
                    Sólo con sobrecarga
                </label>
            </div>

            <ol class="max-h-[26rem] divide-y divide-slate-200/80 overflow-y-auto border-t border-slate-200/80
                       dark:divide-slate-700/60 dark:border-slate-700/60">
                <template x-for="(t, i) in cargaVisible" :key="t.tecnico ?? '__sin_tecnico__'">
                    <li class="flex flex-col gap-2 px-5 py-3 sm:flex-row sm:items-start sm:gap-4"
                        :class="t.alertas.length && 'border-l-4 border-l-rose-500 bg-rose-50/60 dark:bg-rose-950/20'">
                        <div class="flex min-w-0 flex-1 items-start gap-3">
                            <span class="mt-0.5 w-7 shrink-0 text-right text-xs font-semibold tabular-nums
                                         text-slate-400 dark:text-slate-500"
                                  x-text="t.tecnico && !t.esComodin ? (i + 1) + '.' : '—'"></span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-center gap-2 truncate text-sm font-semibold"
                                   :class="t.tecnico && !t.esComodin
                                           ? 'text-slate-800 dark:text-slate-100' : 'italic text-slate-500'">
                                    <span x-text="t.tecnico ?? 'Sin técnico asignado'"></span>
                                    {{-- El comodín aparta programaciones de un técnico real, así que
                                         no es carga de nadie: ni alerta ni entra en el orden. --}}
                                    <span x-show="t.esComodin" class="tw-badge chip-slate not-italic">
                                        no se ejecuta
                                    </span>
                                </p>

                                {{-- Las alertas, una por línea; en un rango llevan el día. --}}
                                <template x-for="a in t.alertas" :key="a.fecha + a.tipo">
                                    <p class="mt-1 flex items-center gap-1.5 text-xs font-medium
                                              text-rose-700 dark:text-rose-300">
                                        <i class="fas fa-triangle-exclamation"></i>
                                        <span x-show="rango" x-text="fechaCorta(a.fecha) + ' ·'"></span>
                                        <span x-text="a.mensaje"></span>
                                    </p>
                                </template>
                            </div>
                        </div>

                        <div class="flex flex-wrap items-center gap-1.5 pl-10 sm:pl-0">
                            <span class="tw-badge chip-sky" title="Mañana" x-show="t.manana">
                                <i class="fas fa-sun"></i> <span x-text="t.manana"></span>
                            </span>
                            <span class="tw-badge chip-violet" title="Tarde" x-show="t.tarde">
                                <i class="fas fa-cloud-sun"></i> <span x-text="t.tarde"></span>
                            </span>
                            <span class="tw-badge chip-amber" title="Todo el día" x-show="t.todoElDia">
                                <i class="fas fa-clock"></i> <span x-text="t.todoElDia"></span>
                            </span>
                            <span class="tw-badge chip-slate" title="Sin jornada" x-show="t.sinJornada">
                                <i class="fas fa-question"></i> <span x-text="t.sinJornada"></span>
                            </span>
                            <span class="tw-badge min-w-[3.5rem] justify-center"
                                  :class="t.alertas.length ? 'chip-rose' : 'chip-slate'"
                                  title="Total de programaciones">
                                <span class="tabular-nums" x-text="t.total"></span>
                            </span>
                        </div>
                    </li>
                </template>
            </ol>

            <p class="tw-hint border-t border-slate-200/80 px-5 py-3 dark:border-slate-700/60">
                <i class="fas fa-sun"></i> mañana ·
                <i class="fas fa-cloud-sun"></i> tarde ·
                <i class="fas fa-clock"></i> todo el día, que ocupa el hueco libre ·
                límite de 7 por jornada y 14 en el día
            </p>
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
            <div class="px-4 py-4 2xl:px-5 2xl:py-5">
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
