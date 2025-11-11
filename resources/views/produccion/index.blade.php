@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
    <h1>Producción Corte: {{$corte?->nombre}}</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{ asset('css/produccion/indexV1.1.css')}}">
    {{-- <script src="{{asset('js/produccionIndex.js')}}"></script> --}}
    <div class="shadow-container">
        <a class="btn-back" href="javascript:history.go(-1)">
            <i class="fas fa-arrow-left"></i> Ir Atrás
        </a>

        <ul class="nav nav-tabs" id="comparisonTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link active" id="graficoPrincipal-tab" data-toggle="tab" href="#graficoPrincipal"
                   role="tab">Gráfico Principal</a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link" id="comparacion-tab" data-toggle="tab" href="#comparacion" role="tab">Comparación
                    de Cortes</a>
            </li>
        </ul>

        <!-- Contenido de las Tabs -->
        <div class="tab-content mt-4">
            <!-- Tab: Gráfico Principal -->
            <div class="tab-pane fade show active" id="graficoPrincipal" role="tabpanel"
                 aria-labelledby="graficoPrincipal-tab">
                <x-adminlte-card title="Total Inspecciones por Operario" theme="light" icon="fas fa-chart-bar"
                                 header-class="text-uppercase" class="dashboard-card">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="filter-group">
                                <!-- Selector de comparación de cortes -->
                                <select id="cortesComparisonSelectStackedbar" class="form-select" multiple
                                        style="display:none;">
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
                                            <option value="{{ $opcionCorte->id }}">Corte: {{ $opcionCorte->nombre }}
                                                - {{$añoInicio}} - {{$añoFin}}</option>
                                        @endif
                                    @endforeach
                                </select>
                                <div class="button-actions-row">

                                    <button class="btn-base btn-animated-gradient btn-primary-animated" id="btnComparar">
                                        <i class="fas fa-chart-pie"></i>
                                        <span>Comparar</span>
                                    </button>


                                    <button class="btn-base btn-animated-gradient btn-secondary-animated" id="btnRestaurar">
                                        <i class="fas fa-sync-alt"></i>
                                        <span>Restaurar Gráfica Principal</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                        @isset($inspectores)
                        <div class="col-md-6">
                            <!-- Selector de inspectores -->
                            <select class="form-select" id="inspectorSelectStackedbar">
                                <option value="">Todos los inspectores</option>

                                @foreach ($inspectores as $inspector)
                                    @if ($inspector->state == 1)
                                        <option
                                            value="{{ $inspector->cedula }}">{{ $inspector->apellidos.' '.$inspector->nombres }}</option>
                                    @endif
                                @endforeach

                            </select>
                        </div>
                        @endisset
                    </div>
                    <canvas id="inspeccionesDiarias"></canvas>
                </x-adminlte-card>
            </div>

            <!-- Tab: Comparación de Cortes -->
            <div class="tab-pane fade" id="comparacion" role="tabpanel" aria-labelledby="comparacion-tab">
                <x-adminlte-card title="Comparación Total de Inspecciones entre Cortes" theme="light"
                                 icon="fas fa-chart-bar" header-class="text-uppercase" class="dashboard-card">
                    <div class="filter-group">
                        <label>Seleccione cortes a comparar:</label>
                        <!-- Nuevo selector para la gráfica de comparación -->
                        <select class="form-select" id="cortesComparisonSelect" multiple>
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
                                    <option value="{{ $opcionCorte->id }}">Corte: {{ $opcionCorte->nombre }}
                                        - {{$añoInicio}} - {{$añoFin}}</option>
                                @endif
                            @endforeach
                        </select>
                        <button class="btn-base btn-animated-gradient btn-primary-animated" id="btnCompararCortes">
                            <i class="fas fa-chart-pie"></i>
                            <span>Comparar</span>
                        </button>
                    </div>
                    <canvas id="comparacionInspecciones"></canvas>
                </x-adminlte-card>
            </div>
        </div>


        {{--  <!-- Sección adicional con categorías de inspección -->
          <div class="row mt-4">
              <div class="col-lg-6 mb-4">
                  <div class="card-body">
                      <x-adminlte-card title="Categorías Inspecciones" theme="light" icon="fas fa-code-branch"
                                       header-class="text-uppercase" class="dashboard-card">
                          <select class="form-select-modern" id="inspectorSelect">
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
              <div class="col-lg-6 mb-4">
                  <div class="card">
                      <div class="card-body">
                          <x-adminlte-card title="Inspecciones hechas por zonas" theme="light"
                                           icon="fas fa-map-marker-alt" header-class="text-uppercase"
                                           class="dashboard-card">
                              <canvas id="zonasInsp"></canvas>
                          </x-adminlte-card>
                      </div>
                  </div>
              </div>
          </div>
      </div>--}}
        @stop
        @if($warning)
            <script>

                document.addEventListener('DOMContentLoaded', function () {
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

                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: "Por favor, ingrese los siguientes municipios en la base de datos:",
                        html: `

                    @foreach ($municipiosNoEncontrados as $municipio)
                        <li>{{ $municipio }}</li>
                    @endforeach

                        `,
                        icon: "warning"
                    });
                });
            </script>
        @endif

        @section('js')
            <script
                src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
            <script
                src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
            <script>
                const corte_id = {{$corte?->id}}
                    window.appData = {
                    meta: @json($corte->meta ?? []),
                    contratosCategoria: {!! json_encode($contratosCategoria ?? []) !!},
                    contratosZonas: {!! json_encode($conteoContratosPorZona ?? []) !!},
                    labels: {!! json_encode($produccionInspector ?? []) !!}
                };
            </script>

            <script src="{{ asset('js/seguimientoProduccionUpdate/verProduccionV1.6.js') }}?v={{ time() }}"></script>

@stop
