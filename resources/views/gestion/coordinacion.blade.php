@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1></h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/coordinacion.css')}}?v={{ time()}}">

<div id="loaderPageCoordination" style="display: none;"></div>
<div class="card cardCoordination" style="display: none">
    <div class="card-body">
        <x-adminlte-card title="Filtros" theme="info" icon="fas fa-tags" collapsible maximizable>
            <form id="formSearchCoordination" autocomplete="off">
                <div class="row">
                    <div class="col-md-4">
                        <label for="orden">Orden</label>
                        <input class="form-control numericalInput" type="text" id="orden">
                    </div>
                    <div class="col-md-4">
                        <label for="orden_solicitud_externa">Orden externa</label>
                        <input class="form-control numericalInput" type="text" id="orden_solicitud_externa">
                    </div>
                    <div class="col-md-4">
                        <label for="contrato">Contrato</label>
                        <input class="form-control numericalInput" type="text" id="contrato">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label for="codigo_tecnico">Inspector</label>
                        <select class="form-control" id="codigo_tecnico">
                            <option value="">Seleccione...</option>
                            @foreach ($inspectors as $inspector)
                            <option value="{{$inspector->id}}">{{$inspector->id}} - {{$inspector->nombres}} {{$inspector->apellidos}}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="localidad">Localidad</label>
                        <input class="form-control" type="text" id="localidad">
                    </div>
                    <div class="col-md-4">
                        <label for="sector_operativo">Barrio</label>
                        <input class="form-control" type="text" id="sector_operativo">
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-4">
                        <label for="id_sede">Sede</label>
                        <select class="form-control" id="id_sede">
                            <option value="">Seleccione...</option>
                            <option value="1">Capital</option>
                            <option value="2">Norte</option>
                            <option value="3">Sur</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="id_grupo">Grupo</label>
                        <select class="form-control" id="id_grupo">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="id_subGrupo">Sub grupo</label>
                        <select class="form-control" id="id_subGrupo">
                            <option value="">Seleccione...</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-2">
                    <div class="col-md-2">
                        <label for="diasInicio">Desde</label>
                        <input class="form-control inputNumericoDias" type="text" id="diasInicio">
                    </div>
                    <div class="col-md-2">
                        <label for="diasFin">Hasta</label>
                        <input class="form-control inputNumericoDias" type="text" id="diasFin">
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <button class="btn btn-primary btnSearchCoordination" type="button">Buscar</button>
                        <button type="button" class="btn btn-danger btnClearCoordination">Limpiar</button>
                        <input type="hidden" id="tokenCoordinacionRP" value="{{ csrf_token() }}">
                    </div>
                </div>
            </form>
        </x-adminlte-card>
        <h2 style="text-align: center;">Coordinación RP</h2>
        <button id="descargarExcelCoordination" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Descargar Excel
        </button>
        <button id="impresionMasiva" class="btn btn-info btn-sm">
            <i class="fas fa-print"></i> Impresion masiva
        </button>
        <button id="asignarOrdCercania" class="btn btn-primary btn-sm">
            <i class="fas fa-tasks"></i> Asignar ordenes por cercania
        </button>
        <p class="mt-2 totalResults"></p>
        <label for="marcarTodos"> Marcar todos</label>
        <input type="checkbox" id="marcarTodos">
        <div id="prueba" class="mt-3" style="position: relative;">
            <!-- tabla coordinacion -->
        </div>
        <div id="overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <span class="loaderCoordination"></span>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalImpMasiva" tabindex="-1" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title fs-5">Impresion masiva</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-md-12">
                            <div class="card mt-3">
                                <div class="card-body">
                                    <form id="formPlanilla" method="post" action="{{route('generarImpMasiva')}}" autocomplete="off">
                                        @csrf
                                        <div class="row justify-content-center">
                                            <div class="col-md-6 text-center">
                                                <label for="sedeImpMas">Sede</label>
                                                <select class="form-control mx-auto" name="sedeImpMas" id="sedeImpMas">
                                                    @foreach($sedes as $sede)
                                                        <option value="{{$sede->id}}">{{$sede->nombre}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mt-5 justify-content-center">
                                            <div class="col-md-6 text-center divTipoOrden">
                                                <label for="tipoOrdenImpMasiva">Tipo orden</label>
                                                <select class="form-control mx-auto" name="tipoOrden" id="tipoOrden">
                                                    <option value="">Ambas</option>
                                                    <option value="1">Masiva</option>
                                                    <option value="2">Externa</option>
                                                </select>
                                            </div>
                                            <div class="col-md-6 text-center">
                                                <label for="fechaAsigna">Fecha asigna</label>
                                                <select class="form-control mx-auto" name="fechaAsigna" id="fechaAsigna">
                                                    <option value="si">Si</option>
                                                    <option value="no" selected >NO</option>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="row mt-5 justify-content-center divFecha" style="display: none">
                                            <div class="col-md-6 text-center">
                                                <label for="fechaImpMasiva">Fecha</label>
                                                <input class="form-control mx-auto" type="date" name="fechaImpMasiva" id="fechaImpMasiva">
                                            </div>
                                        </div>
                                        <div class="row mt-5 justify-content-center">
                                            <div class="col-md-6 text-center">
                                                <label for="expExcel" style="text-align: center;">Exportar excel</label>
                                                <input class="form-control mx-auto" type="checkbox" name="expExcel" id="expExcel">
                                            </div>
                                            <div class="col-md-6 text-center">
                                                <label for="expPdf">Exportar pdf</label>
                                                <input class="form-control mx-auto" type="checkbox" name="expPdf" id="expPdf">
                                            </div>
                                        </div>
                                        <div class="row mt-5 justify-content-center">
                                            <button type="submit" class="btn btn-primary">Aceptar</button>
                                        </div>
                                    </form>
                                </div> 
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{asset('js/management/coordinaciontbl.js')}}?v={{ time()}}"></script>

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            position: "top-end",
            icon: "warning",
            title: "{{ session('error') }}",
            toast: true,
            timer: 3000,
        });
    });
</script>
@endif

<script>
    const url1 = "{{route('getdataCoordinacionRP')}}"
    const url2 = "{{route('filterData')}}"
    const url3 = "{{route('guardarProgramacionTecnico')}}"
    const url4 = "{{route('getGroupsForSede')}}"
    const url5 = "{{route('getDataSubGroups')}}"
    const url6 = "{{route('descargarExcelCoordination')}}"
    const url7 = "{{route('guardarCausaCierre')}}"
    const url8 = "{{route('guardarFechaSolicitudCierre')}}"
    const url9 = "{{route('marcaOrden')}}"
    const url10 = "{{ route('marcaOrdenMasiva')}}"
    const url11 = "{{ route('asignarOrdCercania')}}"
</script>
@stop
@endsection