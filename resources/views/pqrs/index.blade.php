@extends('adminlte::page')

@section('title', 'Quejas')

@section('content_header')
    <h1>Quejas</h1>
@endsection

@section('content')
    <style>
        /* Agrega estos estilos en tu archivo CSS o blade */
        .htDiasWarning {
            background-color: rgba(255, 255, 0, 0.8) !important;
            color: black !important;
        }

        .htDiasError {
            background-color: rgba(255, 0, 0, 0.76) !important;
            color: white !important;
        }

        .filaRecepcionMacro {
            background-color: rgb(147, 255, 134) !important;
            color: rgb(89, 88, 88) !important;
        }
        .filaRecepcionGDW {
            background-color: rgb(150, 186, 255) !important;
            color: rgb(89, 88, 88) !important;
        }
    </style>
    @can('cargar_PQRS')
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
                                <button type="submit" id="btnSubir" class="btn btn-success">Subir</button>
                            </div>
                        </form>
                        <br>
                        <div id="mensaje-programaciones"></div>
                    </div>
                </div>
            </div>
        </div>
    @endcan


    <div class="d-flex justify-content-center">
        <div class="card" style="width:1150px; margin:auto;">
            <div class="card-header">
                <h4 class="card-title text-center mb-0">Tiempos</h4>
            </div>
            <div class="card-body" style="padding: 20px">
                <div id="table"></div>
            </div>
        </div>
    </div>






    <script src="{{ asset('js/PQRS/indexV1.2.js') }}"></script>

@endsection
