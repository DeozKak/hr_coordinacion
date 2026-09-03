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
                    <span class="font-bold text-[#d32f2f]">123456</span> Contrato repetido
                </span>
            </div>
        </section>

        {{-- ================================ TABLA ============================== --}}
        <section class="tw-card overflow-hidden">
            <div class="border-b border-slate-200/80 dark:border-slate-700/60">
                <div id="tabla" class="ht-theme-main ht-compacta" style="position: relative;"></div>
            </div>
            <p class="tw-hint px-5 py-3">
                <i class="fas fa-circle-info"></i>
                @if($permiso_editar)
                    Las columnas editables se guardan al salir de la celda; las fechas asociadas las calcula el servidor.
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
