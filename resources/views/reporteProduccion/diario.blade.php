@extends('adminlte::page')

@section('title', 'Produccion diario')

@section('content_header')
<div class="d-flex align-items-center">
    <h1 class="mb-2">Reporte de producción diario</h1>
    <a href="{{ route('fechasProduccion.registrar') }}" class="ml-2" title="Recuerde parametrizar precios, para parametrizar precios haga click en el signo de interrogación" style="cursor: pointer;">
        <i class="fas fa-question-circle"></i>
    </a>
</div>
@endsection


@section('content')
<link rel="stylesheet" href="{{ asset('css/reporteProduccion/reporteDiarioProduccion.css') }}?v={{ time() }}">

<div class="row align-items-center">
    <div class="col-md-2">
        <select id="nominaSelectorAnio" class="form-control" aria-label="Seleccionar año">
            <option value="" disabled selected>Seleccione un año</option>
            @for ($ano = $currentYear; $ano >= 2022; $ano--)
            <option value="{{ $ano }}">{{ $ano }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-3 nominaSelectorMes" style="display: none">
        <select id="nomina-selector" class="form-control" aria-label="Seleccionar mes">
            <option value="" disabled selected>Seleccione un mes</option>
            <option value="{{ route('produccion.enero') }}">Enero</option>
            <option value="{{ route('produccion.febrero') }}">Febrero</option>
            <option value="{{ route('produccion.marzo') }}">Marzo</option>
            <option value="{{ route('produccion.abril') }}">Abril</option>
            <option value="{{ route('produccion.mayo') }}">Mayo</option>
            <option value="{{ route('produccion.junio') }}">Junio</option>
            <option value="{{ route('produccion.julio') }}">Julio</option>
            <option value="{{ route('produccion.agosto') }}">Agosto</option>
            <option value="{{ route('produccion.septiembre') }}">Septiembre</option>
            <option value="{{ route('produccion.octubre') }}">Octubre</option>
            <option value="{{ route('produccion.noviembre') }}">Noviembre</option>
            <option value="{{ route('produccion.diciembre') }}">Diciembre</option>
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <button class="btn btn-success" id="btnExportarDiraio" style="margin-right: 2%;">Exportar a Excel</button>
        <div class="loaderDiv" style="display: none;">
            <span class="loader"></span>
        </div>
    </div>
</div>


<div class="card mt-3 cardReporteDiarioProduccion" style="display: none">
    <div class="card-body">
        <x-adminlte-card title="REPORTE DE PRODUCCIÓN DIARIO" theme="info" icon="fas fa-file-alt" collapsible maximizable>
            <div id="example" style="display: none">
                <!--Reporte diario de produccion  -->
            </div>
            <div class="loaderTablaDiario" style="display: none">
                <span class="loaderDiario"></span>
            </div>
        </x-adminlte-card>
    </div>
    <div class="row">
        <div class="col-7">
            <div class="card-body">
                <x-adminlte-card title="RESUMEN" theme="info" icon="fas fa-file-alt" collapsible maximizable>
                    <div id="tablaResumen" style="display: none">
                        <!-- tablaResumen -->
                    </div>
                </x-adminlte-card>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="guardarNomina" value="{{ route('produccion.guardar') }}">
<input type="hidden" id="guardarInspeccion" value="{{ route('produccion.guardarInspeccionIndustrial') }}">
<input type="hidden" id="tokenNomina" value="{{ csrf_token() }}">
@endsection

@section('js')
<script src="{{ asset('js/reporteProduccion/reporteDiarioProduccion.js') }}?v={{ time() }}"></script>
@endsection