@extends('layouts.tw.app')

@section('title', 'Contratos sin categoría')
@section('content_header')
    <h1>Contratos sin categoría</h1>
@endsection
@section('subtitle', 'Asigna la categoría a los contratos que no la tienen.')

@include('layouts.tw.partials.handsontable')

@section('content')
    {{-- categoria.js lee estos hooks: no cambiar ids ni el global `nuevoArray`. --}}
    <input type="hidden" id="url" value="{{ route('bitacoras.contratos_sin_categoria.StoreCategoria') }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">

    <section class="tw-card">
        <div class="tw-card-header">
            <div class="flex items-center gap-3">
                <span class="tw-chip chip-amber"><i class="fas fa-tags"></i></span>
                <div>
                    <h2 class="tw-card-title">Contratos pendientes</h2>
                    <p class="tw-card-subtitle">
                        {{ $contratos_sin_categoria->count() }}
                        {{ $contratos_sin_categoria->count() === 1 ? 'contrato' : 'contratos' }} sin categoría
                    </p>
                </div>
            </div>
        </div>

        <div class="tw-card-body">
            {{-- categoria.js sólo hace classList.add('show'); se conserva ese
                 contrato con CSS por id en vez de la clase `hidden` de Tailwind. --}}
            <style>
                #message { display: none; opacity: 0; transition: opacity .5s; }
                #message.show { display: block; opacity: 1; }
            </style>
            <div id="message" class="mb-4 rounded-xl border border-sky-200 bg-sky-50 px-4 py-6 text-center
                                     dark:border-sky-800 dark:bg-sky-950/40">
                <i class="fas fa-circle-info mb-2 block text-2xl text-sky-500"></i>
                <span class="text-sm text-sky-800 dark:text-sky-200">
                    Nada que mostrar, todos los contratos tienen categoría.
                </span>
            </div>

            <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-700">
                <div id="table" class="ht-theme-main"></div>
            </div>

            <p class="tw-hint">
                <i class="fas fa-circle-info"></i>
                Edita la columna <strong>CATEGORÍA</strong>; los cambios se guardan automáticamente.
            </p>
        </div>
    </section>
@endsection

@section('js')
    <script>
        /* categoria.js espera este global antes de arrancar. */
        let registros = @json($contratos_sin_categoria);

        let nuevoArray = registros.map(registro => ({
            id: registro.id,
            CC_OPERARIO: registro.CC_OPERARIO,
            MUNICIPIO: registro.MUNICIPIO,
            FECHA: registro.FECHA,
            No_ACTA: registro.No_ACTA,
            TIPO_TRABAJO: registro.TIPO_TRABAJO,
            CONTRATO: registro.CONTRATO,
            ORDEN_TRABAJO: registro.ORDEN_TRABAJO,
            ORDEN_EXT: registro.ORDEN_EXT,
            CATEGORIA: registro.CATEGORIA,
            RESULTADO_CIERRE: registro.RESULTADO_CIERRE,
        }));
    </script>
    <script src="{{ asset('js/bitacora/categoria.js') }}?v={{ filemtime(public_path('js/bitacora/categoria.js')) }}"></script>
@endsection
