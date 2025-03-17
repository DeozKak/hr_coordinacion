@extends('adminlte::page')

@section('title', 'Coordinación')

@section('content_header')
<h1></h1>
@endsection

@section('content')
<link rel="stylesheet" href="{{asset('css/gestion/planilla.css')}}?v={{ time()}}">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card mt-3">
                <div class="card-body">
                    <x-adminlte-card title="Asignación planilla" theme="info" icon="fas fa-tags">
                        <form id="formPlanilla" method="get" action="{{route('generarExcelPdf')}}" autocomplete="off">
                            <div class="row justify-content-center">
                                <div class="col-md-6 text-center">
                                    <label for="inspectorPlanilla">Inspector</label>
                                    <select class="form-control mx-auto" name="inspectorPlanilla" id="inspectorPlanilla">
                                        <option value="">Seleccione...</option>
                                        @foreach ($inspectors as $inspector)
                                        <option value="{{$inspector->id}}">{{$inspector->id}}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-5 justify-content-center">
                                <div class="col-md-6 text-center">
                                    <label for="parametro">Parametro</label>
                                    <select class="form-control mx-auto" name="parametro" id="parametro">
                                        <option value="">Todo</option>
                                        <option value="1">Fecha</option>
                                        <option value="2">Marca</option>
                                    </select>
                                </div>
                                <div class="col-md-6 text-center divTipoOrden"> 
                                    <label for="tipoOrden">Tipo orden</label>
                                    <select class="form-control mx-auto" name="tipoOrden" id="tipoOrden">
                                        <option value="">Ambas</option>
                                        <option value="1">Masiva</option>
                                        <option value="2">Externa</option>
                                    </select>
                                </div>
                            </div>
                            <div class="row mt-5 justify-content-center divFecha" style="display: none">
                                <div class="col-md-6 text-center">
                                    <label for="fechaAsignacion">Fecha</label>
                                    <input class="form-control mx-auto" type="date" name="fechaAsignacion" id="fechaAsignacion">
                                </div>
                            </div>
                            <div class="row mt-5 justify-content-center">
                                <div class="col-md-6 text-center">
                                    <label for="expExcel" style="text-align: center;">Exportar excel</label>
                                    <input class="form-control mx-auto" type="checkbox" name="expExcel" id="expExcel">
                                </div>
                                <div class="col-md-6 text-center">
                                    <label for="expPdf">Exportar pdf</label>
                                    <input class="form-control mx-auto" type="checkbox" name="expPdf" id="expPdf">
                                </div>
                            </div>
                            <div class="row mt-5 justify-content-center">
                                <button type="submit" id="btnGenerarPdfExcel" class="btn btn-primary">Aceptar</button>
                            </div>
                        </form>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>

@section('js')
<script src="{{asset('js/management/planilla.js')}}?v={{ time()}}"></script>
<script>
    const token = "{{ csrf_token()}}"
</script>

@if(session('error'))
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            position: "top-end",
            icon: "warning",
            title: "{{ session('error') }}",
            toast: true,
            timer: 3000,
        });
    });
</script>
@endif

@stop
@endsection