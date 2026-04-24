@extends('adminlte::page')

@section('title', 'Programación')

@section('content_header')
    <h1>Ver Programación</h1>
@stop

@section('content')

    <div id="loader" style="display: none;"></div>
    <div id="overlay" style="display: none;"></div>
    <link rel="stylesheet" href="{{ asset('css/programacion/ver_programacionV1.css') }}">
    <input type="hidden" name="_token" id="token" value="{{ csrf_token() }}">
    <input type="hidden" name="url_update" id="url_update" value="{{ route('programacion.update',['id'=>':id']) }}">
    <input type="hidden" name="url_busqueda" id="url_busqueda" value="{{ route('programacion.agendamiento') }}">
    <input type="hidden" name="url_exportar" id="url_exportar" value="{{ route('programacion.exportar') }}">
    <input type="hidden" name="urlexportarSup" id="urlexportarSup" value="{{ route('programacion.exportarSup') }}">
    <div class="container-fluid">
        <div class="row justify-content-center">

            {{-- Columna del Formulario de Búsqueda --}}
            <div class="col-lg-4 ">
                <div class="card card-modern">
                    <div class="card-header card-header-modern">
                        <h3 class="card-title-modern"><i class="fas fa-calendar-alt"></i>Fecha de Agendamiento</h3>
                    </div>
                    <div class="card-body">
                        <div class="form-group">
                            <label for="fechaInicio">Fecha Inicial</label>
                            <input type="date" class="form-control form-control-modern" id="fechaInicio"
                                   name="fechaInicio" required>
                        </div>

                        <div class="form-group" id="fechaFinContainer" style="display: none;">
                            <label for="fechaFin">Fecha Final</label>
                            <input type="date" class="form-control form-control-modern" id="fechaFin"
                                   name="fechaFin">
                        </div>

                        <div class="form-check mb-3">
                            <label class="modern-checkbox" for="rangoFechas">
                                <input class="form-check-input" type="checkbox" id="rangoFechas" name="rangoFechas">
                                <span class="checkbox-box"></span>
                                Seleccionar un rango de fechas
                            </label>
                        </div>

                        <div id="mensaje-programaciones" class="mb-3"></div>
                        <div class="button-container-center">
                            <button type="submit" id="btnBuscar" class="btn-gradient-primary">
                                <i class="fas fa-search"></i> Buscar
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
        {{-- Columna de Resultados de la Búsqueda --}}
        <div class="col-12">
            <div class="card card-modern">
                <div class="card-header card-header-modern d-flex justify-content-between align-items-center">
                    <h3 class="card-title-modern mb-0"><i class="fas fa-tasks"></i>Resultados de la Búsqueda</h3>
                    <div class="card-tools">

                        <button id="btnExportarSup" class="btn btn-sm btn-export-sup">
                            <i class="fas fa-file-export"></i> Plantilla Supervisores
                        </button>
                        <button id="btnExportar" class="btn btn-sm btn-export-gdw">
                            <i class="fas fa-file-excel"></i> Plantilla GDW
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div id="buscador">
                        {{-- Aquí se renderizará la tabla de Handsontable --}}
                    </div>
                </div>
            </div>
        </div>
    </div>


    @section('js')

        <script src="{{ asset('js/programacion/verProgramacionV4.4.js') }}"></script>
        <script>
            const tecnicos = @json($tecnicos);
            let permiso_modTec = 0;
            const rangoFechasCheckbox = document.getElementById('rangoFechas');
            const fechaFinContainer = document.getElementById('fechaFinContainer');

            rangoFechasCheckbox.addEventListener('change', () => {
                if (rangoFechasCheckbox.checked) {
                    fechaFinContainer.style.display = 'block';
                } else {
                    fechaFinContainer.style.display = 'none';
                }
            });
        </script>
        @can('mod_tecnicos')
            <script> permiso_modTec = 1;</script>
        @endcan
        <script>
            const url_jobs = "{{ route('jobs.pnd') }}";

            function verificarProgramaciones() {
                fetch(url_jobs)
                    .then(response => response.json())
                    .then(data => {
                        const mensajeDiv = document.getElementById('mensaje-programaciones');
                        console.log(data);
                        if (data.percentage !== null) {
                            document.getElementById('btnBuscar').disabled = true;

                            mensajeDiv.innerHTML = `
                    <div class="alert-modern alert-info-modern">
                        Sincronizando asignaciones de técnicos. Porcentaje completado:
                        <strong>${data.percentage}%</strong>. Por favor, espere...
                    </div>
                `;
                        } else {
                            document.getElementById('btnBuscar').disabled = false;
                            mensajeDiv.innerHTML = ''; // Limpiar el mensaje si no hay jobs en ejecución
                        }
                    });
            }

            // Verificar cada 5 segundos (ajusta el intervalo según tus necesidades)
            setInterval(verificarProgramaciones, 5000);

            // Ejecutar la verificación inicial al cargar la página
            verificarProgramaciones();
        </script>

    @stop
@endsection
