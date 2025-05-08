@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción Corte {{$corte?->nombre}}</h1>
@endsection

@section('content')

{{-- <script src="{{asset('js/produccionIndex.js')}}"></script> --}}
<div class="card">
    <div class="card-body">
        <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>

        <!-- Navegación de Tabs -->
        <ul class="nav nav-tabs" id="comparisonTabs">
            <li class="nav-item">
                <a class="nav-link active" id="graficoPrincipal-tab" data-toggle="tab" href="#graficoPrincipal" role="tab" aria-controls="graficoPrincipal" aria-selected="true">
                    Gráfico Principal
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link disabled" id="comparacion-tab" data-toggle="tab" href="#comparacion" role="tab" aria-controls="comparacion" aria-selected="false">
                    Comparación de Cortes
                </a>
            </li>
        </ul>

        <!-- Contenido de las Tabs -->
        <div class="tab-content">
            <!-- Tab: Gráfico Principal -->
            <div class="tab-pane fade show active" id="graficoPrincipal" role="tabpanel" aria-labelledby="graficoPrincipal-tab">
                <x-adminlte-card title="Total Inspecciones por Operario" theme="info" icon="fas fa-chart-bar" header-class="text-uppercase rounded-bottom border-info">
                    <div class="row">
                        <div class="col-md-6">
                            <!-- Selector de comparación de cortes -->
                            <select id="cortesComparisonSelectStackedbar" class="form-control" multiple style="display:none;">
                                <option value="">Seleccione un corte a comparar</option>
                                @foreach ($cortes as $opcionCorte)
                                @if($corte === null)
                                    @continue
                                @endif
                                    @if ($opcionCorte->id !== $corte->id)
                                        <?php
                                        $añoInicio = explode('-', $opcionCorte->fecha_inicio)[0];
                                        $añoFin = explode('-', $opcionCorte->fecha_fin)[0];
                                        ?>
                                        <option value="{{ $opcionCorte->id }}">Corte: {{ $opcionCorte->nombre }} - {{$añoInicio}} - {{$añoFin}}</option>
                                    @endif
                                @endforeach
                            </select>
                            @if ($corte !== null)
                            <!-- Selector de cortes -->
                            <select class="form-control" id="cortesSelect" data-corte-actual="{{ $corte->id }}">
                                <option value="{{ $corte->id }}">Corte actual</option>
                                @foreach ($cortes as $opcionCorte)
                                    @if ($opcionCorte->id !== $corte->id)
                                        <?php
                                        $añoInicio = explode('-', $opcionCorte->fecha_inicio)[0];
                                        $añoFin = explode('-', $opcionCorte->fecha_fin)[0];
                                        ?>
                                        <option value="{{ $opcionCorte->id }}">Corte: {{ $opcionCorte->nombre }} - {{ $añoInicio }} - {{ $añoFin }}</option>
                                    @endif
                                @endforeach
                            </select>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <!-- Selector de inspectores -->
                            <select class="form-control" id="inspectorSelectStackedbar">
                                <option value="">Todos los inspectores</option>
                                @foreach ($arrayInspectores as $inspector)
                                    @if ($inspector['status'] == 1)
                                        <option value="{{ $inspector['cedula'] }}">{{ $inspector['apellido'] }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <canvas id="inspeccionesDiarias"></canvas>
                </x-adminlte-card>
            </div>

            <!-- Tab: Comparación de Cortes -->
            <div class="tab-pane fade" id="comparacion" role="tabpanel" aria-labelledby="comparacion-tab">
                <x-adminlte-card title="Comparación Total de Inspecciones entre Cortes" theme="info" icon="fas fa-chart-bar" header-class="text-uppercase rounded-bottom border-info">
                    <!-- Nuevo selector para la gráfica de comparación -->
                    <select id="cortesComparisonSelect" multiple>
                        <option value="">Seleccione un corte a comparar</option>
                        @foreach ($cortes as $opcionCorte)
                            @if($corte === null)
                            @continue
                            @endif

                            @if ($opcionCorte->id !== $corte->id)
                                <?php
                                $añoInicio = explode('-',$opcionCorte->fecha_inicio)[0];
                                $añoFin = explode('-',$opcionCorte->fecha_fin)[0];
                                ?>
                                <option value="{{ $opcionCorte->id }}">Corte: {{ $opcionCorte->nombre }} - {{$añoInicio}} - {{$añoFin}}</option>
                            @endif
                        @endforeach
                    </select>
                    <canvas id="comparacionInspecciones" style="width: 1125px; height: 562px; max-height: 562px;"></canvas>
                </x-adminlte-card>
            </div>
        </div>
    </div>

    <!-- Sección adicional con categorías de inspección -->
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="Categorías Inspecciones" theme="info" icon="fas fa-code-branch" header-class="text-uppercase rounded-bottom border-info">
                        <select class="form-control" id="inspectorSelect" style="width: 50%;">
                            <option value="">Mostrar todos los contratos</option>
                            @foreach ($inpectores as $inspector)
                                @if ($inspector->state == 1)
                                    <option value="{{ $inspector->cedula }}">{{ $inspector->apellidos }}</option>
                                @endif
                            @endforeach
                        </select>
                        <canvas id="categoriaInsp"></canvas>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="Inspecciones hechas por zonas" theme="info" icon="fas fa-map-marker-alt" header-class="text-uppercase rounded-bottom border-info">
                        <canvas id="zonasInsp" style="width: 553px; height: 553px; max-height: 553px;"></canvas>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>
@stop
@if($warning)
<script>

    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "{{$warning}}",
            text: "",
            icon: "warning"
        });
    });
</script>
 {{$warning = null;}}
@endif


@if(isset($municipiosNoEncontrados) && $municipiosNoEncontrados->isNotEmpty())
<script>

    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "Por favor, ingrese los siguientes municipios en la base de datos:",
            html: `

                    @foreach ($municipiosNoEncontrados as $municipio)
                        <li>{{ $municipio }}</li>
                    @endforeach

            `,
            type: "warning"
        });
    });
</script>
@endif

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
    window.appData = {
        meta: @json($corte->meta ?? []),
        contratosCategoria: {!! json_encode($contratosCategoria ?? []) !!},
        contratosZonas: {!! json_encode($conteoContratosPorZona ?? []) !!},
        labels: {!! json_encode($produccionInspector ?? []) !!}
    };
</script>

<script src="{{ asset('js/seguimientoProduccionUpdate/verProduccion.js') }}?v={{ time() }}"></script>

@stop
