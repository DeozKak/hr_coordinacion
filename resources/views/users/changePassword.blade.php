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
                            <input type="password" name="new_password" id="claveNueva" class="form-control" required>
                            <i class='fa fa-eye' id='togglePassword' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>
                        </div>
                        <div class="form-group">
                            <label for="name">Confirmar Contraseña</label>
                            <input type="password" name="conf_password" id="claveConfirmar" class="form-control" required>
                            <i class='fa fa-eye' id='togglePasswordConfirm' style='position: absolute; right: 5%;  transform: translateY(-155%); cursor: pointer;'></i>
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

document.addEventListener("DOMContentLoaded", function() {
    document.getElementById('togglePassword').addEventListener('click', function(){
        const passwordField = document.getElementById('claveNueva')
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    })

    document.getElementById('togglePasswordConfirm').addEventListener('click', function(){
        const passwordField = document.getElementById('claveConfirmar')
        const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
        passwordField.setAttribute('type', type);
        this.classList.toggle('fa-eye');
        this.classList.toggle('fa-eye-slash');
    })
});

    </script>
@endsection

@endsection
