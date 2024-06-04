@extends('adminlte::page')

@section('title', 'Información General')

@section('content_header')
<h1>Información General</h1>
@stop

@section('content')
<input type="hidden" id="token" value="{{csrf_token()}}">
<script src="{{asset('js/informacion_general.js')}}"></script>
<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Cortes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearCorte">Crear Corte</a>
                <table class="table table-striped" id="cortes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Fecha Inicio</th>
                            <th>Fecha Fin</th>
                            <th>Meta</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($cortes as $corte)
                        <tr>
                            <td>{{ $corte->nombre }}</td>
                            <td>{{ $corte->fecha_inicio }}</td>
                            <td>{{ $corte->fecha_fin }}</td>
                            <td>{{ $corte->meta }}</td>
                            <td>
                                <a class="btn btn-success btn-sm" data-corte-id="{{ $corte->id }}">Editar</a>
                                <a class="btn btn-primary btn-sm" data-corte-id="{{ $corte->id }}" id="btndetallesCorte">Detalles</a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Municipios</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearMunicipio">Crear Municipio</a>
                <table class="table table-striped" id="municipios">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Sede</th>
                            <th>Zona</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($municipios as $municipio)
                        <tr>
                            <td>{{ $municipio->nombre }}</td>
                            <td>{{ $municipio->sede->nombre }}</td>
                            <td>{{ $municipio->zona->nombre }}</td>
                            <td>
                                <div style="display: flex; gap: 5px; justify-content: center;">
                                    <a class="btn btn-success btn-sm" data-municipio-id="{{ $municipio->id }}">Editar</a>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Sedes</h3>
            </div>
            <div class="card-body">
                <a class="btn btn-primary mb-2" id="btnCrearSede">Crear Sede</a>
                
                <table class="table table-striped" id="sedes">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($sedes as $sede)
                        <tr>
                            <td>{{ $sede->nombre }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Zonas</h3>
            </div>
            <div class="card-body">
                <table class="table table-striped" id="zonas">
                    <thead>
                        <tr>
                            <th>Nombre</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($zonas as $zona)
                        <tr>
                            <td>{{ $zona->nombre }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</div>




{{-- Modal para crear Corte --}}
<div class="modal fade" id="CorteModal" tabindex="-1" aria-labelledby="crearCorteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearCorteModalLabel">Crear Corte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">


                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombre" name="nombre">
                </div>
                <div class="form-group">
                    <label for="fecha_inicio">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio">
                </div>
                <div class="form-group">
                    <label for="fecha_fin">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin">
                </div>
                <div class="form-group">
                    <label for="meta">Meta</label>
                    <input type="text" class="form-control" id="meta" name="meta">
                </div>


            </div>
            <div class="modal-footer">
                <button type="submit" id="crear" class="btn btn-primary">Crear</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear Municipio --}}
<div class="modal fade" id="MunicipioModal" tabindex="-1" aria-labelledby="crearMunicipioModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearSedeModalLabel">Ingresar Municipio</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombreMunicipio" name="nombre">
                </div>
                <div class="form-group">
                    <label for="sede">Sede</label>
                    <select class="form-control" name="sede" id="sede">
                        <option value="">Seleccione una sede</option>
                        @foreach ($sedes as $sede)
                        <option value="{{$sede->id}}">{{$sede->nombre}}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label for="zona">Zona</label>
                    <select class="form-control" name="zona" id="zona">
                        <option value="">Seleccione una zona</option>
                        @foreach ($zonas as $zona)
                        <option value="{{$zona->id}}">{{$zona->nombre}}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="crearMunicipio" class="btn btn-primary">Crear</button>
            </div>
        </div>
    </div>
</div>

{{-- Modal para crear sede --}}
<div class="modal fade" id="SedeModal" tabindex="-1" aria-labelledby="crearSedeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="crearSedeModalLabel">Ingresar Sede</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-group">
                    <label for="nombre">Nombre</label>
                    <input type="text" class="form-control" id="nombreSede" name="nombre">
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" id="crearSede" class="btn btn-primary">Crear</button>
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
            timer: 4000
        });
    });
</script>
@endif
<script>
    const rangosFechasExistentes = @json($cortes);
</script>
@stop