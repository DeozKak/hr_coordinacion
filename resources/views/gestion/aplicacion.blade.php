@extends('layouts.tw.app')

@section('title', 'Organizador App')

@section('content_header')
    <h1>Organizador App</h1>
@endsection

@section('subtitle', 'Arma el listado de órdenes para la aplicación de campo.')

@section('content')
    <div class="mx-auto max-w-3xl"
         x-data="{
            tipoOrden: '1',
            parametro: '1',
            poblacion: '1',
            inspector: '0',
            fecha: '',
         }">

        <section class="tw-card">
            <div class="tw-card-header">
                <div class="flex items-center gap-3">
                    <span class="tw-chip chip-blue"><i class="fas fa-tags"></i></span>
                    <div>
                        <h2 class="tw-card-title">Filtros</h2>
                        <p class="tw-card-subtitle">La fecha y el inspector sólo aplican a algunas combinaciones.</p>
                    </div>
                </div>
            </div>

            <form id="formAplication" method="get" action="{{ route('generarTablaAplication') }}"
                  autocomplete="off" class="p-5">

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div>
                        <label class="tw-label" for="tipoOrden">Tipo de orden</label>
                        <select class="tw-select" name="tipoOrden" id="tipoOrden" x-model="tipoOrden">
                            <option value="1">Masiva</option>
                            <option value="2">Externa</option>
                            <option value="3">Ambas</option>
                        </select>
                    </div>

                    <div>
                        <label class="tw-label" for="parametro">Parámetro</label>
                        <select class="tw-select" name="parametro" id="parametro" x-model="parametro">
                            <option value="1">Fecha</option>
                            <option value="2">Marca</option>
                            <option value="3">Todo</option>
                        </select>
                    </div>

                    {{-- La fecha sólo tiene sentido con el parámetro "Fecha". Antes se
                         ocultaba a mano moviendo clases de rejilla de Bootstrap. --}}
                    <div x-show="parametro === '1'" x-cloak x-transition.opacity>
                        <label class="tw-label" for="fechaAplication">Fecha</label>
                        <input class="tw-input" type="date" name="fechaAplication" id="fechaAplication"
                               x-model="fecha">
                    </div>

                    <div>
                        <label class="tw-label" for="poblacion">Población</label>
                        <select class="tw-select" name="poblacion" id="poblacion" x-model="poblacion">
                            <option value="1">Único</option>
                            <option value="2">Todos</option>
                        </select>
                    </div>

                    {{-- Con "Todos" no se elige inspector. --}}
                    <div class="lg:col-span-2" x-show="poblacion === '1'" x-cloak x-transition.opacity>
                        <label class="tw-label" for="inspector">Nombre del inspector</label>
                        <select class="tw-select" name="inspector" id="inspector" x-model="inspector">
                            <option value="0">Oficina</option>
                            @foreach ($inspectors as $inspector)
                                <option value="{{ $inspector->id }}">
                                    {{ $inspector->apellidos }} {{ $inspector->nombres }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-5 flex justify-end">
                    {{-- El botón sigue sin enviar el formulario, igual que antes: el
                         endpoint generarTablaAplication() está vacío en el
                         controlador. Lo que cambia es que ahora se dice, en vez de
                         escribir "hola mundo" en la consola del navegador. --}}
                    <button type="button" id="generarAplicacion" class="tw-btn-primary"
                            @click="window.Swal.fire({
                                icon: 'info',
                                title: 'Módulo sin terminar',
                                text: 'La generación del listado todavía no está implementada en el servidor.',
                            })">
                        <i class="fas fa-magnifying-glass"></i> Buscar
                    </button>
                </div>
            </form>
        </section>
    </div>
@endsection
