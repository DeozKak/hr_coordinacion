@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
<h1>Programación</h1>
@stop


@section('content')

<body>

    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Programación</div>
                    <div class="card-body">
                        <a href="{{ url('/programacion/create') }}" class="btn btn-success btn-sm" title="Add New Programacion">
                            <i class="fa fa-plus" aria-hidden="true"></i> Agregar Nuevo Programación </a>

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
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
@endsection