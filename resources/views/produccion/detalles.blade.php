@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción</h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/produccion/produccion.css')}}">
<script src="{{asset('js/producciondetalles.js')}}"></script>
<input type="hidden" id="id_produccion" value="{{route('produccion.datosDetalles')}}">

<div class="shadow-container">
    <div class="card-body">
        <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>

        <div id="detalles" style=" width: '100px'"></div>

    </div>
</div>

<!-- Modal Detalles de dia -->
<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo">Inspecciones </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="margin-bottom: 10px;">
                <div id="mensajeNoDatos" style="display: none;">No hay datos</div>
               
                <div id="contratos_dia" style=" width: '100px'; margin-bottom: 10px;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-success" id="agregar">Agregar Inspección</button>
                <button type="button" class="btn btn-secondary" id="cerrar_modal" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
@stop