@extends('adminlte::page')

@section('title', 'Nomina')

@section('head')
<link rel="stylesheet" href="{{asset('css/nomina/diario_nomina.css') }}">


@section('content_header')
<h1>Nomina</h1>
<button class="btn1" data-url="{{ route('nomina.todos') }}">Todos</button>
<button class="btn2" data-url="{{ route('nomina.enero') }}">Enero</button>
<button class="btn3" data-url="{{ route('nomina.febrero') }}">Febrero</button>
<button class="btn4" data-url="{{ route('nomina.marzo') }}">Marzo</button>
<button class="btn5" data-url="{{ route('nomina.abril') }}">Abril</button>
<button class="btn6" data-url="{{ route('nomina.mayo') }}">Mayo</button>
<button class="btn7" data-url="{{ route('nomina.junio') }}">Junio</button>
<button class="btn8" data-url="{{ route('nomina.julio') }}">Julio</button>
<button class="btn9" data-url="{{ route('nomina.agosto') }}">Agosto</button>
<button class="btn10" data-url="{{ route('nomina.septiembre') }}">Septiembre</button>
<button class="btn11" data-url="{{ route('nomina.octubre') }}">Octubre</button>
<button class="btn12" data-url="{{ route('nomina.noviembre') }}">Noviembre</button>
<button class="btn13" data-url="{{ route('nomina.diciembre') }}">Diciembre</button>
<button id="edit-btn" class="btn14" disabled>Editar</button>
@endsection

@section('content')
<div class="content">
    <div id="example"></div>
</div>

@section('js')
<script src="{{asset('js/diario_nomina.js')}}"></script>

@stop
@stop
