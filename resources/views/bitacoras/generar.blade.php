@extends('adminlte::page')

@section('title', 'Bitácoras')

@section('content_header')
    <h1>Bitácoras</h1>
@endsection

@section('content')
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_diaria" value="{{ route('bitacoras.diaria') }}">
    <script src="{{asset('js/bitacora/generar.js')}}"></script>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <!--     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
     -->
        <link rel="stylesheet" href="{{asset('css/bitacoras/generar.css')}}">
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
        <title>Subir Archivos</title>
    </head>

    <body class="body">
    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>
    <div class="container mt-5">
        <div class="row">
            @unlessrole('Supervisor')
            <div class="shadow-container">
                <h2 class="text-center mb-4">Bitacora Todos</h2>
                <small class="text-muted">(No suma producción)</small>
                <br>
                <br>
                <label for="archivo" class="form-label">Seleccione Bitacora:</label>
                <input class="form-control mb-3" type="file" name="archivo" id="archivo_diaria">
                <div class="button-container">
                    <button class="btn btn-primary" id="btnProcesar" type="submit">Procesar</button>
                </div>
                <br>
                <div class="row" id="message"></div>
            </div>
            @endunlessrole
            <div class="shadow-container">


                <h2 class="text-center mb-4">Subir Archivos</h2>
                @role('Supervisor')
                <form action="{{route('bitacoras.generar')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <select class="form-control form-select-lg mb-3" name="supervisor" id="supervisor" disabled>
                        <option value="{{$supervisores->id}}" selected>{{$supervisores->name}}</option>
                    </select>
                    <input type="hidden" name="supervisor" value="{{$supervisores->id}}">
                    @error('supervisor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <label for="archivo" class="form-label">Seleccione Bitacora:</label>

                    <input class="form-control mb-3" type="file" name="archivo" id="archivo">
                    @error('archivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="button-container">
                        <button class="btn btn-primary" type="submit">Subir Archivo</button>
                    </div>

                </form>
                @endrole
                @unlessrole('Supervisor')
                <form action="{{route('bitacoras.generar')}}" method="POST" enctype="multipart/form-data">
                    <select class="form-control form-select-lg mb-3" name="supervisor" id="supervisor">
                        <option value="">Seleccione Supervisor</option>
                        <option value="0">Cierre</option>
                        @foreach ($supervisores as $supervisor)

                            <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                        @endforeach
                    </select>

                    @error('supervisor')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <label for="archivo" class="form-label">Seleccione Bitacora:</label>
                    @csrf
                    <input class="form-control mb-3" type="file" name="archivo" id="archivo">
                    @error('archivo')
                    <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="button-container">
                        <button class="btn btn-primary" type="submit">Subir Archivo</button>

                </form>
                @endunlessrole

            </div>
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
