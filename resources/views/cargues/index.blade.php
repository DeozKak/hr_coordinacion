@extends('adminlte::page')

@section('title', 'Asignadas')

@section('content_header')
<h1>Asignadas y cerradas</h1>
@endsection


@section('content')
<link rel="stylesheet" href="{{asset('css/asignadas.css')}}">
<div class="container mt-5">
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
    <br>

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
@section('js')
<script>

$(document).ready(function() {

$('#table').DataTable({
    "language": {
        "lengthMenu": "Mostrar _MENU_ registros por página",
        "zeroRecords": "Nada encontrado - lo siento",
        "info": "Mostrando la página _PAGE_ de _PAGES_",
        "infoEmpty": "No hay registros disponibles",
        "infoFiltered": "(Filtrado de _MAX_ registros totales)",
        "search": "Buscar:",
        "paginate": {
            "first": "Primero",
            "last": "Ultimo",
            "next": "Siguiente",
            "previous": "Anterior"
        }
    }

});
});
    </script>
@endsection
    @endsection