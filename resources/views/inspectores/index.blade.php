@extends('adminlte::page')

@section('title', 'Inspectores')

@section('content_header')
    <h1>Inspectores</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/admin/indexV1.css')}}">
    <input type="hidden" id="urlGetData" value="{{ route('inspector.getData')}}">
    <input type="hidden" id="tokenGetData" value="{{csrf_token()}}">
    <div class="shadow-container">
        <div class="controls-header">
            <button class="btn-gradient btn-gradient-primary modalDesactivados" data-url="{{route('inspectores.show_disabled')}}">
                <i class="fas fa-user-slash"></i> Ver desactivados
            </button>
            <button class="btn-gradient btn-gradient-success modalCrearInspector">
                <i class="fas fa-plus"></i> Nuevo Inspector
            </button>
        </div>

        <div class="table-responsive">
            <table id="table_users" class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombres</th>
                    <th>Apellidos</th>
                    <th>Tipo ID</th>
                    <th>Cédula</th>
                    <th>Supervisor</th>
                    <th>Aprendiz</th>
                    <th>Estado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($inspectores as $inspector)
                    <tr data-id="{{$inspector->id}}">
                        <td data-id="{{$inspector->id}}">{{$inspector->id}}</td>
                        <td data-id="{{$inspector->id}}">{{$inspector->nombres}}</td>
                        <td>{{$inspector->apellidos}}</td>
                        <td>{{$inspector->type_id}}</td>
                        <td>{{$inspector->cedula}}</td>
                        <td>{{$inspector->supervisor->name}}</td>
                        <td>
                            @if($inspector->aprendiz)
                                <span class="badge-modern badge-warning-modern">SI</span>
                            @else
                                <span class="badge-modern badge-secondary-modern">NO</span>
                            @endif
                        </td>
                        <td>
                            @if ($inspector->state)
                                <span class="badge-modern badge-success-modern">Activo</span>
                            @else
                                <span class="badge-modern badge-danger-modern">Inactivo</span>
                            @endif
                        </td>
                        <td>
                            <div class="btn-group" role="group">
                                <button class="btn-gradient btn-gradient-warning btn-sm modalEditarInspector" style="margin-right: 10px">Editar</button>
                                <button type="button" data-url="{{route('inspectores.change_state', ['inspector' => $inspector->id])}}" class="btn-gradient btn-gradient-danger btn-sm change_state">Desactivar</button>
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @include('inspectores.modales.modales')

    @section('js')
        <script src="{{asset('js/inspectores/inspectoresV1.2.js')}}"></script>
        <script>
            let changeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
            let activeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
        </script>
    @endsection
@endsection
