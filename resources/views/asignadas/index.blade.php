@extends('adminlte::page')

@section('title', 'Asignadas')

@section('content_header')
<h1>Asignadas</h1>
@endsection


@section('content')
<link rel="stylesheet" href="{{asset('css/asignadas.css')}}">
<div class="container mt-5">
    <div class="shadow-container">

        <form action="{{route('asignadas.store')}}" method="POST" enctype="multipart/form-data">
            @csrf
            <label for="archivo" class="form-label">Asignadas OSF</label>

            <input class="form-control mb-3" type="file" name="archivo" id="archivo" required>

            <div class="button-container">
                <button class="btn btn-primary" type="submit">Subir Archivo</button>
            </div>
        </form>
    </div>
</div>
    <br>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Fecha</th>
                <th>Direccion</th>
            </tr>
        </thead>
        <tbody>
        @foreach ($asignadas as $asignado) 
            <tr>
                                      
                <td>{{$asignado->nombre_lugar}}</td>
                <td>{{$asignado->fecha_asignacion}}</td>
                <td>{{$asignado->direccion}}</td>
             
            </tr>
        @endforeach
        </tbody>

    @if (session('error'))
    <div class="alert alert-danger">
        {{ session('error')}}
    </div>
    @endif

    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                position: "top-end",
                type: "success",
                title: "{{ session('success') }}",
                showConfirmButton: false,
                toast: true,
                timer: 3000
            });
        });
    </script>
    @endif

    @endsection