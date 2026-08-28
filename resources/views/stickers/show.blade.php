@extends('layouts.tw.app')

@section('title', 'Detalle de stickers')

@section('content_header')
    <h1>Detalle de stickers</h1>
@endsection

@section('subtitle', 'Saldos y consumo de la semana seleccionada.')

@include('layouts.tw.partials.handsontable')

@section('content')
    {{-- show.js lee estos hooks por id: no cambiar los identificadores. --}}
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <input type="hidden" name="url_data" id="url_data" value="{{ route('bitacora.stickers.getData', ['id' => $id]) }}">
    <input type="hidden" name="url_update" id="url_update" value="{{ route('bitacora.stickers.update') }}">
    <input type="hidden" name="index" id="index" value="{{ route('bitacora.stickers') }}">

    <section class="tw-card">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-violet"><i class="fas fa-table-list"></i></span>
                <div>
                    <h2 class="tw-card-title">Semana {{ $id }}</h2>
                    <p class="tw-card-subtitle">Edita las columnas AMARILLOS y ROJOS; se guardan al salir de la celda.</p>
                </div>
            </div>

            <a href="{{ url()->previous() }}" class="tw-btn-secondary">
                <i class="fas fa-arrow-left"></i> Volver
            </a>
        </div>

        <div class="tw-card-body">
            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                <div id="table" class="ht-theme-main"></div>
            </div>
        </div>
    </section>
@endsection

@section('js')
    <script>
        /* show.js espera este global antes de arrancar. */
        const id_semana = {{ $id }};
    </script>
    <script src="{{ asset('js/stickers/show.js') }}?v={{ time() }}"></script>
@endsection
