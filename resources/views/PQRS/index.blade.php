@extends('adminlte::page')

@section('title', 'Quejas')

@section('content_header')
    <h1>Quejas</h1>
@endsection

@section('content')
    <div class="d-flex justify-content-center">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title text-center mb-3">Macro PQR</h4>
                    <form id="formulario-archivo" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <input type="file" class="form-control" id="archivo" name="macroPQR" required>
                        </div>
                        <div class="text-center">
                            <button type="submit" id="btnBuscar" class="btn btn-success">Subir</button>
                        </div>
                    </form>
                    <br>
                    <div id="mensaje-programaciones"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body" style="padding: 20px">
            <h4 class="card-title text-center mb-3">Quejas</h4>

            <div id="table"></div>
        </div>
    </div>


    <script src="{{ asset('js/PQRS/index.js') }}"></script>

@endsection
