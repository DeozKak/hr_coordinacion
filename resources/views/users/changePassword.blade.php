@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
<h1>Cambiar Contraseña</h1>
@endsection
@section('content')
<link rel="stylesheet" href="{{asset('css/admin/edit.css')}}">
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{route('updatePassword', $user)}}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="name">Nueva Contraseña</label>
                            <input type="password" name="new_password" id="name" class="form-control" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Confirmar Contraseña</label>
                            <input type="password" name="conf_password" id="name" class="form-control" required>
                        </div>

                        <div class="button-container">
                            <button type="submit" id="enviar" class="btn btn-primary">Cambiar</button>
                    </form>
                    <a href="#" id="cancelar" class="btn btn-danger" style="margin-right: 10px;">Cancelar</a>

                </div>
                @if (session('error'))
                <br>
                <div class="alert alert-danger">
                    {{ session('error')}}
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
</div>
</div>
@section('js')
<script>
document.getElementById('cancelar').addEventListener('click', function(event) {
        event.preventDefault(); // Prevenir el comportamiento predeterminado del enlace
        history.back(); // Volver a la página anterior en el historial del navegador
    });

    </script>
@endsection

@endsection