@extends('layouts.tw.app')

@section('title', 'Programación')

@section('content_header')
    <h1>{{ $programacion->nombre }}</h1>
@endsection

@section('subtitle', $user->name)

@php
    /* $view solo llega en modo consulta; en edición la variable no existe.
       El JS original comparaba `view === ""` porque Blade imprime false como
       cadena vacía: aquí se resuelve a un booleano de una vez. */
    $soloLectura = (bool) ($view ?? false);
    $puedeGuardar = $programacion->finished == 0 && ! $soloLectura;
    $tabla = $tabla ?? [];
    $puedeAsignarTecnico = auth()->user()->can('ver_programacion');
@endphp

{{-- Solo el enlace de vuelta: los botones con lógica viven dentro del x-data,
     porque esta sección la pinta el layout fuera del componente. --}}
@section('actions')
    <a href="{{ route('programacion.index') }}" class="tw-btn-secondary">
        <i class="fas fa-arrow-left"></i> Regresar
    </a>
@endsection

@include('layouts.tw.partials.handsontable')

@section('content')
    <div x-data="programacionCreate({
            soloLectura: {{ $soloLectura ? 'true' : 'false' }},
            puedeAsignarTecnico: {{ $puedeAsignarTecnico ? 'true' : 'false' }},
            tablaId: {{ (int) $programacion->id }},
            usuario: @js($user->name),
            tecnicos: {{ Js::from($tecnicos->map(fn ($t) => $t->id . '. ' . $t->apellidos . ' ' . $t->nombres)->values()) }},
            filas: {{ Js::from($tabla) }},
            urls: {
                busqueda:  '{{ route('programacion.busqueda', ['contrato' => '__id__']) }}',
                store:     '{{ route('programacion.store') }}',
                update:    '{{ route('programacion.update', ['id' => '__id__']) }}',
                destroy:   '{{ route('programacion.destroy') }}',
                finish:    '{{ route('programacion.finish', ['id' => $programacion->id]) }}',
                plantilla: '{{ route('programacion.PlantillaStore') }}',
                index:     '{{ route('programacion.index') }}',
                municipios:'{{ route('municipios.json') }}',
            },
         })"
         class="space-y-6">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-calendar-days"></i></span>
                    <div>
                        <h2 class="tw-card-title">Contratos programados</h2>
                        <p class="tw-card-subtitle">
                            @if ($soloLectura)
                                Tabla en modo consulta.
                            @else
                                Escribe un contrato para traer sus datos; el resto de campos se
                                habilitan al encontrarlo.
                            @endif
                        </p>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <span class="tw-badge" :class="soloLectura ? 'chip-slate' : 'chip-emerald'"
                          x-text="soloLectura ? 'Solo lectura' : 'Edición'"></span>

                    @if ($puedeGuardar)
                        <button type="button" class="tw-btn-secondary" @click="abrirPlantilla()">
                            <i class="fas fa-file-circle-plus"></i> Añadir en plantilla
                        </button>
                        <button type="button" class="tw-btn-primary" @click="finalizar()"
                                :disabled="guardando">
                            <i class="fas" :class="guardando ? 'fa-spinner fa-spin' : 'fa-floppy-disk'"></i>
                            Guardar
                        </button>
                    @endif
                </div>
            </div>

            <div class="border-t border-slate-200/80 dark:border-slate-700/60">
                <div id="tabla_programacion" class="ht-theme-main ht-compacta"></div>
            </div>
        </section>

        @include('programacion.partials.create-modales')

        {{-- Velo de guardado --}}
        <div x-show="guardando" x-cloak
             class="fixed inset-0 z-[10050] flex items-center justify-center bg-slate-900/50 backdrop-blur-sm">
            <div class="rounded-2xl bg-white px-8 py-6 text-center shadow-2xl dark:bg-slate-800">
                <i class="fas fa-spinner fa-spin mb-3 block text-3xl text-brand-600 dark:text-brand-300"></i>
                <p class="text-sm font-medium text-slate-600 dark:text-slate-300">Finalizando programación…</p>
            </div>
        </div>
    </div>
@endsection

@section('js')
    @include('programacion.partials.create-script')
@endsection
