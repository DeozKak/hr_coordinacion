@extends('adminlte::page')

@section('title', 'Nuevo Inspector')

@section('content_header')
    <h1>Nuevo Inspector</h1>
@endsection

@section('content')

<link rel="stylesheet" href="{{asset('css/admin/edit.css')}}">
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-body">
                <small class="text-muted">Por favor colocar información tal cual está en movilidad con el fin de evitar errores con el cruce de información entre las aplicaciones.</small>
                    <br>
                    <br>
                    <form action="{{ route('inspectores.store') }}" method="POST" autocomplete="off">
                        @csrf
                        <div class="form-group">
                            <label for="nombres">Nombre</label>
                            <input type="text" name="nombres" id="nombres" class="form-control" value="{{old('nombres')}}" >
                            @error('nombres')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group
                        ">
                            <label for="apellidos">Apellidos</label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control" value="{{old('apellidos')}}" >
                            @error('apellidos')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group
                        ">
                            <label for="Tipo de identificacion">Tipo de identificación</label>
                            <select name="type_id" id="type_id" class="form-control">
                                <option value="">Seleccione un tipo de identificación</option>
                                <option value="CC">CC</option>
                                <option value="CE">CE</option>
                            </select>
                            @error('type_id')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group ">
                            <label for="cedula">Identificación</label>
                            <input type="text" name="cedula" id="cedula" class="form-control" value="{{old('cedula')}}" >
                            @error('cedula')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group ">
                            <label for="supervisor">Supervisor</label>
                            <select name="supervisor" id="supervisor" class="form-control">
                                <option value="">Seleccione un supervisor</option>
                                @foreach ($supervisores as $supervisor)
                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                                @endforeach
                            </select>
                            @error('supervisor')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="button-container">
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </form>
                    <a class="btn btn-danger" style="margin-right: 10px;" onclick="goBack()">Cancelar</a>
                </div>
                </div>
            </div>
        </div>
    </div>
</div>
@section('js')
<script>
    function goBack() {
        window.history.back();
    }
</script>
@endsection
@endsection