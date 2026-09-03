@extends('layouts.tw.app')

@section('title', 'Asignadas y cerradas')

@section('content_header')
    <h1>Asignadas y cerradas</h1>
@endsection

@section('subtitle', 'Carga de los archivos de OSF para revisiones periódicas.')

@section('content')
    {{-- Los avisos de sesión los pinta la plantilla; antes esta vista traía su
         propio bloque de alerta y un Swal suelto para lo mismo. --}}
    <div class="grid gap-4 2xl:gap-6 md:grid-cols-2">
        <x-carga-archivo titulo="Asignadas OSF"
                         :action="route('cargues.store')"
                         icon="fa-list-check" tint="blue" />

        <x-carga-archivo titulo="Cerradas"
                         :action="route('cargues.storeClosed')"
                         icon="fa-circle-check" tint="emerald" />
    </div>
@endsection
