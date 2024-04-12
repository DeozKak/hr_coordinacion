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

        </form>

    </div>
    <br>

    @if (isset($error))
    <div class="alert alert-danger">
        {{ $error }}
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