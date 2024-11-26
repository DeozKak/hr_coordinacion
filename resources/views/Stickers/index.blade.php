@extends('adminlte::page')

@section('title', 'Stickers')

@section('content_header')
    <h1>Control Stickers</h1>
@stop   

@section('content')

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-12">
                <div id="table"></div>
            </div>
        </div>
    </div>
</div>



@section('js')
<script src="{{ asset('js/Stickers/index.js') }}?v={{ time() }}"></script>
@stop
@stop



