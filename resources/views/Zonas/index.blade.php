@extends('adminlte::page')

@section('title', 'Zonificación')

@section('content_header')
    <h1>Zonificación</h1>
@endsection

@section('content')
    <script src="{{ asset('js/Zonas/zonas.js') }}"></script>
    <script src="{{ asset('js/Zonas/alerts.js') }}"></script>
    <script src="{{ asset('js/Zonas/asignador.js') }}"></script>
    <link rel="stylesheet" href="{{ asset ('css/zonas/zonas.css')}}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <div class="card">
        <div class="row">
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
                                            <button class="btn btn-info btn-sm abrirBarrioModal" data-barrio-id="{{ $barrio->id }}">Editar</button>

                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tarjeta Grupos --}}

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Grupos</h3>
                    </div>
                    <div class="card-body">
                        <a class="btn btn-primary mb-2" id="btnCrearGrupo">Crear Grupo</a>
                        <table class="table table-striped" id="grupos">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Sede</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($grupos as $grupo)
                                    <tr data-id="{{ $grupo->id }}">
                                        <td>{{ $grupo->grupo }}</td>
                                        <td>{{ $grupo->id_sede }}</td>
                                        <td>
                                            <div style="display: flex; gap: 5px; justify-content: center;">
                                                <button class="btn btn-info btn-sm abrirGrupoModal" data-grupo-id="{{ $grupo->id }}">Editar</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- Tarjeta Sub Grupos --}}

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Sub Grupos</h3>
                    </div>
                    <div class="card-body">
                        <a class="btn btn-primary mb-2" id="btnCrearSubGrupo">Crear Sub Grupo</a>
                        <table class="table table-striped" id="subgrupos">
                            <thead>
                                <tr>
                                    <th>Nombre</th>
                                    <th>Sede</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($subgrupos as $subgrupo)
                                    <tr data-id="{{ $subgrupo->id }}">
                                        <td>{{ $subgrupo->subgrupo }}</td>
                                        <td>{{ $subgrupo->id_sede }}</td>
                                        <td>
                                            <div style="display: flex; gap: 5px; justify-content: center;">
                                                <button class="btn btn-info btn-sm abrirSubGrupoModal" data-subgrupo-id="{{ $subgrupo->id }}">Editar</button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
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

    {{-- Modal para crear Barrios --}}

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
                        <input type="text" class="form-control" id="barrio" name="nombre">
                        <input type="hidden" id="idGuardarBarrio">
                    </div>
                    <div class="form-group">
                        <label for="municipio">Municipio</label>
                        <select class="form-control" id="municipioBarrio" name="municipio">
                            <option value="">Seleccione un municipio</option>
                            @foreach ($municipios as $municipio)
                                <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" id="crearBarrio" class="btn btn-primary">Crear Municipio</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal para crear Grupos --}}

    <div class="modal fade" id="GrupoModal" tabindex="-1" aria-labelledby="crearGrupoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="crearGrupoModalLabel">Crear Grupo</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idGuardarGrupo">
                    <div class="form-group">
                        <label for="grupo">Nombre</label>
                        <input type="text" class="form-control" id="grupo" placeholder="Ingrese el nombre del grupo">
                    </div>
                    <div class="form-group">
                        <label for="sedeGrupo">Sede</label>
                        <select id="sedeGrupo" class="form-control">
                            <option value="">Seleccione una sede</option>
                            @foreach($sedes as $sede)
                                <option value="{{ $sede->id }}">{{ $sede->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="button" class="btn btn-primary" id="crearGrupo">Crear Grupo</button>
                </div>
            </div>
        </div>
    </div>

        {{-- Modal de asignacion de grupos --}}
        <div class="modal fade" id="AsignadorModal" aria-labelledby="asignadorModalLabel" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="asignadorModalLabel">Asignador</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <div id="selectores-container">
                        </div>
                    </div>
                    <div class="modal-footer">
                    </div>
                </div>
            </div>
        </div>

    @if (session('error'))
        <script>
            Swal.fire({
                icon: 'error',
                title: 'Oops...',
                text: '{{ session('warning') }}',
            })
        </script>
    @endif

    @if(session('warning'))
        <script>
            let mun_sin_grupo = '{{json_encode($mun_sin_grupo)}}';
            let municipios = '{{json_encode($municipios->toArray())}}';
            let barrios = '{{json_encode($barrios->toArray())}}';
            let grupos = '{{json_encode($grupos->toArray())}}';
            let subgrupos = '{{json_encode($subgrupos->toArray())}}';

            let warning = '{{ session('warning') }}';
        </script>
    @else
            <script>
                let warning = '';
            </script>
    @endif
@endsection
