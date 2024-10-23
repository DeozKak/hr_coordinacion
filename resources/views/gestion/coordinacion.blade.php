@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1>Coordinación Nuevas</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/coordinacion.css')}}">

<div class="card">
    <div id="loader1" style="display: none;"></div>
    <div class="card-body">
        <div class="row align-items-end">
            <div class="col-md-2">
                <select class="form-control" id="columnasBuscar">
                    <option value="" disabled selected>Seleccione columna</option>
                    <option value="0">Orden</option>
                    <option value="1">Contrato</option>
                    <option value="2">Producto</option>
                    <option value="3">Numero solicitud</option>
                    <option value="4">Tipo solicitud</option>
                    <option value="5">Cedula</option>
                    <option value="6">Nombre</option>
                    <option value="7">Departamento</option>
                    <option value="8">Localidad</option>
                    <option value="9">Barrio</option>
                    <option value="10">Dirección</option>
                    <option value="11">Consecutivo Ruta</option>
                    <option value="12">Telefono</option>
                    <option value="13">Medidor</option>
                    <option value="14">Categoria</option>
                    <option value="15">Unidad</option>
                    <option value="16">Tipo trabajo</option>
                    <option value="17">Fecha asignación</option>
                    <option value="18">Observación solicitud</option>
                </select>
            </div>
            <div class="col-md-3 ms-2"> <!-- Agregado un margen izquierdo -->
                <input class="form-control" id="inputBuscar" type="text" placeholder="Buscar" readonly />
                <input type="hidden" id="tipoColumnas" />
                <input type="hidden" id="falseTrue" />
                <input type="hidden" id="tokenCoordinacionRP" value="{{ csrf_token() }}">
            </div>
        </div>
        <div id="prueba" class="mt-3" style="position: relative; display: none;">
            <!-- tabla coordinacion -->
        </div>
        <div class="overlay" style="display: none;">
            <i class="fas fa-2x fa-sync-alt"></i>
        </div>
        @csrf
    </div>
</div>

@section('js')
<script src="{{asset('js/coordinaciontbl.js')}}"></script>
<script>
    const url = "{{route('getdataCoordinacionRP')}}"
    const url2 = "{{route('filterData')}}"
    const url3 = "{{route('guardarProgramacionTecnico')}}"
</script>
@stop
@endsection