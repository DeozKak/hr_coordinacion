@extends('layouts.tw.app')

@section('title', 'Bitácoras')

@section('content_header')
    <h1>Bitácoras</h1>
@endsection

@section('content')
    {{-- Dependencias --}}
    <link rel="stylesheet" href="{{asset('css/bitacoras/generarV3.1.css')}}">
    <script src="{{ asset('js/bitacora/generar.js') }}"></script>
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_diaria" value="{{ route('bitacoras.diaria') }}">

    {{-- Loader y Overlay para operaciones AJAX --}}
    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>

    <div class="container mt-4">
        {{-- Usamos nuestro nuevo layout de grid --}}
        <div class="upload-grid">

            @unlessrole('Supervisor')
            {{-- Tarjeta #1: Bitácora Todos --}}
            <div class="upload-card">
                <h2 class="text-center">Bitácora Todos</h2>
                <small class="text-muted text-center">(No suma producción)</small>

                <label for="archivoDiaria" class="form-label mt-3">Seleccione Bitácora:</label>
                <input class="form-control" type="file" name="archivoDiaria" id="archivo_diaria">

                <div class="button-container">
                    <button class="btn-gradient" id="btnProcesar"><span>Procesar</span></button>
                </div>

                <div class="row mt-3" id="message"></div>
            </div>
            @endunlessrole

            {{-- Tarjeta #2: Subir Archivos (siempre visible) --}}
            <div class="upload-card">
                <h2 class="text-center">Subir Archivos</h2>

                <form action="{{route('bitacoras.generar')}}" method="POST" enctype="multipart/form-data">
                    @csrf

                    @role('Supervisor')
                    <input type="hidden" name="supervisor" value="{{$supervisores->id}}">
                    <select class="form-control" id="supervisor" disabled>
                        <option selected>{{$supervisores->name}}</option>
                    </select>
                    @else
                        <label for="supervisor" class="form-label mt-3">Supervisor:</label>
                        <select class="form-control" name="supervisor" id="supervisor">
                            <option value="">Seleccione Supervisor</option>
                            <option value="0">Todos</option>
                            @foreach ($supervisores as $supervisor)
                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                            @endforeach
                        </select>
                        @error('supervisor')
                        <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
                        @endrole

                        <label for="archivo" class="form-label mt-3">Seleccione Bitácora:</label>
                        <input class="form-control" type="file" name="archivo" id="archivo">
                        @error('archivo')
                        <div class="invalid-feedback d-block">{{ $message }}</div> @enderror

                        <div class="button-container">
                            <button class="btn-gradient" type="submit"><span>Subir Archivo</span></button>
                        </div>
                </form>

            </div>
        </div>
    </div>
    @if (session('error'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: "Error",
                    text: "{{session('error')}}",
                    icon: "error"
                });
            });
        </script>
        @php
            session()->forget('error');
        @endphp
    @endif
    @if(session('success'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: "Éxito",
                    text: "{{session('success')}}",
                    icon: "success"
                });
            });
        </script>
    @endif
    @if (session('warning'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    title: "{{session('warning')}}",
                    showDenyButton: true,
                    showCancelButton: false,
                    allowOutsideClick: false,
                    confirmButtonText: "Si",
                    denyButtonText: "No",
                }).then((result) => {

                    if (result.value) {
                        window.location.href = "{{route('bitacoras.restaurar',['id'=> $temp])}}";
                    }
                    if (result.isDenied) {
                        swal.fire({
                            icon: "warning",
                            title: "Se perderán los cambios!",
                            allowOutsideClick: false,
                            showDenyButton: true,
                            confirmButtonText: "Quiero generar una bitacora nueva",
                            denyButtonText: "Mantener cambios",
                        }).then((result) => {

                            if (result.isConfirmed) {
                                $.ajax({
                                    type: "POST",
                                    dataType: "json",
                                    data: {
                                        _token: "{{ csrf_token() }}"
                                    },
                                    url: "{{route('bitacoras.borrar',['id'=> $temp])}}",
                                    success: function (response) {
                                        console.log(response);
                                    },
                                    error: function (xhr, error, status) {

                                        console.log(xhr.responseText);

                                    }

                                });
                            }
                            if (result.isDenied) {
                                window.location.href = "{{route('bitacoras.restaurar',['id'=> $temp])}}";
                            }
                        });


                    }

                });
            });
        </script>
        @endif
        </body>

        @endsection
