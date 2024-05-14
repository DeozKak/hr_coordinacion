@extends('adminlte::page')

@section('title', 'Reportes de Bitácoras')

@section('content_header')
<h1>Reporte {{$bitacora->nombre_archivo}}</h1>
@endsection


@section('content')
<link rel="stylesheet" href="{{asset('css/bitacoras/verReportes.css')}}">
<body>
    <input type="hidden" id="id_bitacora" value="{{route('bitacoras.consulta_reporte',['id_bitacora'=>$bitacora->id])}}">
    <input type="hidden" id="url_indicadores" value="{{route('bitacoras.Consulta_indicadores',['id_bitacora'=>$bitacora->id])}}">
<script src="{{asset('js/verReportes.js')}}"></script>
<div class="shadow-container">
    <div class="card-body">
    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>
        <div id="indicadores"style=" margin-bottom: 10px;"></div>

        <div id="tabla" style=" width: '100px'"></div>

        @csrf
    </div>
</div>

</body>
@endsection