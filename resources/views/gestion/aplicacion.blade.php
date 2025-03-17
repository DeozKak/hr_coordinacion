@extends('adminlte::page')

@section('title', 'Aplicacion')

@section('content_header')
<h1></h1>
@endsection

@section('content')
<!-- <link rel="stylesheet" href="{{asset('css/gestion/planilla.css')}}?v={{ time()}}"> -->

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-3">
                <div class="card-body">
                    <x-adminlte-card title="Organizador App" theme="info" icon="fas fa-tags">
                        <form id="formAplication" method="get" action="{{route('generarTablaAplication')}}" autocomplete="off">
                            <div class="row">
                                <div class="col-md-4 divTipoOrden">
                                    <label for="tipoOrden">Tipo orden</label>
                                    <select class="form-control" name="tipoOrden" id="tipoOrden">
                                        <option value="1">Masiva</option>
                                        <option value="2">Externa</option>
                                        <option value="3">Ambas</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="parametro">Parametro</label>
                                    <select class="form-control" name="parametro" id="parametro">
                                        <option value="1">Fecha</option>
                                        <option value="2">Marca</option>
                                        <option value="3">Todo</option>
                                    </select>
                                </div>
                                <div class="col-md-4 divFecha">
                                    <label for="">Fecha</label>
                                    <input class="form-control" name="fechaAplication" id="fechaAplication" type="date">
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-4">
                                    <label for="poblacion">Población</label>
                                    <select class="form-control" name="poblacion" id="poblacion">
                                        <option value="1">Unico</option>
                                        <option value="2">Todos</option>
                                    </select>
                                </div>
                                <div class="col-md-8 divInspector">
                                    <label for="inspector">Nombre inspector</label>
                                    <select class="form-control" name="inspector" id="inspector">
                                        <option value="0">Oficina</option>
                                        @foreach($inspectors as $inspector)
                                        <option value="{{$inspector->id}}">{{$inspector->apellidos}} {{$inspector->nombres}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-3">
                                <div class="col-md-3">
                                    <button type="button" id="generarAplicacion" class="btn btn-success">Buscar</button>
                                </div>
                            </div>
                        </form>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{asset('js/management/aplicacion.js')}}?v={{ time()}}"></script>

@stop
@endsection