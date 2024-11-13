@extends('adminlte::page')

@section('title', 'Recepción')

@section('content_header')
<div></div>
<!-- <h1>Recepción</h1le=> -->
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/reception.css')}}?v={{ time() }}">
<div id="loaderPageReception" style="display: none"></div>
<div class="card cardReception" style="display: none">
    <div class="card-body">
        <h2 style="text-align: center;">Recepción</h2>
        <div id="tableReception" class="mt-3" style="position: relative;">
            <!-- table reception -->
        </div>
        <!-- Overlay for loading -->
        <div id="overlay" style="display: none; position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(255, 255, 255, 0.8); z-index: 1000;">
            <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
                <span class="loaderReception"></span>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/management/reception.js') }}?v={{ time() }}"></script>
<script>
    const url = "{{ route('management.reception') }}"
    const token = "{{ csrf_token() }}"
</script>
@stop
@endsection