@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción</h1>
@endsection
@section('content')

<input type="hidden" id="fecha_inicio" value="{{session('fecha_inicio')}}">
<link rel="stylesheet" href="{{asset('css/produccion/produccion.css')}}">
<script src="{{asset('js/producciondetallesV3-7.js')}}"></script>
<!-- <script src="{{asset('js/zonasV2.js')}}"></script> -->

<input type="hidden" id="id_corte_detalles" value="">
<input type="hidden" id="id_produccion" value="{{route('produccion.datosDetalles')}}">
<input type="hidden" name="_token" id="token" value="{{csrf_token()}}">

<div class="shadow-container">


    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>
    <button type="button" class="btn btn-success" id="exportar" style="margin-bottom: 10px;">Exportar</button>
    <x-adminlte-card title="Producción por dia" theme="info" icon="fas fa-code-branch" header-class="text-uppercase rounded-bottom border-info" collapsible>
        <div id="detalles" style="width: '100px'"></div>
    </x-adminlte-card>
    <div id="zonas">
        <div id="loader"></div>
        <div id="overlay"></div>
    </div>

</div>

<!-- Modal Detalles de dia -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo">Inspecciones </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="margin-bottom: 10px;">
                <div id="mensajeNoDatos" style="display: none;" class="alert alert-warning">No hay datos</div>

                <div id="contratos_dia" style=" width: '100px'; margin-bottom: 10px;"></div>
            </div>
            <div class="modal-footer">
                @haspermission('ver_residente')
                <button type="button" class="btn btn-success" id="agregar">Agregar Inspección</button>
                @endhaspermission
                <button type="button" class="btn btn-secondary" id="cerrar_modal" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- modal agregar Inspeccion -->
<div class="modal fade" id="ventanaEmergente" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Agregar Inspección</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="nombre">Inspector:</label>
                        <select class="form-control" name="nombre" id="nombre" disabled>

                        </select>
                    </div>
                    <br>

                    <div class="col-md-6">
                        <label for="municipio">Municipio:</label>
                        <select class="form-control select2" name="municipio" id="municipio-select"></select>
                    </div>
                </div>
                <br>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="fecha">Fecha:</label>
                        <input type="date" class="form-control" name="fecha" id="fecha" placeholder="dd-mm-yy" disabled>
                    </div>

                    <br>

                    <div class="col-md-6">
                        <label for="N°acta">N° ACTA</label>
                        <input type="text" class="form-control" name="N°acta" id="N°acta">
                    </div>
                </div>
                <br>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="tipo_trabajo">Tipo de Trabajo</label>
                        <select class="form-control" name="tipo_trabajo" id="tipo_trabajo">
                            <option value="">Seleccione Tipo de Trabajo</option>
                            <option value="FI-29 revisión periódica línea matriz">FI-29 revisión periódica línea matriz</option>
                            <option value="RP 10444">RP 10444</option>
                            <option value="RP 12161">RP 12161</option>
                            <option value="RN 12162">RN 12162</option>
                            <option value="SA 12163">SA 12163</option>
                            <option value="SA 12164">SA 12164</option>
                        </select>
                    </div>

                    <br>

                    <div class="col-md-6">
                        <label for="contrato">Contrato</label>
                        <input type="text" class="form-control" name="contrato" id="contrato" value=":">
                    </div>
                </div>
                <br>
                <div class="form-group matriz-des1">
                    <div class="col-md-6">
                        <label for="orden_trabajo">Orden de trabajo</label>
                        <input type="text" class="form-control" name="orden_trabajo" id="orden_trabajo">
                    </div>

                    <br>

                    <div class="col-md-6">
                        <label for="categoria">Categoria</label>
                        <select class="form-control" name="categoria" id="categoria">
                            <option value="">Seleccione categoria</option>
                            <option value="RESIDENCIAL">RESIDENCIAL</option>
                            <option value="COMERCIAL">COMERCIAL</option>
                        </select>
                    </div>
                </div>
                <br>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="hora_inicio">Hora Inicio</label>
                        <input type="time" class="form-control" name="hora_inicio" id="hora_inicio" step="60" pattern="[0-9]{2}:[0-9]{2}">
                    </div>
                    <div class="col-md-6">
                        <label for="hora_final" style="margin-left: 10px;">Hora Final</label>
                        <input type="time" class="form-control" name="hora_final" id="hora_final" step="60" pattern="[0-9]{2}:[0-9]{2}">
                    </div>
                </div>
                <br>
                <div class="form-group matriz-des2">
                    <div class="col-md-6">
                        <label for="recintos">4 Recintos o mas</label>
                        <select class="form-control" name="recintos" id="recintos">
                            <option value="NO" selected>NO</option>
                            <option value="SI">SI</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="cantidad_recintos">Cantidad de recintos</label>
                        <input type="text" class="form-control" id="NroRecintosP" style="text-align: center;" disabled>
                    </div>
                </div>
                <br>
                <div class="form-group">
                    <div class="col-md-6">
                        <label for="resultado_cierre">Resultado Cierre</label>
                        <select class="form-control" name="resultado_cierre" id="resultado_cierre">
                            <option value="">Seleccione categoria</option>
                            <option value="CERTIFICADA">CERTIFICADA</option>
                            <option value="CERTIFICADA CON NOVEDADES">CERTIFICADA CON NOVEDADES</option>
                            <option value="INSPECCIONADA CON DEFECTO CRITICO VALLE">INSPECCIONADA CON DEFECTO CRITICO VALLE</option>
                            <option value="INSPECCIONADA CON DEFECTO NO CRITICO VALLE">INSPECCIONADA CON DEFECTO NO CRITICO VALLE</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button class="btn btn-success" id="agregarInspeccion">Agregar</button>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @section('js')
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
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
        const urlMunicipios = "{{ route('municipios.json') }}"; // Usando el helper route()
        const urlObtenerDetalles = "{{ route('obtener-url-detalles') }}"; // Usando el helper route()
        const urlObtenerBitacoras = "{{ route('obtener-url-bitacoras') }}"; // Usando el helper route()
        const urlActualizarDetallesDiario = "{{ route('produccion.ActualizarDetallesDiario', ['id' => ':id']) }}";
        const urlDiseñoEspecial = "{{ route('produccion.diseñoEspecial', ['id' => ':id']) }}";
        const urlDesasociar = "{{ route('produccion.eliminarDetallesDiario', ['id' => ':id']) }}";
        const urlCrearSession = "{{ route('produccion.crearSession') }}";
        const urlActualizarDetallesDia = "{{ route('produccion.detallesDiario',['fecha' => ':fecha', 'inspector' => ':inspector']) }}";
        const urlZonas = "{{ route('produccion.zonas') }}";
        const urlInsertar = "{{ route('produccion.insertarContrato') }}"


        $(document).ready(function() {
            $('#ventanaEmergente').on('shown.bs.modal', function() {
                select2();

                function select2() {
                    $('#municipio-select').select2({
                        language: "es",
                        ajax: {
                            url: urlMunicipios, // Ruta a la función del controlador
                            dataType: 'json',
                            delay: 250, // Retraso antes de realizar la búsqueda
                            data: function(params) {
                                return {
                                    term: params.term // Término de búsqueda
                                };
                            },
                            processResults: function(data) {
                                return {
                                    results: $.map(data, function(item, key) { // Mapear resultados
                                        return {
                                            id: key,
                                            text: item
                                        };
                                    })
                                };
                            },
                            cache: true
                        },
                        minimumInputLength: 2 // Mínimo de caracteres para iniciar la búsqueda
                    });
                }
            });
            $(window).on('resize', function() {
                $('#municipio-select').select2('destroy'); // Destruir la instancia actual de Select2
                $('#municipio-select').select2({
                    language: "es",
                    ajax: {
                        url: urlMunicipios, // Ruta a la función del controlador
                        dataType: 'json',
                        delay: 250, // Retraso antes de realizar la búsqueda
                        data: function(params) {
                            return {
                                term: params.term // Término de búsqueda
                            };
                        },
                        processResults: function(data) {
                            return {
                                results: $.map(data, function(item, key) { // Mapear resultados
                                    return {
                                        id: key,
                                        text: item
                                    };
                                })
                            };
                        },
                        cache: true
                    },
                    minimumInputLength: 2 // Mínimo de caracteres para iniciar la búsqueda
                });

            });
        });
    </script>
    @stop
    @stop