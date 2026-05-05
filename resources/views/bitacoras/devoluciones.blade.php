@extends('adminlte::page')

@section('title', 'Seguimiento de Devoluciones')

@section('content_header')
<h1>Seguimiento de Devoluciones</h1>

@endsection

@section('content')
<!-- <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-T3c6CoIi6uLrA9TneNEoa7RxnatzjcDSCmG1MXxSR1GAsXEV/Dwwykc2MPK8M2HN" crossorigin="anonymous">
 -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<link rel="stylesheet" href="{{asset('css/bitacoras/DevolucionesV2.css')}}">

<script src="{{asset('js/bitacora/tbl_devoluciones.js')}}"></script>

<body class="body">

    <input type="hidden" id="exportar_devoluciones" value="{{ route('bitacora.exportar_devoluciones') }}">
    <input type="hidden" id="token" value="{{ csrf_token() }}">
    <div id="contenedor">
        <div id="loader" style="display: none;"></div>
        <div id="overlay" style="display: none;"></div>
        <div class="row justify-content-center mt-3 shadow-container">
            <div class="card text-center border-info w-100">
                <div class="card-header" style="white-space: nowrap;">
                    <div class="nav-wrapper" style="flex-direction: column-reverse;">
                        <ul class="nav nav-tabs card-header-tabs flex-nowrap">

                            <li class="nav-item" style="white-space: nowrap;">
                                <a id="Devoluciones" class="nav-link btnav active" data-bs-toggle="tab">Devoluciones</a>
                            </li>
                            <li class="nav-item" style="white-space: nowrap;">
                                <a id="Gestionados" class="nav-link btnav" data-bs-toggle="tab">Historico</a>
                            </li>
                        </ul>
                    </div>
                </div>
                <div class="row justify-content-center">
                    <div class="col-lg-12">
                        <div class="col-md-12 table-responsive">
                            <table class="table" id="devoluciones">
                                <thead>
                                    <tr>
                                        <th>Supervisor</th>
                                        <th>Inspector</th>
                                        <th>Fecha Inspeccion</th>
                                        <th>Tipo Trabajo</th>
                                        <th>Contrato</th>
                                        <th>Orden de trabajo</th>
                                        <th>Orden Externa</th>
                                        <th>Resultado</th>
                                        <th>Causal</th>
                                        <th>Fecha devolución</th>
                                        <th>Gestionado</th>
                                        <th>Fecha gestión</th>
                                        <th>Observación Gestión</th>
                                        <th>Dias sin Gestionar</th>
                                        @haspermission('mod_devoluciones')
                                        <th>Acciones</th>
                                        @endhaspermission

                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($devoluciones as $dato)
                                    <tr style="<?php if ($dato->vence === "60 meses") {
                                                    echo ("background-color: rgb(251,201,255);");
                                                } ?>">

                                        <td>{{$dato->Supervisor->name}}</td>
                                        <td> {{$dato->Inspector->nombres}} {{$dato->Inspector->apellidos}}</td>
                                        <td> {{$dato->FECHA_INSP}} </td>
                                        <td> {{$dato->TIPO_TRABAJO}} </td>
                                        <td> {{$dato->CONTRATO}} </td>
                                        <td> {{$dato->ORDEN_TRABAJO}} </td>
                                        <td> {{$dato->ORDEN_EXT}} </td>
                                        <td> {{$dato->RESULTADO_CIERRE}} </td>
                                        <td> {{$dato->CAUSAL}} </td>
                                        <td> {{$dato->FECHA_DV}} </td>
                                        @if ($dato->GESTIONADO === 0 )
                                        <td>NO</td>
                                        @else
                                        <td style='background-color: #9cffa9 !important; font-weight: 600; color: #1a7f37; '>SI</td>
                                        @endif
                                        <td> {{$dato->FECHA_GESTION}} </td>
                                        <td> {{$dato->OBSERVACION_GESTION}}</td>
                                        @if($dato->DIAS_SIN_GESTION >= 2)
                                        <td style='background-color: rgba(255,255,0,0.8) !important; font-weight: 600; color: #8d6500;'> {{$dato->DIAS_SIN_GESTION}} </td>
                                        @else
                                        <td>{{$dato->DIAS_SIN_GESTION}} </td>
                                        @endif
                                        @haspermission('mod_devoluciones')
                                        @if ($dato->GESTIONADO === 0 )
                                                <td>
                                                    <form action="{{route('bitacoras.actualizar_devolucion',['id' => $dato->id])}}" method="POST">
                                                    @csrf
                                                        <button type="submit" class="btn btn-gradient btn-gradient-info btn-sm">Cambiar</button>
                                                    </form>
                                                </td>
                                        @else
                                            <td>
                                                -
                                            </td>
                                        @endif
                                        @endhaspermission
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>


                            <table class="table tbl_datos" id="gestionados">
                                <thead>
                                    <tr>
                                        <th>Supervisor</th>
                                        <th>Inspector</th>
                                        <th>Fecha Inspeccion</th>
                                        <th>Tipo Trabajo</th>
                                        <th>Contrato</th>
                                        <th>Orden de trabajo</th>
                                        <th>Orden Externa</th>
                                        <th>Resultado</th>
                                        <th>Causal</th>
                                        <th>Fecha devolución</th>
                                        <th>Gestionado</th>
                                        <th>Fecha gestión</th>
                                        <th>Observación Gestión</th>
                                        <th>Dias sin Gestionar</th>


                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($gestionados as $dato)
                                    <tr style="<?php if ($dato->vence === "60 meses") {
                                                    echo ("background-color: rgb(251,201,255);");
                                                } ?>">
                                        <td>{{$dato->Supervisor->name}}</td>
                                        <td> {{$dato->Inspector->nombres}} {{$dato->Inspector->apellidos}}</td>
                                        <td> {{$dato->FECHA_INSP}} </td>
                                        <td> {{$dato->TIPO_TRABAJO}} </td>
                                        <td> {{$dato->CONTRATO}} </td>
                                        <td> {{$dato->ORDEN_TRABAJO}} </td>
                                        <td> {{$dato->ORDEN_EXT}} </td>
                                        <td> {{$dato->RESULTADO_CIERRE}} </td>
                                        <td> {{$dato->CAUSAL}} </td>
                                        <td> {{$dato->FECHA_DV}} </td>
                                        @if ($dato->GESTIONADO === 0 )
                                        <td>NO</td>
                                        @else
                                        <td style='background-color: #c2ffd4 !important; font-weight: 600; color: #1a7f37; '>SI</td>
                                        @endif
                                        <td> {{$dato->FECHA_GESTION}} </td>
                                        <td> {{$dato->OBSERVACION_GESTION}}</td>
                                        @if($dato->DIAS_SIN_GESTION >= 2)
                                        <td style='background-color: rgb(255, 165, 0);'> {{$dato->DIAS_SIN_GESTION}} </td>
                                        @else
                                        <td>{{$dato->DIAS_SIN_GESTION}} </td>
                                        @endif

                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="card-footer group-buttons">
                    <a class="btn-back" href="javascript:history.go(-1)">Ir Atrás</a>
                    <button class="btn btn-gradient btn-gradient-success" id="btnGuardar">Exportar</button>
                </div>
            </div>
        </div>
    </div>
</body>
@section('js')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();

                let currentForm = this;

                Swal.fire({
                    title: '¿Estás seguro?',
                    // Insertamos el checkbox mediante HTML
                    html: '¿Quieres cambiar el estado de la devolución?<br><br>' +
                        '<div style="display: flex; flex-direction: row; align-items: center; justify-content: center; margin-bottom: 1em;">' +
                        '<input type="checkbox" id="chk_produccion" style="margin-right: 10px; transform: scale(1.1); cursor: pointer;">' +
                        '<label for="chk_produccion" style="margin-bottom: 0; cursor: pointer; font-weight: normal;">Agregar a producción</label>' +
                        '</div>',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Sí, cambiar estado',
                    input: 'textarea', // Agregamos un campo de entrada de texto
                    inputPlaceholder: 'Escriba la observación de la gestión',
                    inputAttributes: {
                        'aria-label': 'Escriba la observación de la gestión'
                    },
                    preConfirm: (observacion) => { // Validamos que se haya ingresado una observación
                        if (!observacion) {
                            Swal.showValidationMessage('Por favor, ingrese una observación.');
                        }
                        // Buscamos el checkbox dentro del popup de SweetAlert
                        const agregarProduccion = Swal.getPopup().querySelector('#chk_produccion').checked;

                        // Retornamos un OBJETO con ambos datos
                        return {
                            observacion: observacion,
                            agregarProduccion: agregarProduccion
                        };
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        // 1. Crear input para la Observación
                        let observacionInput = document.createElement('input');
                        observacionInput.type = 'hidden';
                        observacionInput.name = 'observacion';
                        observacionInput.value = result.value.observacion; // Accedemos a la propiedad del objeto
                        currentForm.appendChild(observacionInput);

                        // 2. Crear input para el Checkbox "Agregar a producción"
                        let produccionInput = document.createElement('input');
                        produccionInput.type = 'hidden';
                        produccionInput.name = 'agregar_produccion'; // Este nombre recibirás en el Request de Laravel
                        // Enviamos '1' si está marcado, '0' si no
                        produccionInput.value = result.value.agregarProduccion ? 1 : 0;
                        currentForm.appendChild(produccionInput);

                        currentForm.submit();
                    }
                });
            });
        });
    });
</script>
@stop
@endsection
