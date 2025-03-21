@extends('adminlte::page')

@section('title', 'Zonificación')

@section('content_header')
    <h1>Zonificación</h1>
@endsection

@section('content')
    <script src="{{ asset('js/Zonas/zonas.js') }}"></script>
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <div class="card">
        {{-- Tarjeta Municipios --}}
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
                            <tr data-id="{{$municipio->id}}">
                                <td>{{ $municipio->nombre }}</td>
                                <td>{{ $municipio->sede->nombre }}</td>
                                <td>{{ $municipio->zona->nombre }}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirMunicipioModal" data-municipio-id="{{ $municipio->id }}">Editar</button>
                                        @if ($municipio->status == 1)
                                            <button class="btn btn-danger btn-sm" id="btnChangeStatusMunicipio" data-municipio-id="{{ $municipio->id }}">Desactivar</button>
                                        @else
                                            <button class="btn btn-success btn-sm" id="btnChangeStatusMunicipio" data-municipio-id="{{ $municipio->id }}">Activar</button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <input type="hidden" id="cambiarEstadoMunicipio" value="{{route('zonas.changeStatusMunicipio')}}">
                </div>
            </div>
        </div>

        {{-- Tarjeta Barrios --}}
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Barrios</h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-primary mb-2" id="btnCrearBarrio">Crear Barrio</a>
                    <table class="table table-striped" id="Barrios">
                        <thead>
                        <tr>
                            <th>Nombre</th>
                            <th>Municipio</th>
                            <th>Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($barrios as $barrio)
                            <tr data-id="{{$barrio->id}}">
                                <td>{{ $barrio->barrio }}</td>
                                <td>
                                    {{ $barrio->municipios ? ($barrio->municipios->first() ? $barrio->municipios->first()->nombre : "N/A") : "N/A" }}
                                </td>

                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirMunicipioModal" data-municipio-id="{{ $barrio->id }}">Editar</button>

                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <input type="hidden" id="cambiarEstadoMunicipio" value="{{route('zonas.changeStatusMunicipio')}}">
                </div>
            </div>
        </div>
    </div>


    {{-- Modal para crear Municipio --}}
    <div class="modal fade" id="municipioModal" tabindex="-1" role="dialog" aria-labelledby="crearMunicipioModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearMunicipioModalLabel">Ingresar Municipio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombreMunicipio" name="nombre">
                        <input type="hidden" id="idGuardarMunicipio">
                    </div>
                    <div class="form-group">
                        <label for="sede">Sede</label>
                        <select class="form-control" name="sede" id="sedeMunicipio">
                            <option value="">Seleccione una sede</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="zona">Zona</label>
                        <select class="form-control" name="zona" id="zonaMunicipio">
                            <option value="">Seleccione una zona</option>
                            @foreach ($zonas as $zona)
                                <option value="{{ $zona->id }}">{{ $zona->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="crearMunicipio" class="btn btn-primary">Crear Municipio</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal Barrios --}}

    <div class="modal fade" id="BarrioModal" tabindex="-1" role="dialog" aria-labelledby="crearBarrioModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearBarrioModalLabel">Ingresar Barrio</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="nombre">Nombre</label>
                        <input type="text" class="form-control" id="nombreBarrio" name="nombre">
                        <input type="hidden" id="idGuardarBarrio">
                    </div>
                    <div class="form-group">
                        <label for="sede">Sede</label>
                        <select class="form-control" name="sede" id="sedeBarrio">
                            <option value="">Seleccione una sede</option>
                            @foreach ($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="zona">Zona</label>

                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="crearBarrio" class="btn btn-primary">Crear Municipio</button>
                </div>
            </div>
        </div>
    </div>


    @if (session('error'))

        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('error') }}',
            })
        </script>

    @endif

    @if(session('warning'))

        <script>
            Swal.fire({
                icon: 'warning',
                title: 'Oops...',
                text: '{{ session('warning') }}',
            })
        </script>

    @endif

@endsection
