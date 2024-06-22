@extends('adminlte::page')

@section('title', 'Gestión de Usuarios')

@section('content_header')
<h1>Perfil</h1>
@endsection
@section('content')
<link rel="stylesheet" href="{{asset('css/admin/edit.css')}}">
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-body ">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" name="name" id="name" class="form-control" value="{{$user->name}}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" name="email" id="email" class="form-control" value="{{$user->email}}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="Tipo de identificacion">Tipo de identificación</label>
                        <select name="Tipo de identificacion" id="Tipo de identificacion" class="form-control" disabled>
                            <option value="{{$user->type_id}}">{{$user->type_id}}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="identificacion">Identificación</label>
                        <input type="text" name="identification" id="identification" class="form-control" value="{{$user->identification}}" disabled>
                    </div>

                    <div class="form-group">
                        <label for="roles">Rol</label>
                        <select name="roles" id="roles" class="form-control" disabled>
                            <option value="{{$currentRole->name}}" selected>{{$currentRole->name}}</option>
                        </select>
                    </div>
                    <div class="justify-content-center d-flex">
                    <a href="{{route('profile.edit')}}" class="btn btn-primary " style="margin-right: 10px;">Editar</a>
                    <a href="{{route('home')}}" class="btn btn-danger" style="margin-right: 10px;">Cancelar</a>
                    </div>
                </div>
                
            </div>
        </div>
    </div>
</div>
@endsection