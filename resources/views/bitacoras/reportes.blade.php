@extends('adminlte::page')

@section('title', 'Reportes de Bitácoras')

@section('content_header')
<h1>Reportes de Bitácoras</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/bitacoras/Reportes.css')}}">
<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="{{asset('js/Reportes.js')}}"></script>

<body>

    <div class="container">
        <div class="row justify-content-center mt-3 shadow-container">
           <div class="col-md-12">
                <div class="col-md-12 table-responsive" style="padding: 17px;">
                    <table class="table table-striped table-bordered" id="devoluciones">
                        <thead>
                            <tr>
                                <th>Nombre Reporte</th>
                                <th>Usuario</th>
                                <th>Fecha Creación</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($bitacoras as $bitacora)
                            <tr>
                                <td>{{$bitacora->nombre_archivo}}</td>
                                <td>{{$bitacora->Usuario->name}}</td>
                                <td>{{$bitacora->fecha_creacion}}</td>
                                <td>
                                    <div class="btn-group" role="group" aria-label="Botones">
                                        <form action="{{route('bitacoras.ver_reporte',['id_bitacora'=> $bitacora->id])}}" method="POST">
                                            @csrf
                                            <button class="btn btn-primary" id="verReporte">Ver reporte</button>
                                        </form>
                                        <form action="{{route('bitacoras.download',['file'=>$bitacora->nombre_archivo.".xlsx"])}}" method="POST">
                                            @csrf
                                            <button class="btn btn-success" id="btnDescargar">Descargar Xlsx</button>
                                        </form>
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
</body>

@endsection