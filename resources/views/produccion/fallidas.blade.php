@extends('adminlte::page')

@section('title', 'Fallidas')

@section('content_header')
    <h1 class="m-0 text-dark">Fallidas</h1>
@stop

@section('content')
   <input type="hidden" id="data" value="{{route('produccion.fallidas.data')}}">
<div class="shadow-container">


    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>
  
    <x-adminlte-card title="Fallidas por dia" theme="info" icon="fas fa-code-branch" header-class="text-uppercase rounded-bottom border-info" collapsible>
        <div id="detalles" style="width: '100px'"></div>
    </x-adminlte-card>

</div>

<div class="modal fade" id="exampleModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" style="max-width: 90%;" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="titulo">Inspecciones </h5>&nbsp;<span class="text-danger" id="cantidadDobles"></span>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" style="margin-bottom: 10px;">
                <div id="mensajeNoDatos" style="display: none;" class="alert alert-warning">No hay datos</div>

                <div id="contratos_dia" style=" width: '100px'; margin-bottom: 10px;"></div>
            </div>
            <div class="modal-footer">
                
                <button type="button" class="btn btn-secondary" id="cerrar_modal" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{ asset('js/fallidas/detallesFallidasV2.js') . '?v=' . time() }}"></script>
<script>
const urlObtenerDetalles = "{{ route('obtener-url-detalles-fallidas') }}"; // Usando el helper route()
</script>
@stop

@stop