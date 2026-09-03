@extends('layouts.tw.app')

@section('title', 'Producción')

@section('content_header')
    <h1>Producción</h1>
@endsection

@section('subtitle', $corte?->nombre ? 'Corte: '.$corte->nombre : 'Sin corte activo')

@section('actions')
    <button type="button" class="tw-btn-secondary" onclick="history.back()">
        <i class="fas fa-arrow-left"></i> Ir atrás
    </button>
@endsection

@php
    /* Opciones de corte: las mismas que armaba el Blade anterior (todos menos el actual,
       con los años de inicio y fin). */
    $opcionesCortes = collect($cortes)
        ->filter(fn ($c) => $corte !== null && $c->id !== $corte->id)
        ->map(fn ($c) => [
            'value' => (string) $c->id,
            'label' => 'Corte: '.$c->nombre.' - '.explode('-', $c->fecha_inicio)[0]
                       .' - '.explode('-', $c->fecha_fin)[0],
        ])->values();

    $opcionesInspectores = collect($inspectores ?? [])
        ->filter(fn ($i) => $i->state == 1)
        ->map(fn ($i) => [
            'value' => (string) $i->cedula,
            'label' => trim($i->apellidos.' '.$i->nombres),
        ])->values();
@endphp

@section('content')
    <div x-data="verProduccion({
            corteId: {{ $corte?->id ?? 'null' }},
            meta: {{ Js::from($corte->meta ?? 0) }},
            inspectores: {{ Js::from($produccionInspector ?? []) }},
            urls: {
                corteData:  '{{ route('produccion.getCorteData') }}',
                totalData:  '{{ route('produccion.getCorteTotalData') }}',
            },
         })"
         class="space-y-4 2xl:space-y-6">

        {{-- ============================== PESTAÑAS ============================= --}}
        <nav class="flex gap-1 border-b border-slate-200 dark:border-slate-700" role="tablist">
            @foreach ([
                'principal'   => ['Gráfico principal', 'fa-chart-column'],
                'comparacion' => ['Comparación de cortes', 'fa-chart-pie'],
            ] as $clave => [$texto, $icono])
                <button type="button" role="tab" @click="cambiarTab('{{ $clave }}')"
                        class="-mb-px inline-flex items-center gap-2 border-b-[3px] px-4 py-2.5 text-sm font-semibold transition"
                        :class="tab === '{{ $clave }}'
                            ? 'border-brand-600 text-brand-700 dark:border-brand-400 dark:text-brand-300'
                            : 'border-transparent text-slate-500 hover:border-slate-300 hover:text-slate-700
                               dark:text-slate-400 dark:hover:text-slate-200'">
                    <i class="fas {{ $icono }}"></i> {{ $texto }}
                </button>
            @endforeach
        </nav>

        {{-- ========================= TAB: GRÁFICO PRINCIPAL ==================== --}}
        <section class="tw-card" x-show="tab === 'principal'">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-chart-column"></i></span>
                    <div>
                        <h2 class="tw-card-title" x-text="titulo"></h2>
                        <p class="tw-card-subtitle">
                            <span x-text="inspectoresVisibles"></span> inspectores ·
                            <span x-text="totalVisible.toLocaleString('es-CO')"></span> inspecciones
                            <template x-if="meta > 0">
                                <span> · meta <span x-text="Number(meta).toLocaleString('es-CO')"></span></span>
                            </template>
                        </p>
                    </div>
                </div>
            </div>

            <div class="tw-card-body space-y-4 2xl:space-y-5">
                <div class="grid gap-4 md:grid-cols-2">
                    {{-- El tope cambia con la selección de inspectores, igual que antes:
                         1 corte cuando se miran todos, hasta 6 al filtrar por inspector. --}}
                    <div>
                        <x-multi-select label="Cortes a comparar"
                                        :options="$opcionesCortes"
                                        model="cortesSel"
                                        max-expr="topeCortes"
                                        placeholder="Seleccione un corte a comparar" />
                        <p class="tw-hint">
                            <i class="fas fa-circle-info"></i>
                            <span x-show="inspectoresSel.length === 0">
                                Un corte a la vez mientras se muestran todos los inspectores.
                            </span>
                            <span x-show="inspectoresSel.length > 0" x-cloak>
                                Hasta 7 cortes al filtrar por inspector.
                            </span>
                        </p>
                    </div>

                    @if($opcionesInspectores->isNotEmpty())
                        <x-multi-select label="Inspectores"
                                        :options="$opcionesInspectores"
                                        model="inspectoresSel"
                                        :max="7"
                                        placeholder="Todos los inspectores" />
                    @endif
                </div>

                <div class="flex flex-wrap gap-2">
                    <button type="button" class="tw-btn-primary" id="btnComparar"
                            @click="comparar()" :disabled="cargando">
                        <i class="fas" :class="cargando ? 'fa-spinner fa-spin' : 'fa-chart-pie'"></i>
                        Comparar
                    </button>
                    <button type="button" class="tw-btn-secondary" id="btnRestaurar" @click="restaurar()">
                        <i class="fas fa-rotate"></i> Restaurar gráfica principal
                    </button>
                </div>

                <div class="h-[32.5rem]">
                    <canvas id="inspeccionesDiarias" role="img"
                            aria-label="Total de inspecciones por operario"></canvas>
                </div>
            </div>
        </section>

        {{-- ======================= TAB: COMPARACIÓN DE CORTES ================== --}}
        <section class="tw-card" x-show="tab === 'comparacion'" x-cloak>
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-violet"><i class="fas fa-chart-pie"></i></span>
                    <div>
                        <h2 class="tw-card-title">Comparación total de inspecciones entre cortes</h2>
                        <p class="tw-card-subtitle">Corte actual frente a los que selecciones</p>
                    </div>
                </div>
            </div>

            <div class="tw-card-body space-y-4 2xl:space-y-5">
                <div class="grid gap-4 lg:grid-cols-[1fr_auto] lg:items-start">
                    <x-multi-select label="Cortes a comparar"
                                    :options="$opcionesCortes"
                                    model="cortesComp"
                                    :max="7"
                                    placeholder="Seleccione un corte a comparar" />
                    <button type="button" class="tw-btn-primary lg:mt-[26px]" id="btnCompararCortes"
                            @click="compararCortes()" :disabled="cargandoComp">
                        <i class="fas" :class="cargandoComp ? 'fa-spinner fa-spin' : 'fa-chart-pie'"></i>
                        Comparar
                    </button>
                </div>

                <div class="h-[30rem]">
                    <canvas id="comparacionInspecciones" role="img"
                            aria-label="Comparación de inspecciones entre cortes"></canvas>
                </div>
            </div>
        </section>
    </div>
@endsection

@push('libs')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
@endpush

@section('js')
    @include('produccion.partials.index-script')

    @if($warning)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({ title: @js($warning), text: '', icon: 'warning' });
            });
        </script>
    @endif

    @if(isset($municipiosNoEncontrados) && $municipiosNoEncontrados->isNotEmpty())
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                Swal.fire({
                    title: 'Por favor, ingrese los siguientes municipios en la base de datos:',
                    html: @js('<li>'.$municipiosNoEncontrados->implode('</li><li>').'</li>'),
                    icon: 'warning'
                });
            });
        </script>
    @endif
@endsection
