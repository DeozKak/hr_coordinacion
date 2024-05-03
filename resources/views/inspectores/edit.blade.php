@extends('adminlte::page')

@section('title', 'Editar Inspector')

@section('content_header')
<h1>Editar Inspector</h1>
@endsection

@section('content')

<link rel="stylesheet" href="{{asset('css/admin/edit.css')}}">
<div class="container">
    <div class="row">
        <div class="col-sm-6 mx-auto">
            <div class="card">
                <div class="card-body">
                    <form action="{{ route('inspectores.update', ['inspector' => $inspector->id]) }}" method="POST" autocomplete="off">
                        @csrf
                        @method('PUT')
                        <div class="form-group">
                            <label for="nombres">Nombre</label>
                            <input type="text" name="nombres" id="nombres" class="form-control" value="{{$inspector->nombres}}" >
                        
                            @error('nombres')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="apellidos">Apellidos</label>
                            <input type="text" name="apellidos" id="apellidos" class="form-control" value="{{$inspector->apellidos}}" >
           
                            @error('apellidos')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="form-group">
                            <label for="Tipo de identificacion">Tipo de identificación</label>
                            <select name="Tipo de identificacion" id="Tipo de identificacion" class="form-control" disabled>
                                <option value="{{$inspector->type_id}}">{{$inspector->type_id}}</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="identificacion">Identificación</label>
                            <input type="text" name="identification" id="identification" class="form-control" value="{{$inspector->cedula}}" disabled>
                        </div>
                        <div class="form-group">
                            <label for="supervisor">Supervisor</label>
                            <select name="supervisor" id="supervisor" class="form-control">
                                <option value="{{$inspector->supervisor->id}}">{{$inspector->supervisor->name}}</option>
                                @foreach ($supervisores as $supervisor)
                                @if ($supervisor->id == $inspector->supervisor->id)
                                @else
                                <option value="{{$supervisor->id}}">{{$supervisor->name}}</option>
                                @endif
                                @endforeach
                            </select>
                            @error('supervisor')
                            <div class="alert alert-danger">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="button-container">
                            <button type="submit" id="enviar" class="btn btn-primary">Guardar</button>
                    </form>


                    <a class="btn btn-danger" style="margin-right: 10px;" onclick="goBack()">Cancelar</a>



                </div>
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