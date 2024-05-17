@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/produccion/produccion.css')}}">
<script src="{{asset('js/producciondetalles.js')}}"></script>
<input type="hidden" id="id_produccion" value="{{route('produccion.datosDetalles')}}">
<div class="shadow-container">
    <div class="card-body">
    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>
        
        <div id="detalles" style=" width: '100px'"></div>
 
    </div>
</div>


@stop