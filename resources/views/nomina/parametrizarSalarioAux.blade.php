@extends('adminlte::page')

@section('title', 'Parametro salario minimo - Auxilio transporte')

@section('content')
<link rel="stylesheet" href="{{ asset('css/nomina/parametroSalarioAux.css') }}">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-9">
            <div class="card mt-3">
                <div class="card-body">
                    <x-adminlte-card class="tituloFormSalAux" title="Parametrizar salario minimo - auxilio de transporte" theme="info" icon="fas fa-tags" collapsible maximizable>
                        <form autocomplete="off">
                            <div class="row">
                                <div class="col-6">
                                    <label for="fechaSalAuxInicio">Fecha inicio</label>
                                    <input type="month" class="form-control" id="fechaSalAuxInicio">
                                </div>
                                <div class="col-6">
                                    <label for="fechaSalAuxFin">Fecha fin</label>
                                    <input type="month" class="form-control" id="fechaSalAuxFin">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-6">
                                    <label for="salMin">Salario minimo</label>
                                    <input type="text" class="form-control inputNumerico" id="salMin">
                                </div>
                                <div class="col-6">
                                    <label for="auxTrans">Auxilio de transporte</label>
                                    <input type="text" class="form-control inputNumerico" id="auxTrans">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-12 mt-3 botonesFormularioSalAux">
                                    <button type="reset" class="btn btn-danger resetFormSalAux">Cancelar</button>
                                    <button data-token="{{ csrf_token() }}" data-url="{{ route('nomina.guardarSalarioAux')}}" type="button" class="btn btn-info" id="guardarParametroSalarioAux">Guardar</button>
                                    <input type="hidden" id="editarParametroSalarioAux" data-url="{{ route('nomina.actualizarSalarioAux')}}">
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
                    <table id="tableParametroSalarioAux" class="table table-sm">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>Fecha inicio</th>
                                <th>Fecha fin</th>
                                <th>Salario minimo</th>
                                <th>Auxilio transporte</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($parametroSalarioAux as $value)
                            <tr>
                                <td>{{ $value->id }}</td>
                                <td>{{ $value->fecha_inicio }}</td>
                                <td>{{ $value->fecha_fin }}</td>
                                <td>$ <?=number_format($value->salario_minimo)?></td>
                                <td>$ <?=number_format($value->auxilio_transporte)?></td>
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
<script src="{{ asset('js/nomina/parametroSalarioAux.js') }}"></script>
@endsection