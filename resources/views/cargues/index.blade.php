@extends('adminlte::page')

@section('title', 'Asignadas')

@section('content_header')
<h1>Asignadas y cerradas</h1>
@endsection


@section('content')
<link rel="stylesheet" href="{{asset('css/asignadas.css')}}">
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6">
            <div class="shadow-container">
                <form action="{{route('cargues.store')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="archivo" class="form-label">Asignadas OSF</label>

                    <input class="form-control mb-3" type="file" name="archivo" id="archivo" required>

                    <div class="button-container">
                        <button class="btn btn-primary" type="submit">Subir Archivo</button>
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-6">
            <div class="shadow-container">
                <form action="{{route('cargues.storeClosed')}}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <label for="fileClosed" class="form-label">Cerradas</label>
                    <input class="form-control mb-3" type="file" name="archivo" id="fileClosed" required>
                    <div class="button-container">
                        <button class="btn btn-primary" type="submit">Subir Archivo</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
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
            icon: "success",
            title: "{{ session('success') }}",
            showConfirmButton: false,
            toast: true,
            timer: 3000
        });
    });
</script>
@endif
@endsection