@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
    <h1>Programación</h1>
@stop


@section( 'content')

    <body>
    <link rel="stylesheet" href="{{asset('css/programacion/index.css')}}">
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <input type="hidden" name="url_base" id="url_base" value="{{ route('programacion.base') }}">
    <input type="hidden" name="url_masivo" id="url_masivo" value="{{ route('programacion.masivos') }}">
    <input type="hidden" name="url_buscar" id="url_buscar" value="{{ route('programacion.buscar_por_contrato') }}">
    <input type="hidden" name="url_ver" id="url_ver"
           value="{{ route('programacion.show', ['id' => ':id'])}}'?action=view">

    <input type="hidden" name="url_callcenterGDO" id="url_callcenterGDO"
           value="{{ route('programacion.callCenterGDO')}}">


    <div class="shadow-container">
        <div class="controls-header">
            <div class="actions-group">
                @haspermission('ver_programacion')
                <button id="openModalBtn" class="btn-gradient btn-gradient-primary btn-sm">Añadir a Base</button>
                <button id="openMasivoBtn" class="btn-gradient btn-gradient-primary btn-sm">Programadas Tecnicos
                </button>
                <button id="opencalcenterGDOBtn" class="btn-gradient btn-secondary-modern btn-sm">Programadas GDO
                </button>
                @endhaspermission
            </div>
            <div class="actions-group">
                <div class="search-group">
                    <input type="text" class="form-control form-control-sm" id="buscadorContrato"
                           placeholder="Buscar contrato...">
                </div>
                <a href="{{ route('programacion.create') }}" class="btn-gradient btn-gradient-success btn-sm">
                    <i class="fa fa-plus"></i> Generar nueva Tabla
                </a>
            </div>
        </div>

        <div id="resultadosBusqueda" class="search-results-modern"></div>

        <div class="table-responsive">
            <table id="programacion" class="table">
                <thead>
                <tr>
                    <th>ID</th>
                    <th>Usuario</th>
                    <th>Tipo de programación</th>
                    <th>Creado</th>
                    <th>Acciones</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($datos as $dato)
                        <?php
                        $fecha = explode(" ", $dato->created_at)[0];
                        $nombre = explode(" ", $dato->nombre);

                        if ($nombre[1] == "tecnicos") {
                            $tipo_cargue = $nombre[0] . " " . $nombre[1];

                        } elseif ($nombre[1] == "GDO") {
                            $tipo_cargue = $nombre[0] . " " . $nombre[1];
                        } else {
                            $tipo_cargue = "";
                        }


                        ?>
                    <tr>
                        <td>{{$dato->id}}</td>
                        <td>{{$dato->usuario->name}}</td>
                        <td>{{$tipo_cargue}}</td>
                        <td>{{$fecha}}</td>
                        <td>
                            @haspermission('ver_programacion')
                            <a href="{{ route('programacion.show', $dato->id)}}'?action=edit"
                               title="show">
                                <button type="button" class="btn-gradient btn-gradient-warning btn-sm">Editar</button>
                            </a>
                            @endhaspermission
                            <a href="{{ route('programacion.show', $dato->id)}}'?action=view"
                               title="show">
                                <button type="button" class="btn-gradient btn-gradient-success btn-sm">Ver</button>
                            </a>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>


    <!-- Modal base -->
    <div class="modal fade modal-modern" id="addProgramacionModal" tabindex="-1"
         aria-labelledby="addProgramacionModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">

                    <h5 class="modal-title" id="addProgramacionModalLabel">
                        <i class="fas fa-file-upload text-primary"></i>
                        <span>Añadir a Base</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="programacionForm" enctype="multipart/form-data" method="POST"
                          action="{{ route('programacion.base') }}">
                        @csrf
                        <div class="mb-3">
                            <input type="hidden" name="type" id="type" value="base">
                            <label for="archivo" class="form-label">Archivo:</label>
                            <input type="file" class="form-control" id="archivo" name="archivo">
                            <br>
                            <div id="loader" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="visually-hidden">Cargando...</span>
                            </div>
                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1rem 0 0 0;">
                            <button id="submit-programacion" type="submit" class="btn-gradient btn-gradient-primary">
                                Subir
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal programación prioridades -->

    <div class="modal fade modal-modern" id="addMasivoModal" tabindex="-1" aria-labelledby="addMasivoModalLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addMasivoModalLabel">
                        <i class="fas fa-file-upload text-primary"></i>
                        <span>Programadas tecnicos</span>
                    </h5>
                    <button type="button" class="btn-close masivoModal" data-bs-dismiss="modal"
                            aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="masivoForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="hidden" name="type" id="type" value="programacion_tec">
                            <label for="archivo">Selecciona un archivo:</label>

                            <input class="form-control mb-3" type="file" name="archivo" id="archivo">

                            <div id="loaderMasivo" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="visually-hidden">Cargando...</span>
                            </div>

                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1rem 0 0 0;">
                            <button id="submit-masivo" class="btn-gradient btn-gradient-primary" type="submit">Cargar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{--<!-- Programadas GDO -->
    <div class="modal fade modal-modern" id="addGDO" tabindex="-1" aria-labelledby="addGDOLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGDOLabel">Programadas GDO</h5>
                    <button type="button" class="GDO" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="GDOForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="archivo">Selecciona un archivo:</label>

                            <input class="form-control mb-3" type="file" name="archivo" id="archivo">

                            <div id="loaderGDO" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="visually-hidden">Cargando...</span>
                            </div>

                        </div>
                        <button id="submit-GDO" class="btn btn-primary" type="submit">Cargar</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </div>
--}}
    <!-- Call center GDO -->
    <div class="modal fade modal-modern" id="callcenterGDO" tabindex="-1" aria-labelledby="callcenterGDOLabel"
         aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="callcenterGDOLabel">
                        {{-- Título actualizado con ícono --}}
                        <i class="fas fa-file-upload text-primary"></i>
                        <span>Programadas GDO</span>
                    </h5>
                    <button type="button" class="callcenterGDO btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form action="#" id="callcenterGDOForm" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="hidden" name="type" id="type" value="gdo">
                            <label for="archivo">Selecciona un archivo:</label>

                            <input class="form-control mb-3" type="file" name="archivo" id="archivo">

                            <div id="loaderCallcenterGDO" style="display: none;">
                                <div class="spinner-border text-primary" role="status"></div>
                                <span class="visually-hidden">Cargando...</span>
                            </div>

                        </div>
                        <div class="modal-footer" style="border-top: none; padding: 1rem 0 0 0;">
                            {{-- Botón de Cargar actualizado con el estilo de gradiente --}}
                            <button id="submit-callcenterGDO" class="btn-gradient btn-gradient-primary" type="submit">
                                Cargar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    </body>
    @section('js')
        <script src="{{ asset('js/programacion/indexProgramacionV3-8.js') }}" type="text/javascript"></script>


        @if (session('success'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "{{ session('success') }}",
                        showConfirmButton: false,
                        toast: true,
                        timer: 4000
                    });
                });
            </script>
        @endif
        @if (session('error'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        icon: "error",
                        title: "Error",
                        text: "{{session('error')}}",
                    });
                });
            </script>
        @endif
        @if (session('warning'))
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    Swal.fire({
                        title: "{{session('warning')}}",
                        showDenyButton: true,
                        showCancelButton: true,
                        allowOutsideClick: false,
                        confirmButtonText: "Si",
                        denyButtonText: "No",
                        cancelButtonText: "Cancelar",
                    }).then((result) => {

                        if (result.value) {
                            window.location.href = "{{ route('programacion.show',['id' => $temp->id]) }}?action=edit";
                        }
                        if (result.isDenied) {
                            swal.fire({
                                icon: "warning",
                                title: "Se perderán los cambios!",
                                allowOutsideClick: false,
                                showDenyButton: true,
                                confirmButtonText: "Quiero generar una tabla nueva",
                                denyButtonText: "Mantener cambios",
                            }).then((result) => {

                                if (result.isConfirmed) {
                                    $.ajax({
                                        type: "DELETE",
                                        dataType: "json",
                                        data: {
                                            _token: "{{ csrf_token() }}"
                                        },
                                        url: "{{ route('programacion.erase', ['id' => $temp->id]) }}?action=edit",
                                        success: function (response) {
                                            console.log(response);
                                        },
                                        error: function (xhr, error, status) {

                                            console.log(xhr.responseText);

                                        }

                                    });
                                }
                                if (result.isDenied) {
                                    window.location.href = "{{ route('programacion.show',['id' => $temp->id]) }}?action=edit";
                                }
                            });
                        }

                    });
                });
            </script>
        @endif
    @stop
@endsection
