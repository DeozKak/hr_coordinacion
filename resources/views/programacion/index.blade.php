@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Programación</h1>
@stop


@section( 'content')
<style>

        /* En tu archivo CSS (por ejemplo, Reportes.css) */
        .lista-resultados {
            max-height: 200px;
            /* Ajusta la altura máxima según tus necesidades */
            overflow-y: auto;
            /* Habilita el scroll vertical */
        }

        .lista-resultados ul {
            list-style: none;
            /* Elimina los puntos de la lista */
            padding: 0;
            margin: 0;
        }

        .lista-resultados li {
            padding: 8px;
            cursor: pointer;
            /* Cambia el cursor a una mano para indicar que es seleccionable */
        }

        .lista-resultados li:hover {
            background-color: #f5f5f5;
            /* Cambia el color de fondo al pasar el mouse por encima */
        }

</style>

<body>
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <input type="hidden" name="url_base" id="url_base" value="{{ route('programacion.base') }}">
    <input type="hidden" name="url_masivo" id="url_masivo" value="{{ route('programacion.masivos') }}">
    <input type="hidden" name="url_buscar" id="url_buscar" value="{{ route('programacion.buscar_por_contrato') }}">
    <input type="hidden" name="url_ver" id="url_ver" value="{{ route('programacion.show', ['id' => ':id'])}}'?action=view">
    <input type="hidden" name="url_GDO" id="url_GDO" value="{{ route('programacion.programacionGDO')}}">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-12">
                <div class="col-md-12 table-responsive" style="padding: 15px;">
                    <div class="card">

                        <div class="card-body">
                            <a href="{{ route('programacion.create') }}" class="btn btn-success btn-sm" title="Add New Programacion">
                                <i class="fa fa-plus" aria-hidden="true"></i> Generar nueva Tabla
                            </a>
                            @haspermission('ver_programacion')

                            <button id="openModalBtn" class="btn btn-primary btn-sm" title="Añadir a Base"> Añadir a Base </button>
                            <button id="openMasivoBtn" class="btn btn-primary btn-sm" title="Programadas Tecnicos"> Programadas Tecnicos </button>
                            <button id="openGDOBtn" class="btn btn-secondary btn-sm" title="Programadas GDO"> Programadas GDO </button>
                            <br>
                            <br>
                            <div class="form-group">
                                <input type="text" class="form-control" id="buscadorContrato" placeholder="Buscar contrato...">
                            </div>

                            <div id="resultadosBusqueda" class="lista-resultados">
                            </div>
                            @endhaspermission

                            <br>

                            <table id="programacion" class="table table-striped table-bordered">
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
                                        $fecha=explode(" ",$dato->created_at)[0];
                                        $nombre=explode(" ",$dato->nombre);

                                        if ($nombre[1]=="tecnicos") {
                                            $tipo_cargue=$nombre[0] . " " . $nombre[1];

                                        } elseif($nombre[1]=="GDO") {
                                            $tipo_cargue=$nombre[0] . " " . $nombre[1];
                                        }else{
                                            $tipo_cargue="";
                                        }


                                    ?>
                                    <tr>
                                        <td>{{$dato->id}}</td>
                                        <td>{{$dato->usuario->name}}</td>
                                        <td>{{$tipo_cargue}}</td>
                                        <td>{{$fecha}}</td>
                                        <td>
                                            @haspermission('ver_programacion')
                                            <a href="{{ route('programacion.show', $dato->id)}}'?action=edit" title="show">
                                                <button type="button" class="btn btn-warning">Editar</button>
                                            </a>
                                            @endhaspermission
                                            <a href="{{ route('programacion.show', $dato->id)}}'?action=view" title="show">
                                                <button type="button" class="btn btn-success">Ver</button>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Modal base -->
        <div class="modal fade" id="addProgramacionModal" tabindex="-1" aria-labelledby="addProgramacionModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addProgramacionModalLabel">Añadir a Base</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="programacionForm" enctype="multipart/form-data" method="POST" action="{{ route('programacion.base') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="archivo" class="form-label">Archivo:</label>
                                <input type="file" class="form-control" id="archivo" name="archivo">
                                <br>
                                <div id="loader" style="display: none;">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <span class="visually-hidden">Cargando...</span>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary">Subir</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal programación prioridades -->

        <div class="modal fade" id="addMasivoModal" tabindex="-1" aria-labelledby="addMasivoModalLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addMasivoModalLabel">Programadas tecnicos</h5>
                        <button type="button" class="btn-close masivoModal" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="#" id="masivoForm" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="form-group">
                                <label for="archivo">Selecciona un archivo:</label>

                                <input class="form-control mb-3" type="file" name="archivo" id="archivo">

                                <div id="loaderMasivo" style="display: none;">
                                    <div class="spinner-border text-primary" role="status"></div>
                                    <span class="visually-hidden">Cargando...</span>
                                </div>

                            </div>
                            <button class="btn btn-primary" type="submit">Cargar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    <!-- Programadas GDO -->
    <div class="modal fade" id="addGDO" tabindex="-1" aria-labelledby="addGDOLabel" aria-hidden="true">
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
                            <button class="btn btn-primary" type="submit">Cargar</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
@section('js')
<script src="{{ asset('js/indexProgramacionV3-3.js') }}" type="text/javascript"></script>


@if (session('success'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
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
    document.addEventListener('DOMContentLoaded', function() {
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
    document.addEventListener('DOMContentLoaded', function() {
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
                            success: function(response) {
                                console.log(response);
                            },
                            error: function(xhr, error, status) {

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
