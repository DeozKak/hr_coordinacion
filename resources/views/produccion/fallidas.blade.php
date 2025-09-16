@extends('adminlte::page')

@section('title', 'Fallidas')

@section('content_header')
    {{-- Encabezado moderno --}}
    <div class="d-flex align-items-center justify-content-between mb-3">
        <h1 class="fw-bold text-primary mb-0">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Gestión de Fallidas
        </h1>
    </div>
@stop

@section('content')
    <link rel="stylesheet" href="{{ asset('css/produccion/fallidas.css') }}">

    <div id="overlay" style="display: none;"></div>
    <div id="loader" style="display: none;">
        <div class="spinner"></div>
        <div class="loader-text">Cargando...</div>
    </div>

    <input type="hidden" id="data" value="{{route('produccion.fallidas.data')}}">

    <div class="container-fluid mb-4">
        <a class="btn btn-outline-primary shadow-sm" href="javascript:history.go(-1)">
            <i class="fas fa-arrow-left"></i> Ir Atrás
        </a>
        {{-- Aquí puedes agregar otros botones como "Exportar" si lo necesitas --}}
    </div>

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="card shadow-lg border-0 rounded-4 bg-light h-100 custom-card-altura">
                    <div class="card-header text-white rounded-top-4">
                        <h3 class="mb-0 fw-bold">
                            <i class="fas fa-list-ul"></i> Fallidas por Día
                        </h3>
                    </div>
                    <div class="card-body">
                        <div id="detalles">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <br>
    <br>
    <div class="modal fade modal-modern" id="exampleModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-adaptive" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="titulo"><i class="fas fa-list-alt"></i> Inspecciones </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="mensajeNoDatos" class="alert-modern alert-warning-modern" style="display: none;">No hay datos</div>
                    <div class="table-container" style="height: 65vh;">
                        <div id="contratos_dia"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn-secondary-modern" id="cerrar_modal" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
@stop
@section('js')
<script src="{{ asset('js/fallidas/detallesFallidasV2.js') . '?v=' . time() }}"></script>
<script>
const urlObtenerDetalles = "{{ route('obtener-url-detalles-fallidas') }}"; // Usando el helper route()
</script>
@stop

