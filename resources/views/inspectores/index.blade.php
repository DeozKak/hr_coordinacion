@extends('adminlte::page')

@section('title', 'Inspectores')

@section('content_header')
    <h1>Inspectores</h1>
@endsection

@section('content')
    <link rel="stylesheet" href="{{asset('css/admin/index.css')}}">

    <div class="card">
        <div class="card-body">
            <!-- Botones para abrir los modales -->
            <button class="btn btn-success mb-2 modalCrearInspector">Nuevo Inspector</button>
            <button data-url="{{route('inspectores.show_disabled')}}" class="btn btn-primary mb-2 modalDesactivados">Ver
                desactivados</button>

            <div class="table-responsive">
                <!-- Tabla de Inspectores -->
                <table id="table_users" class="table table-sm">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombres</th>
                            <th>Apellidos</th>
                            <th>Tipo de identificación</th>
                            <th>Cedula</th>
                            <th>Supervisor</th>
                            <th>Aprendiz</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tableBody">
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
                                        <span class="badge bg-warning text-dark">SI</span>
                                    @else
                                        <span class="badge bg-secondary">NO</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($inspector->state)
                                        <span class="badge badge-success">Activo</span>
                                    @else
                                        <span class="badge badge-danger">Inactivo</span>
                                    @endif
                                </td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Botones">
                                        <!-- Botón para abrir el modal de edición -->
                                        <button class="btn btn-warning modalEditarInspector">Editar</button>

                                        <form class="d-inline">
                                            <button type="button"
                                                data-url="{{route('inspectores.change_state', ['inspector' => $inspector->id])}}"
                                                class="btn btn-danger change_state">Desactivar</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <input type="hidden" id="urlGetData" value="{{ route('inspector.getData')}}">
                <input type="hidden" id="tokenGetData" value="{{csrf_token()}}">
            </div>
        </div>
    </div>

    @include('inspectores.modales.modales')

    @section('js')
        <script src="{{asset('js/inspectores/inspectoresV1.1.js')}}"></script>
        <script>
            let changeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
            let activeStateUrl = "{{ route('inspectores.change_state', ['inspector' => '__ID__']) }}";
        </script>
    @endsection
@endsection
