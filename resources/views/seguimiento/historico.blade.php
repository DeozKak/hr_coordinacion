@extends('adminlte::page')

@section('title', 'Historico')

@section('content_header')
<h1></h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/historico.css')}}?v={{ time()}}">

<div id="loaderPageHistorico" style="display: none;"></div>
<div class="card cardHistorico" style="display: none">
    <div class="card-body">
        <x-adminlte-card title="Filtros" theme="info" icon="fas fa-tags" collapsible maximizable>
            <form id="formSearchHistorico" autocomplete="off">
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
                <div class="row">
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
                <div class="row">
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
                <div class="row mt-4">
                    <div class="col-md-4">
                        <button class="btn btn-primary btnSearchHistorico" type="button">Buscar</button>
                        <button type="button" class="btn btn-danger btnClearHistorico">Limpiar</button>
                        <input type="hidden" id="tokenCoordinacionRP" value="{{ csrf_token() }}">
                    </div>
                </div>
            </form>
        </x-adminlte-card>
        <h2 style="text-align: center;">Histórico</h2>
        <!-- <button id="descargarExcelHistorico" class="btn btn-success btn-sm">
            <i class="fas fa-file-excel"></i> Descargar Excel
        </button> -->
        <p class="mt-1 totalResults"></p>
        <div id="historico" class="mt-3" style="position: relative;">
            <!-- tabla coordinacion -->
        </div>
        <!-- Overlay for loading -->
        <div id="overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <span class="loaderHistorico"></span>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{asset('js/management/historico.js')}}?v={{ time()}}"></script>
<script>
    const url1 = "{{route('getDataHistorico')}}"
    // const url2 = "{{route('filterData')}}"
    // const url3 = "{{route('guardarProgramacionTecnico')}}"
    const url4 = "{{route('getGroupsForSede')}}"
    const url5 = "{{route('getDataSubGroups')}}"
    // const url6 = "{{route('descargarExcelCoordination')}}"
    // const url7 = "{{route('guardarCausaCierre')}}"
    // const url8 = "{{route('guardarFechaSolicitudCierre')}}"
</script>
@stop
@endsection