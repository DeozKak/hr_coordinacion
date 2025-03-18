@extends('adminlte::page')

@section('title', 'Nomina')

@section('content_header')
<div class="d-flex align-items-center">
    <h1 class="mb-2">Nomina</h1>
    <a href="{{ route('nomina.parametrizarSalarioAux') }}" class="ml-2" title="Recuerde parametrizar el salario mínimo, auxilio de transporte y demás datos necesarios. Haga clic en el signo de interrogación para parametrizar." style="cursor: pointer;">
        <i class="fas fa-question-circle"></i>
    </a>
</div>
@endsection


@section('content')

    <link rel="stylesheet" href="{{ asset('css/nomina/nomina.css') }}?v={{ time() }}">

<div class="row align-items-center">
    <div class="col-md-2">
        <input type="month" class="form-control" id="mesAnio">
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <button data-token="{{ csrf_token() }}" data-url="{{ route('nomina.generarReporteNomina') }}" class="btn btn-success" id="generarReporte" style="margin-right: 2%;">Generar reporte</button>
        <div class="loaderDivNomina" style="display: none;">
            <span class="loaderNomina"></span>
        </div>
    </div>
</div>

<div class="card mt-3 cardReporteProduccion" style="display: none">
    <div class="card-body">
        <x-adminlte-card class="reporteNominaTitulo" theme="info" icon="fas fa-file-alt" collapsible maximizable>
            <button id="descargarExcelNomina" class="btn btn-success btn-sm mb-2">
                <i class="fas fa-file-excel"></i> Descargar Excel
            </button>
            <div id="tablaNomina">
                <!--Reporte nomina  -->
            </div>
            <div class="loaderTablaNomina" style="display: none">
                <span class="loaderNominaTabla"></span>
            </div>
        </x-adminlte-card>
    </div>
    <div class="card-body">
        <x-adminlte-card title="Reporte costos proyecto" theme="info" icon="fas fa-file-alt" collapsible maximizable>
            <div id="tablaCostosProyecto">
                <!--Reporte costos proyecto  -->
            </div>
            <div class="loaderTablaCostosProyecto" style="display: none">
                <span class="loaderCostosProyecto"></span>
            </div>
        </x-adminlte-card>
    </div>
</div>
<input type="hidden" id="tokenReporteNomina" value="{{ csrf_token() }}">
<input type="hidden" id="guardarMultaRodamiento" value="{{ route('nomina.guardarMultaRodamiento') }}">
@endsection

@section('js')
<script src="{{ asset('js/nomina/nomina.js') }}?v={{ time() }}"></script>
@endsection
