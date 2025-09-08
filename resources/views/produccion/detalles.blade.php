@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="fw-bold text-primary mb-0">
            <i class="fas fa-industry me-2"></i>
            Producción Corte:
            <span class="text-dark">{{$corte?->nombre}}</span>
        </h1>
    </div>

@stop

@section('content')
    <!-- Loader y overlay modernos -->
    <div id="overlay"></div>
    <div id="loader">
        <div class="spinner"></div>
        <div class="loader-text">Cargando información...</div>
    </div>


    <!-- Inputs ocultos -->
    <input type="hidden" id="fecha_inicio" value="{{session('fecha_inicio')}}">
    <input type="hidden" id="id_corte_detalles" value="">
    <input type="hidden" id="id_produccion" value="{{route('produccion.datosDetalles')}}">
    <input type="hidden" name="_token" id="token" value="{{csrf_token()}}">
    <link rel="stylesheet" href="{{ asset('css/produccion/detallesV2.2.css')}}">
    <!-- Contenido principal -->
    <div class="container-xxl my-4">
        <div class="row mb-4">
            <div class="col-12 d-flex flex-wrap align-items-center justify-content-between">
                <div>
                    <a class="btn btn-outline-primary btn-lg shadow-sm me-2" href="javascript:history.go(-1)">
                        <i class="fas fa-arrow-left"></i> Ir Atrás
                    </a>
                    <button type="button" class="btn btn-gradient-success btn-lg shadow-sm" id="exportar">
                        <i class="fas fa-download"></i> Exportar
                    </button>
                </div>
                <div class="d-none d-md-block">
                </div>
            </div>
        </div>

        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-4 bg-light h-100 custom-card-altura">
                    <div class="card-header bg-primary text-white rounded-top-4">
                        <h3 class="mb-0 fw-bold">
                            <i class="fas fa-industry"></i> Detalles de Producción
                        </h3>
                    </div>

                    <div class="overflow-auto"  id="detalles">
                        <!-- Aquí se cargan los detalles dinámicamente -->
                    </div>

                </div>
            </div>
        </div>
    </div>


   @include('produccion.modales.modales')
@stop

@section('css')
    <link rel="stylesheet" href="{{asset('css/produccion/produccionV3.css')}}">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet"/>

@stop

@section('js')
    <script src="{{asset('js/produccion/producciondetallesV10.2.js')}}?v={{ time()}}"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/i18n/es.js"></script>
    <script>
        let permission = 0;
    </script>
    @haspermission('ver_residente')
    <script>
        permission = 1;
    </script>
    @endhaspermission
    <script>
        const urlMunicipios = "{{ route('municipios.json') }}";
        const urlObtenerDetalles = "{{ route('obtener-url-detalles') }}";
        const urlObtenerBitacoras = "{{ route('obtener-url-bitacoras') }}";
        const urlActualizarDetallesDiario = "{{ route('produccion.ActualizarDetallesDiario', ['id' => ':id']) }}";
        const urlDiseñoEspecial = "{{ route('produccion.diseñoEspecial', ['id' => ':id']) }}";
        const urlDesasociar = "{{ route('produccion.eliminarDetallesDiario', ['id' => ':id']) }}";
        const urlCrearSession = "{{ route('produccion.crearSession') }}";
        const urlActualizarDetallesDia = "{{ route('produccion.detallesDiario',['fecha' => ':fecha', 'inspector' => ':inspector']) }}";
        const urlInsertar = "{{ route('produccion.insertarContrato') }}"
        const urlContarDobles = "{{ route('produccion.contarDobles') }}"
        const urlNoContarDobles = "{{ route('produccion.guardarNoDobles') }}"
        const urlGuardarNoDoblesFestivos = "{{ route('produccion.storeNotDoublesHolidays') }}"
        const urlCountDoublesHolidays = "{{ route('produccion.countDoublesHolidays') }}"
        const urlNoContarDoblesSabados = "{{ route('produccion.noContarDoblesSaturday') }}"

        $(document).ready(function() {
            function initializeSelect2() {
                $('#municipio-select').select2({
                    language: "es",
                    ajax: {
                        url: urlMunicipios,
                        dataType: 'json',
                        delay: 250,
                        data: function(params) {
                            return {
                                term: params.term
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data, function(item, key) {
                                    return {
                                        id: key,
                                        text: item
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2
                });
            }

            $('#ventanaEmergente').on('shown.bs.modal', function() {
                initializeSelect2();
            });

            $(window).on('resize', function() {
                if ($('#municipio-select').hasClass("select2-hidden-accessible")) {
                    $('#municipio-select').select2('destroy');
                    initializeSelect2();
                }
            });
        });
    </script>
@stop
