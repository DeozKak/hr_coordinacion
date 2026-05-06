@extends('adminlte::page')

@section('title', 'stickers')

@section('content_header')
    <h1>Control Stickers</h1>
@stop

@section('content')

<input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
<input type="hidden" name="url_data" id="url_data" value="{{ route('bitacora.stickers.getData',['id' => $id]) }}">
<input type="hidden" name="url_update" id="url_update" value="{{ route('bitacora.stickers.update') }}">
<input type="hidden" name="index" id="index" value="{{ route('bitacora.stickers') }}">
<div class="card">
    <div class="card-body">
    <form action="{{ url()->previous() }}" method="GET">
        <button type="submit" class="btn btn-primary" id="btn_update">Volver</button>
    </form>
    <br>
    <br>
        <div class="row">
            <div class="col-12">

            <div id="table"></div>

            </div>
        </div>
    </div>
</div>

@section('js')
<script>
    const id_semana = {{ $id }};

</script>
<script src="{{ asset('js/stickers/show.js') }}?v={{ time() }}"></script>

 @stop
@stop
