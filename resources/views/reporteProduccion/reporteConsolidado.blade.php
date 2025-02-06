@extends('adminlte::page')

@section('title', 'Fechas nomina')

@section('content_header')
<div class="d-flex align-items-center">
    <h1 class="mb-2">Reporte Consolidado</h1>
    <a href="{{ route('fechasProduccion.registrar') }}" class="ml-2" title="Recuerde parametrizar precios, para parametrizar precios haga click en el signo de interrogación" style="cursor: pointer;">
        <i class="fas fa-question-circle"></i>
    </a>
</div>
@endsection

@section('content')
<link rel="stylesheet" href="{{ asset('css/reporteProduccion/reporteConsolidado.css') }}">
<div class="row">
    <div class="col-md-2 mt-1">
        <select id="selectorAnio" class="form-control" aria-label="Seleccionar año"
            data-url="{{ route('nomina.generarReporteConsolidado') }}" data-token="{{ csrf_token() }}">
            <option value="" disabled selected>Seleccione un año</option>
            @for ($ano = $currentYear; $ano >= 2023; $ano--)
            <option value="{{ $ano }}">{{ $ano }}</option>
            @endfor
        </select>
    </div>
    <div class="col-md-2 d-flex align-items-center">
        <button type="button" class="btn btn-success" disabled id="btnExportarConsolidado" style="margin-right: 2%;">Exportar a Excel</button>
        <div class="loaderDivReporteConsolidado" style="display: none;">
            <span class="loaderReporteConsolidado"></span>
        </div>
    </div>
</div>

<div class="card mt-3 cardReporte" style="display: none">
    <div class="card-body">
        <x-adminlte-card title="REPORTE CONSOLIDADO" theme="info" icon="fas fa-file-alt" collapsible maximizable>
            <div id="reporteConsolidado">
                <!-- reporteConsolidado -->
            </div>
        </x-adminlte-card>
    </div>
    <div class="row">
        <div class="col-4">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="REPORTE TIPO DE TRABAJO" theme="info" icon="fas fa-file-alt" collapsible maximizable>
                        <div id="tablaPrevias" style="display: none">
                            <!-- reportePrevias -->
                        </div>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
        <div class="col-8">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="REPORTE POR ZONA" theme="info" icon="fas fa-file-alt" collapsible maximizable>
                        <div class="divSelector mb-3" style="display: none">
                            <select id="selectorMes" class="form-control selector-pequeno" aria-label="Seleccionar mes" data-url="{{ route('produccion.generarReportePorMes') }}">
                                <option value="0" disabled selected>Seleccione un mes</option>
                                @foreach ($meses as $index => $mes)
                                <option value="{{ $index }}">{{ $mes }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div id="reportePorMes" style="display: none">
                            <!-- reportePorMes -->
                        </div>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>
<input type="hidden" id="guardarMetas" value="{{ route('produccion.insertarMetas') }}">
@endsection

@section('js')
<script src="{{ asset('js/reporteProduccion/reporteConsolidado.js') }}"></script>
@endsection