@extends('layouts.tw.app')

@section('title', 'Coordinación de Quejas')

@section('content_header')
    <h1>Coordinación de Quejas</h1>
@endsection

@section('subtitle', 'Seguimiento de PQRS asignadas y control de tiempos de respuesta.')

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="coordinacionPqrs({
            permisoEditar: {{ $permiso_editar ? 'true' : 'false' }},
            urls: {
                importar:          '{{ route('pqrs.coordinacion.ImportOSF') }}',
                actualizar:        '{{ route('pqrs.coordinacion.updateAsignado') }}',
                historico:         '{{ route('pqrs.coordinacion.historico') }}',
                exportarGDW:       '{{ route('pqrs.coordinacion.exportarGDW') }}',
                datosActualizados: '{{ route('pqrs.coordinacion.datosActualizados') }}',
                supervisores:      '{{ route('pqrs.coordinacion.getSupervisores') }}',
                exportarSuper:     '{{ route('pqrs.coordinacion.exportarSupervisores') }}',
                exportarHistorico: '{{ route('pqrs.coordinacion.exportarHistorico') }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- =========================== BARRA DE ACCIONES ======================= --}}
        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-list-check"></i></span>
                    <div>
                        <h2 class="tw-card-title">Quejas en gestión</h2>
                        <p class="tw-card-subtitle">
                            <span x-text="totalFilas"></span> registros ·
                            actualiza solo cada minuto
                            <span class="ml-1 inline-flex items-center gap-1"
                                  :class="refrescando ? 'text-brand-600 dark:text-brand-300' : 'text-slate-400'">
                                <i class="fas fa-rotate text-[0.625rem]" :class="refrescando && 'fa-spin'"></i>
                                <span x-text="ultimaActualizacion"></span>
                            </span>
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="tw-btn-primary tw-btn-sm" @click="abrirCargar()"
                            @if(!$permiso_editar) disabled @endif>
                        <i class="fas fa-cloud-arrow-up"></i> Cargar datos
                    </button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="abrirHistorico()"
                            @if(!$permiso_editar) disabled @endif>
                        <i class="fas fa-clock-rotate-left"></i> Histórico
                    </button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="abrirExportarGDW()"
                            @if(!$permiso_editar) disabled @endif>
                        <i class="fas fa-file-excel"></i> Exportar a GDW
                    </button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm" @click="abrirExportarSupervisor()">
                        <i class="fas fa-user-shield"></i> Exportar supervisores
                    </button>
                </div>
            </div>

            {{-- Leyenda del semáforo: los mismos colores que pinta contratoRenderer
                 sobre la columna CONTRATO. --}}
            <div class="flex flex-wrap items-center gap-x-5 gap-y-2 border-t border-slate-200/80 px-5 py-3
                        dark:border-slate-700/60">
                <span class="tw-eyebrow">Código de color · columna Contrato</span>
                @php
                    $leyenda = [
                        ['#90EE90', 'Accede / No accede'],
                        ['#83b7f1', 'No procedente'],
                        ['#f8f849', 'Vence en 1 o 2 días'],
                        ['#ff9535', 'Vence hoy'],
                        ['#ff8493', 'Vencida'],
                    ];
                @endphp
                @foreach($leyenda as [$color, $texto])
                    <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                        <span class="h-3.5 w-3.5 shrink-0 rounded border border-black/10"
                              style="background-color: {{ $color }}"></span>
                        {{ $texto }}
                    </span>
                @endforeach
                <span class="inline-flex items-center gap-2 text-xs text-slate-600 dark:text-slate-300">
                    <span class="font-bold text-[#d32f2f] dark:text-[#f87171]">123456</span> Contrato repetido
                </span>
            </div>
        </section>

        {{-- ================================ TABLA ============================== --}}
        <section class="tw-card overflow-hidden">
            {{-- Rejilla: sólo de `lg` para arriba. La construye el JS únicamente
                 cuando se ve, porque Handsontable mide el contenedor al nacer y
                 con display:none saldría con ancho cero. --}}
            <div class="hidden border-b border-slate-200/80 lg:block dark:border-slate-700/60">
                <div id="tabla" class="ht-theme-main ht-compacta" style="position: relative;"></div>
            </div>

            {{-- ===================== VARIANTE MÓVIL ====================== --}}
            {{-- Una ficha por queja. La rejilla tiene 38 columnas y en un teléfono
                 no hay forma de llegar a las editables, así que aquí se sacan como
                 controles nativos. Los campos son los mismos que deja tocar
                 `camposPermitidos()`, que es también de donde salen los de la
                 rejilla: una sola definición para las dos vistas. --}}
            <div class="border-b border-slate-200/80 lg:hidden dark:border-slate-700/60">
                <div x-show="fichas.length === 0" x-cloak
                     class="px-4 py-8 text-center text-sm text-slate-500 dark:text-slate-400">
                    No hay quejas en gestión.
                </div>

                <div class="divide-y divide-slate-200/80 dark:divide-slate-700/60">
                    <template x-for="{ fila, indice } in fichas" :key="fila[0]">
                        <article data-ficha-pqrs>
                            <button type="button" class="flex w-full items-start gap-3 px-4 py-3 text-left"
                                    :aria-expanded="!!desplegadas[fila[0]]"
                                    @click="alternarFicha(fila[0])">
                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="rounded-md px-2 py-0.5 text-sm font-semibold"
                                              :style="estiloContrato(fila)"
                                              x-text="valorDe(fila, 'CONTRATO')"></span>
                                        <span class="text-xs text-slate-500 dark:text-slate-400"
                                              x-text="'Orden ' + fila[0]"></span>
                                    </div>
                                    <p class="mt-1 truncate text-sm text-slate-700 dark:text-slate-200"
                                       x-text="valorDe(fila, 'NOMBRE')"></p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400"
                                       x-text="valorDe(fila, 'DIRECCIÓN')"></p>
                                    <p x-show="contratoRepetido(fila)" x-cloak
                                       class="mt-1 text-xs font-semibold text-[#d32f2f] dark:text-[#f87171]">
                                        <i class="fas fa-triangle-exclamation"></i> Contrato repetido
                                    </p>
                                </div>

                                <div class="flex flex-col items-end gap-1">
                                    <span class="whitespace-nowrap text-xs font-semibold text-slate-600
                                                 dark:text-slate-300"
                                          x-text="valorDe(fila, 'DÍAS RESTANTES') + ' días'"></span>
                                    <i class="fas fa-chevron-down text-xs text-slate-400 transition-transform"
                                       :class="desplegadas[fila[0]] && 'rotate-180'"></i>
                                </div>
                            </button>

                            {{-- `x-if` y no `x-show`: dos de los desplegables son la
                                 lista de inspectores, 155 opciones cada uno. Con
                                 `x-show` el DOM se crea igual aunque la ficha esté
                                 cerrada, y con 24 quejas en pantalla eso son más de
                                 7.000 <option> que el teléfono construye para nada.
                                 Así sólo existe la ficha que está abierta. --}}
                            <template x-if="desplegadas[fila[0]]">
                                <div class="space-y-4 px-4 pb-4">
                                    {{-- Campos editables, con el control que toca a cada tipo. --}}
                                    <div class="space-y-3">
                                        <template x-for="campo in camposPermitidos()" :key="campo.header">
                                            <label class="block">
                                                <span class="tw-eyebrow" x-text="campo.header"></span>

                                                <select x-show="campo.tipo === 'lista'"
                                                        class="tw-select mt-1 w-full"
                                                        :value="valorDe(fila, campo.header)"
                                                        @change="editarCampo(indice, campo, $event)">
                                                    <template x-for="o in campo.opciones" :key="o">
                                                        <option :value="o"
                                                                :selected="o === valorDe(fila, campo.header)"
                                                                x-text="o || '— sin definir —'"></option>
                                                    </template>
                                                </select>

                                                <input x-show="campo.tipo === 'fecha'" type="date"
                                                       class="tw-input mt-1 w-full"
                                                       :value="valorDe(fila, campo.header)"
                                                       @change="editarCampo(indice, campo, $event)">

                                                <input x-show="campo.tipo === 'numero'" type="number"
                                                       inputmode="numeric"
                                                       class="tw-input mt-1 w-full"
                                                       :value="valorDe(fila, campo.header)"
                                                       @change="editarCampo(indice, campo, $event)">

                                                <textarea x-show="campo.tipo === 'texto'" rows="2"
                                                          class="tw-input mt-1 w-full"
                                                          :value="valorDe(fila, campo.header)"
                                                          @change="editarCampo(indice, campo, $event)"></textarea>
                                            </label>
                                        </template>
                                    </div>

                                    {{-- El resto de la queja, de sólo lectura. --}}
                                    <dl class="divide-y divide-slate-200/60 rounded-xl border border-slate-200/80
                                               dark:divide-slate-700/40 dark:border-slate-700/60">
                                        <template x-for="[etiqueta, valor] in fichaDatos(fila)" :key="etiqueta">
                                            <div class="flex gap-3 px-3 py-2">
                                                <dt class="w-32 shrink-0 text-xs text-slate-500 dark:text-slate-400"
                                                    x-text="etiqueta"></dt>
                                                <dd class="min-w-0 flex-1 break-words text-xs text-slate-700
                                                           dark:text-slate-200"
                                                    x-text="valor"></dd>
                                            </div>
                                        </template>
                                    </dl>
                                </div>
                            </template>
                        </article>
                    </template>
                </div>
            </div>

            <p class="tw-hint px-5 py-3">
                <i class="fas fa-circle-info"></i>
                @if($permiso_editar)
                    <span class="hidden lg:inline">
                        Las columnas editables se guardan al salir de la celda; las fechas asociadas las calcula el servidor.
                    </span>
                    <span class="lg:hidden">
                        Cada campo se guarda al salir de él; las fechas asociadas las calcula el servidor.
                    </span>
                @else
                    Solo puedes editar <strong>Observación supervisor</strong>.
                @endif
            </p>
        </section>

        @include('pqrs.partials.coordinacion-modales')
    </div>
@endsection

@section('js')
    <script>
        /* Contrato con el servidor: este orden de columnas debe coincidir
           exactamente con el mapeo de getDatosActualizados en el componente. */
        const permisoEditar = @json($permiso_editar);
        const dataFromPHP = @json($completeData);
        const listaInspectores = @json($listaInspectoresArray);

        const colHeaders = [
            'NÚMERO ORDEN', 'CONTRATO', 'CÉDULA', 'NOMBRE', 'DEPARTAMENTO',
            'LOCALIDAD', 'BARRIO', 'DIRECCIÓN', 'CATEGORÍA',
            'COD UNIDAD OPERATIVA', 'TIPO TRABAJO', 'FECHA ASIGNACIÓN',
            'OBSERVACIÓN SOLICITUD', 'FECHA CIERRE ÚLTIMA', 'OBSERVACIÓN CIERRE ÚLTIMA',
            'TIPO TRABAJO CIERRE ÚLTIMA', 'CAUSAL CIERRE ÚLTIMA', 'FECHA ASIGNACIÓN ÚLTIMA',
            'OBSERVACIÓN ASIGNACIÓN ÚLTIMA', 'GESTIÓN ASIGNACIÓN ÚLTIMA', 'TIPO TRABAJO ASIGNACIÓN ÚLTIMA',
            'MOTIVO DE PQR', 'RESPONSABLE', 'ASIGNADO', 'SUPERVISOR', 'FECHA ASIGNADO',
            'TÉCNICO PROXIMA PROGRAMACION', 'FECHA AGENDAMIENTO',
            'INSTRUCCIONES CAMPO', 'OBSERVACION SUPERVISOR', 'RECEPCIÓN',
            'FECHA RECEPCIÓN', 'FECHA SOLICITUD CIERRE', 'OBSERVACIÓN GESTIÓN',
            'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA', 'FECHA LÍMITE', 'DÍAS RESTANTES',
        ];
    </script>
    @include('pqrs.partials.coordinacion-script')
@endsection
