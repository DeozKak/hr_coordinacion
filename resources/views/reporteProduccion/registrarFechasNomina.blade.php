@extends('adminlte::page')

@section('title', 'Fechas parametros')

@section('content')
<link rel="stylesheet" href="{{ asset('css/reporteProduccion/fechasParametro.css') }}">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card mt-3">
                <div class="card-body">
                    <x-adminlte-card class="tituloForm" title="Parametrizar precios" theme="info" icon="fas fa-tags" collapsible maximizable>
                        <form autocomplete="off">
                            <div class="row">
                                <div class="col-4">
                                    <label for="fechaPrecioInicio">Fecha inicio</label>
                                    <input type="month" class="form-control" id="fechaPrecioInicio">
                                </div>
                                <div class="col-4">
                                    <label for="fechaPrecioFin">Fecha fin</label>
                                    <input type="month" class="form-control" id="fechaPrecioFin">
                                </div>
                                <div class="col-4">
                                    <label for="inspeccionInd">Inspección industrial</label>
                                    <input type="text" class="form-control inputNumerico" id="inspeccionInd">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-3">
                                    <h4 class="text-center">Residencial</h4>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <label for="metroRes">Metropolitano</label>
                                    <input type="text" class="form-control inputNumerico" id="metroRes">
                                </div>
                                <div class="col-4">
                                    <label for="norteRes">Norte del valle</label>
                                    <input type="text" class="form-control inputNumerico" id="norteRes">
                                </div>
                                <div class="col-4">
                                    <label for="caucaRes">Cauca/Buenaventura</label>
                                    <input type="text" class="form-control inputNumerico" id="caucaRes">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-3">
                                    <h4 class="text-center">Comercial</h4>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-4">
                                    <label for="metroCom">Metropolitano</label>
                                    <input type="text" class="form-control inputNumerico" id="metroCom">
                                </div>
                                <div class="col-4">
                                    <label for="norteCom">Norte del valle</label>
                                    <input type="text" class="form-control inputNumerico" id="norteCom">
                                </div>
                                <div class="col-4">
                                    <label for="caucaCom">Cauca/Buenaventura</label>
                                    <input type="text" class="form-control inputNumerico" id="caucaCom">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-3 botonesFormulario">
                                    <button type="reset" class="btn btn-danger resetForm">Cancelar</button>
                                    <button data-token="{{ csrf_token() }}" data-url="{{ route('fechasParametro.guardar')}}" type="button" class="btn btn-info" id="guardarParametro">Guardar</button>
                                    <input type="hidden" id="ditarParametro" data-url="{{ route('fechasParametro.actualizar') }}">
                                </div>
                            </div>
                        </form>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- consulta -->
<div class="container">
    <div class="card mt-3">
        <div class="card-body">
            <x-adminlte-card title="Consultar parametros" theme="info" icon="fas fa-tags" collapsible maximizable>
                <!-- tabla para ver los parametros -->
                <div class="table-responsive">
                    <table id="tableParametro" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Fecha inicio</th>
                                <th>Fecha fin</th>
                                <th>Res metropolitano</th>
                                <th>Res Norte</th>
                                <th>Res Cauca</th>
                                <th>Com metropolitano</th>
                                <th>Com Norte</th>
                                <th>Com Cauca</th>
                                <th>Ins industrial</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($fechaPrecios as $item)
                                <tr>
                                    <td>{{ $item->id }}</td>
                                    <td>{{ $item->fecha_inicio }}</td>
                                    <td>{{ $item->fecha_fin }}</td>
                                    <td>$ <?=number_format($item->res_metro)?></td>
                                    <td>$ <?=number_format($item->res_norte)?></td>
                                    <td>$ <?=number_format($item->res_cauca)?></td>
                                    <td>$ <?=number_format($item->com_metro)?></td>
                                    <td>$ <?=number_format($item->com_norte)?></td>
                                    <td>$ <?=number_format($item->com_cauca)?></td>
                                    <td>$ <?=number_format($item->inspeccion_industrial)?></td>
                                    <td>
                                        <div class="btn-group" role="group" aria-label="Botones">
                                            <button title="Editar" type="button" class="btn btn-info btn-sm editarFechasParametros">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </x-adminlte-card>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('js/reporteProduccion/fechasParametro.js') }}"></script>
@endsection