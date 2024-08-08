@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Programación</h1>
@stop


@section('content')

<body>
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <input type="hidden" name="url_base" id="url_base" value="{{ route('programacion.base') }}">
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Programación</div>
                    <div class="card-body">
                        <a href="{{ route('programacion.create') }}" class="btn btn-success btn-sm" title="Add New Programacion">
                            <i class="fa fa-plus" aria-hidden="true"></i> Generar nueva Tabla </a>

                        <button id="openModalBtn" class="btn btn-primary btn-sm" title="Add New Programacion"> Añadir a Base </button>

                        <form method="GET" action="{{ url('/programacion') }}" accept-charset="UTF-8" class="form-inline my-2 my-lg-0 float-right" role="search">
                            <div class="input-group">
                                <input type="text" class="form-control" name="search" placeholder="Buscar...">
                                <span class="input-group-append">
                                    <button class="btn btn-secondary" type="submit">
                                        <i class="fa fa-search"></i>
                                    </button>
                                </span>
                            </div>
                        </form>
                        <br>
                        <br>
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>nombre</th>
                                    <th>Usuario</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($datos as $dato)
                                <tr>
                                   

                                    <td>{{$dato->id}}</td>
                                    <td>{{$dato->nombre}}</td>
                                    <td>{{$dato->usuario->name}}</td>
                                    <td>
                                        <button type="button" class="btn btn-warning">Editar</button>
                                    </td>

                                 
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
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
    </div>


</body>
@section('js')
<script src="{{ asset('js/indexProgramacion.js') }}" type="text/javascript"></script>


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
            title: "Error",
            text: "{{session('error')}}",
            icon: "error"
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
                showCancelButton: false,
                allowOutsideClick: false,
                confirmButtonText: "Si",
                denyButtonText: "No",
            }).then((result) => {

                if (result.value) {
                    window.location.href = "{{ route('programacion.show',['id' => $temp->id]) }}";
                }
                if (result.isDenied) {
                    swal.fire({
                        icon: "warning",
                        title: "Se perderán los cambios!",
                        allowOutsideClick: false,
                        showDenyButton: true,
                        confirmButtonText: "Quiero generar una bitacora nueva",
                        denyButtonText: "Mantener cambios",
                    }).then((result) => {

                        if (result.isConfirmed) {
                            $.ajax({
                                type: "POST",
                                dataType: "json",
                                data: {
                                    _token: "{{ csrf_token() }}"
                                },
                                url: "",
                                success: function(response) {
                                    console.log(response);
                                },
                                error: function(xhr, error, status) {

                                    console.log(xhr.responseText);

                                }

                            });
                        }
                        if (result.isDenied) {
                            window.location.href = "";
                        }
                    });


                }

            });
        });
    </script>
    @endif
@stop
@endsection