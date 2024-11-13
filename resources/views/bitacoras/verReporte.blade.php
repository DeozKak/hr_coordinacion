@extends('adminlte::page')

@section('title', 'Reportes de Bitácoras')

@section('content_header')
<h1>Reporte {{$bitacora->nombre_archivo}}</h1>
@endsection


@section('content')
<link rel="stylesheet" href="{{asset('css/bitacoras/verReportes.css')}}">

<body>
    <input type="hidden" id="url_devolucion" value="{{route('bitacoras.devolver',['ids' => ':id','bitacora' => $bitacora->id])}}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="id_bitacora" value="{{route('bitacoras.consulta_reporte',['id_bitacora'=>$bitacora->id])}}">
    <input type="hidden" id="url_indicadores" value="{{route('bitacoras.Consulta_indicadores',['id_bitacora'=>$bitacora->id])}}">
<script src="{{asset('js/verReportesV2.js')}}"></script>
<div class="shadow-container">
    <div class="card-body">
    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>

        <div id="indicadores"style=" margin-bottom: 10px;"></div>
        <a class="btn btn-secondary" id="devolucion" style="margin-bottom: 10px;">Pasar a devolucion</a>
        <div id="tabla" style=" width: '100px'"></div>
        
        @csrf
    </div>
</div>
@section('js')

<script>
    const causales = {!!json_encode($causales_dv)!!};
</script>


@stop
</body>
@endsection