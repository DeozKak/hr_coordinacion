@extends('adminlte::page')

@section('title', 'Recepción')

@section('content_header')
<div></div>
<!-- <h1>Recepción</h1le=> -->
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/reception.css')}}?v={{ time() }}">
<div id="loaderPageReception" style="display: none"></div>
<div class="card cardReception" style="display: none">
    <div class="card-body">
        <x-adminlte-card class="tituloFormSalAux" title="Filtros" theme="info" icon="fas fa-tags" collapsible maximizable>
            <form id="formSearchReception" autocomplete="off" class="py-2">
                <div class="row">
                    <div class="col-md-3">
                        <label for="ordenTrabajo">Orden principal</label>
                        <input class="form-control numericalInput" type="text" id="ordenTrabajo">
                    </div>
                    <div class="col-md-3">
                        <label for="ordenExterna">Orden segundaria</label>
                        <input class="form-control numericalInput" type="text" id="ordenExterna">
                    </div>
                    <div class="col-md-3">
                        <label for="numeroSolicitud">Número de solicitud</label>
                        <input class="form-control numericalInput" type="text" id="numeroSolicitud">
                    </div>
                    <div class="col-md-3">
                        <label for="contrato">Contrato</label>
                        <input class="form-control numericalInput" type="text" id="contrato">
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <label for="direccion">Dirección</label>
                        <input class="form-control" type="text" id="direccion">
                    </div>
                    <div class="col-md-4">
                        <label for="ccOperario">Codigo del tecnico</label>
                        <select class="form-control" id="ccOperario">
                            <option value="">Seleccione...</option>
                            @foreach($inspectors as $inspector)
                                <option value="{{$inspector->id}}">{{$inspector->id}}</option>     
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4 ">
                        <label for="tipo">Tipo</label>
                        <select class="form-control" id="tipo">
                            <option value="">Seleccione...</option>
                            <option value="Existe efe">Existe efe</option>
                            <option value="No existe efe">No existe efe</option>
                        </select>
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <label for="estadoRecepcion">Estado recepción</label>
                        <select class="form-control" id="estadoRecepcion">
                            <option value="">Seleccione...</option>
                            <option value="1">1</option>
                            <option value="2">2</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="created_at">Fecha recepción</label>
                        <input class="form-control" type="date" id="created_at">
                    </div>
                    <div class="col-md-4">
                        <label for="numActa">Acta</label>
                        <input class="form-control numericalInput" type="text" id="numActa">
                    </div>
                </div>
                <div class="row mt-4">
                    <div class="col-md-4">
                        <button class="btn btn-primary btnSearchReception" type="button">Buscar</button>
                        <button type="button" class="btn btn-danger btnClearReception">Limpiar</button>
                    </div>
                </div>
            </form>
        </x-adminlte-card>
        <h2 style="text-align: center;">Recepción</h2>
        <p class="mt-3 totalResults"></p>
        <div id="tableReception" style="position: relative;">
            <!-- table reception -->
        </div>
        <!-- Overlay for loading -->
        <div id="overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <span class="loaderReception"></span>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/management/reception.js') }}?v={{ time() }}"></script>
<script>
    const urlReception = "{{ route('management.reception') }}"
    const token = "{{ csrf_token() }}"
    const urlFilter = "{{ route('management.filterDataReception') }}";
</script>
@stop
@endsection