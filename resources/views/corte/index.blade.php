@extends('adminlte::page')

@section('title', 'Información General') 

@section('content_header')
    <h1>Información General</h1>
@stop

@section('content')
<style>
 .table {
    margin-bottom: 0; /* Elimina el margen inferior de las tablas */
}
</style>
<script src="{{asset('js/informacion_general.js')}}"></script>
<div class="row">  
<div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cortes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" >Crear Corte</a> 
                <table class="table table-striped" id="cortes">
                    <thead>
                        <tr>
                            <th>Id</th>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cortes as $corte)
                            <tr>
                                <td>{{ $corte->id }}</td>
                                <td>{{ $corte->nombre }}</td>
                                <td>{{ $corte->fecha_inicio }}</td>
                                <td>{{ $corte->fecha_fin }}</td>
                                <td>
                                    <a  class="btn btn-success">Editar</a>
                                    <a class="btn btn-primary">Detalles</a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Municipios</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" >Crear Municipio</a>
                <table class="table table-striped" id="municipios">
                    <thead>
                        <tr>
                         <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($municipios as $municipio)
                            <tr>
                               <td>{{ $municipio->nombre }}</td>
                            
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6"> 
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sedes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" >Crear Sede</a>
                <table class="table table-striped" id="sedes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sedes as $sede)
                            <tr>
                               <td>{{ $sede->nombre }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@stop
