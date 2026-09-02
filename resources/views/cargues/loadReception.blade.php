@extends('layouts.tw.app')

@section('title', 'Recepción')

@section('content_header')
    <h1>Recepción</h1>
@endsection

@section('subtitle', 'Carga del archivo de recepción.')

@section('content')
    <div class="mx-auto max-w-xl">
        <x-carga-archivo titulo="Carga de recepción"
                         :action="route('load.receptionStore')"
                         icon="fa-inbox" tint="violet" />
    </div>
@endsection
