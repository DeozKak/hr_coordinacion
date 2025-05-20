@extends('adminlte::page')

@section('title', 'Zonificación')

@section('content_header')
    <h1>Zonificación</h1>
@endsection

@section('content')

    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <script src="{{ asset('js/Zonas/zonasV1.2.js') }}"></script>
    <script src="{{ asset('js/Zonas/alerts.js') }}"></script>
    <script src="{{ asset('js/Zonas/asignador.js') }}"></script>
    <script src="{{ asset('js/Zonas/buscadorV1.2.js') }}"></script>
    <script src="{{ asset('js/Zonas/asigResponsablesV1.1.js') }}"></script>
    <script src="{{ asset('js/Zonas/insercionMasiva.js') }}"></script>

    <link rel="stylesheet" href="{{ asset ('css/zonas/zonas.css')}}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <input type="hidden" id="url_asignarBarrio" value="{{ route('zonas.asignarBarrio') }}">
    <input type="hidden" id="url_masivo" value="{{ route('zonas.recepcionMasiva') }}">
    <div class="row">

        {{-- Tarjeta Sedes y Zonas --}}

        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Sedes y Zonas</h3>
                </div>
                <div class="card-body text-left">
                    <button class="btn btn-primary" data-toggle="modal" data-target="#extraCardsModal">
                        Gestionar Sedes y Zonas
                    </button>
                </div>
            </div>
        </div>

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
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Sede</th>
                            <th>Zona</th>
                            <th class="text-center">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach ($municipios as $municipio)
                            <tr data-id="{{$municipio->id}}">
                                <td>{{ $municipio->id }}</td>
                                <td>{{ $municipio->nombre }}</td>
                                <td>{{ $municipio->sede->nombre }}</td>
                                <td>{{ $municipio->zona->nombre }}</td>
                                <td>
                                    <div style="display: flex; gap: 5px; justify-content: center;">
                                        <button class="btn btn-info btn-sm abrirMunicipioModal"
                                                data-municipio-id="{{ $municipio->id }}">Editar
                                        </button>
                                        @if ($municipio->status == 1)
                                            <button class="btn btn-danger btn-sm" id="btnChangeStatusMunicipio"
                                                    data-municipio-id="{{ $municipio->id }}">Desactivar
                                            </button>
                                        @else
                                            <button class="btn btn-success btn-sm" id="btnChangeStatusMunicipio"
                                                    data-municipio-id="{{ $municipio->id }}">Activar
                                            </button>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    <input type="hidden" id="cambiarEstadoTabla" value="{{route('zonas.changeStatusTable')}}">
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
                            <th class="text-center">Acciones</th>
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
                                        <button class="btn btn-info btn-sm abrirBarrioModal"
                                                data-barrio-id="{{ $barrio->id }}">Editar
                                        </button>

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
                             <th class="text-center">Acciones</th>
                         </tr>
                         </thead>
                         <tbody>
                         @foreach ($grupos as $grupo)
                             <tr data-id="{{ $grupo->id }}">
                                 <td>{{ $grupo->grupo }}</td>
                                 <td>{{ $grupo->sede->nombre }}</td>
                                 <td>
                                     <div style="display: flex; gap: 5px; justify-content: center;">
                                         <button class="btn btn-info btn-sm abrirGrupoModal"
                                                 data-grupo-id="{{ $grupo->id }}">Editar
                                         </button>
                                         @if ($grupo->status == 1)
                                             <button class="btn btn-danger btn-sm" id="btnChangeStatusGrupo"
                                                    data-grupo-id="{{ $grupo->id }}">Desactivar
                                             </button>
                                         @else
                                             <button class="btn btn-success btn-sm" id="btnChangeStatusGrupo"
                                                    data-grupo-id="{{ $grupo->id }}">Activar
                                             </button>
                                         @endif
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
                             <th class="text-center">Acciones</th>
                         </tr>
                         </thead>
                         <tbody>
                         @foreach ($subgrupos as $subgrupo)
                             <tr data-id="{{ $subgrupo->id }}">
                                 <td>{{ $subgrupo->subgrupo }}</td>
                                 <td>{{ $subgrupo->sede->nombre }}</td>
                                 <td>
                                     <div style="display: flex; gap: 5px; justify-content: center;">
                                         <button class="btn btn-info btn-sm abrirSubGrupoModal"
                                                 data-subgrupo-id="{{ $subgrupo->id }}">Editar
                                         </button>
                                         @if ($subgrupo->status == 1)
                                             <button class="btn btn-danger btn-sm" id="btnChangeStatusSubgrupo"
                                                     data-subgrupo-id="{{ $subgrupo->id }}">Desactivar
                                             </button>
                                         @else
                                             <button class="btn btn-success btn-sm" id="btnChangeStatusSubgrupo"
                                                     data-subgrupo-id="{{ $subgrupo->id }}">Activar
                                             </button>
                                         @endif
                                        {{-- <button class="btn btn-success btn-sm btnAbrirResponsables"
                                                 data-subgrupo-id="{{ $subgrupo->id }}">
                                             Insp Asignados
                                         </button>--}}
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


        {{-- Contenedor de Barras de Búsqueda y Tabla --}}
        <div class="col-md-12">
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Buscar Relaciones</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary" id="btn-masivo" style="margin-right: 50px">Inserción masiva</button>
                        <button type="button" class="btn btn-secondary" id="btn-asignacion" style="margin-right: 25px" >Gestionar Asignación</button>

                        <button type="button" class="btn btn-tool" data-card-widget="collapse"><i
                                class="fas fa-minus"></i>
                        </button>

                    </div>
                </div>
                <div class="card-body">

                    {{-- Barras de Búsqueda --}}

                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control" id="buscarMunicipio">
                                <option value="">Seleccione un municipio</option>
                                @foreach ($municipios as $municipio)
                                    <option value="{{ $municipio->id }}">{{ $municipio->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="buscarGrupo">
                                <option value="">Seleccione un grupo</option>
                                @foreach ($grupos as $grupo)
                                    <option value="{{ $grupo->id }}">{{ $grupo->grupo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="buscarSubGrupo">
                                <option value="">Seleccione un sub grupo</option>
                                @foreach ($subgrupos as $subgrupo)
                                    <option value="{{ $subgrupo->id }}">{{ $subgrupo->subgrupo }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-control" id="buscarBarrio">
                                <option value="">Seleccione un barrio</option>
                                @foreach ($barrios as $barrio)
                                    <option value="{{ $barrio->id }}">{{ $barrio->barrio }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <br>
                    <div class="row" id="message"></div>
                    <div class="text-center mt-3">
                        <button class="btn btn-primary" id="btnBuscar">Buscar</button>
                    </div>

                    {{-- Tabla --}}

                    <div id="table" class="mt-4" style="display: none;"></div>
                    <br>
                    <br>
                </div>
            </div>
        </div>

        @include('zonas.partials.index_modals')


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
                let warning = '{{ session('warning') }}';
            </script>
        @else
            <script>
                let warning = '';
            </script>
    @endif
@endsection
