@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1>Coordinación Nuevas</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/coordinacion.css')}}">

<div class="card">
    <div class="card-body">


        <div id="prueba" style=" width: '100px'"></div>

        @csrf
    </div>
</div>

@section('js')
<script src="{{asset('js/coordinaciontbl.js')}}"></script>
<script>
    const url = "{{route('getdataCoordinacionRP')}}"
</script>
@stop
@endsection