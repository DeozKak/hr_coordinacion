@extends('layouts.tw.app')

@section('title', 'Histórico')

@section('content_header')
    <h1>Histórico</h1>
@endsection

@section('subtitle', 'Órdenes cerradas de revisiones periódicas, con su recorrido completo.')

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="historico({ url: '{{ route('getDataHistorico') }}' })" class="space-y-4 2xl:space-y-6">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-clock-rotate-left"></i></span>
                    <div>
                        <h2 class="tw-card-title">Histórico</h2>
                        <p class="tw-card-subtitle" x-text="`${total} registros`"></p>
                    </div>
                </div>

                <div class="flex items-center gap-2 text-sm">
                    <span class="text-slate-500" x-text="`Página ${pagina} de ${totalPaginas}`"></span>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina - 1)" :disabled="pagina === 1 || cargando">Anterior</button>
                    <button type="button" class="tw-btn-secondary tw-btn-sm"
                            @click="irA(pagina + 1)" :disabled="pagina >= totalPaginas || cargando">Siguiente</button>
                </div>
            </div>

            <x-color-legend :items="[
                ['g1', 'Asignación base OSF'],
                ['g2', 'Información complementaria'],
                ['g3', 'Programación'],
                ['g4', 'Asignación inspector'],
                ['g5', 'Recepción en campo'],
                ['g6', 'Gestión en oficina'],
                ['g7', 'Formulación y cálculo'],
            ]" />

            <div class="relative border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tablaHistorico" class="ht-theme-main ht-compacta"></div>

                <div x-show="cargando" x-cloak
                     class="absolute inset-0 z-[900] flex items-center justify-center bg-white/70
                            backdrop-blur-[1px] dark:bg-slate-800/70">
                    <i class="fas fa-spinner fa-spin text-2xl text-brand-600 dark:text-brand-300"></i>
                </div>
            </div>

            <p x-show="!total && !cargando" x-cloak
               class="border-t border-slate-200/80 px-5 py-10 text-center text-sm text-slate-500
                      dark:border-slate-700/60">
                No hay registros en el histórico.
            </p>
        </section>
    </div>
@endsection

@section('js')
    @include('seguimiento.partials.historico-script')
@endsection
