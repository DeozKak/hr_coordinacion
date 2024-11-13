@extends('adminlte::page')

@section('title', 'Recepcion')

@section('content_header')
<h1>Recepcion</h1>
@endsection
@section('content')
<link rel="stylesheet" href="{{asset('css/carga/loadReception.css') }}?v={{ time() }}">
<div class="container mt-5">
    <div class="row">
        <div class="col-md-6 mx-auto">
            <div class="card mt-3">
                <div class="card-body">
                    <h5 class="card-title mb-3">Carga de Recepcion</h5>
                    <form action="{{route('load.receptionStore')}}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input class="form-control mb-3" type="file" name="archivo" id="archivo" required>
                        <div class="button-container">
                            <button class="btn btn-primary" type="submit">Subir Archivo</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

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
@section('js')
<script src="{{ asset('js/carga/loadReception.js') }}?v={{ time() }}"></script>
@endsection