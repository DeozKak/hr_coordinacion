@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción Corte {{$corte?->fecha_inicio}} a {{$corte?->fecha_fin}}</h1>
@endsection

@section('content')
<script src="{{asset('js/produccionIndex.js')}}"></script>
<div class="card">
    <div class="card-body">
    <a class="btn btn-primary" href="javascript:history.go(-1)" style="margin-bottom: 10px;">Ir Atrás</a>
        <x-adminlte-card title="Total Inspecciones por Operario" theme="info" icon="fas fa-hard-hat">
            <canvas id="inspeccionesDiarias"></canvas>
        </x-adminlte-card>
    </div>

    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="Categorias Inspecciones" theme="info" icon="fas fa-code-branch" header-class="text-uppercase rounded-bottom border-info">
                        @if(isset($inspectores) && $inspectores->isNotEmpty())
                        <select class="form-control" id="inspectorSelect" style="width: 50%;">
                            <option value="">Mostrar todos los contratos</option>
                            @foreach ($inspectores as $inspector)
                            @if ($inspector->state == 1)
                            <option value="{{$inspector->cedula}}">{{$inspector->apellidos}}</option>
                            @endif
                            @endforeach
                        </select>
                        <canvas id="categoriaInsp"></canvas>
                        @endif
                    </x-adminlte-card>
                </div>
            </div>
        </div>
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="Inspecciones hechas por zonas" theme="info" icon="fas fa-map-marker-alt">
                        <canvas id="zonasInsp"></canvas>
                    </x-adminlte-card>
                </div>
            </div>
        </div>
    </div>
</div>
@stop

@if($warning)
<script>

    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "",
            text: "{{$warning}}",
            type: "warning"
        });
    });
</script>
 {{$warning = null;}}
@endif



@if(isset($municipiosNoEncontrados) && $municipiosNoEncontrados->isNotEmpty())
<script>

    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            title: "Por favor, ingrese los siguientes municipios en la base de datos:",
            html: `

                    @foreach ($municipiosNoEncontrados as $municipio)
                        <li>{{ $municipio }}</li>
                    @endforeach

            `,
            type: "warning"
        });
    });
</script>
@endif

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
    let stackedBar;
        const meta = @json($corte->meta ?? []);
        const canva = document.querySelector('#inspeccionesDiarias').getContext('2d');
        const labels = {!! json_encode($produccionInspector) !!};
        Chart.register(ChartDataLabels);

        stackedBar = new Chart(canva, {
            type: 'bar',
            data: {
                labels: labels.map(inspector => inspector.nombres),
                datasets: [{
                    label: 'Inspecciones',
                    data: labels.map(inspector => inspector.contratos),
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true
                    }
                },
                plugins: {
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line',
                                mode: 'horizontal',
                                scaleID: 'y',
                                value: meta, // Valor de la línea de meta
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 2,
                                label: {
                                    content: 'META',
                                    enabled: true,
                                    position: 'end'
                                }
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, context) => {
                            return value; // Mostrar el valor encima de las barras
                        }
                    }
                }
            }
        });
    let pieChart;
    const canvaCategoria = document.querySelector('#categoriaInsp').getContext('2d');
    const contratosCategoria = {!! json_encode($contratosCategoria) !!}; // Datos desde el backend
    const inspectorSelect = document.getElementById('inspectorSelect');

    // Función para actualizar el gráfico con los datos totales o de un inspector específico
    function actualizarGrafico(inspectorCedula = null) {
        let contratosComerciales = 0;
        let contratosResidenciales = 0;

        // Contar contratos comerciales y residenciales
        contratosCategoria.forEach(item => {
            if (inspectorCedula === null || item.CC_OPERARIO === inspectorCedula) {
                if (item.CATEGORIA === 'COMERCIAL') {
                    contratosComerciales++;
                } else if (item.CATEGORIA === 'RESIDENCIAL') {
                    contratosResidenciales++;
                }
            }
        });

        // Mostrar alerta si el inspector seleccionado no tiene contratos
        if (inspectorCedula && contratosComerciales === 0 && contratosResidenciales === 0) {
            Swal.fire({
                icon: 'warning',
                title: 'Sin contratos',
                text: 'Este inspector no tiene contratos comerciales ni residenciales.',
                confirmButtonText: 'Aceptar'
            });
        }

        // Si el gráfico ya existe, actualizar los datos
        if (pieChart) {
            pieChart.data.datasets[0].data = [contratosComerciales, contratosResidenciales];
            pieChart.update(); // Refrescar el gráfico
        } else {
            // Crear un nuevo gráfico si no existe
            pieChart = new Chart(canvaCategoria, {
                type: 'pie',
                data: {
                    labels: ['Comerciales', 'Residenciales'],
                    datasets: [{
                        label: 'Categorías de Contratos',
                        data: [contratosComerciales, contratosResidenciales],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.2)',
                            'rgba(54, 162, 235, 0.2)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    plugins: {
                        datalabels: {
                            formatter: (value, context) => {
                                return value; // Mostrar el valor de la cantidad de contratos en el gráfico
                            }
                        }
                    }
                }
            });
        }
    }

    // Evento para detectar cambios en el select
    inspectorSelect.addEventListener('change', (event) => {
        const selectedInspector = event.target.value; // Obtener el valor seleccionado (cédula del inspector)
        actualizarGrafico(selectedInspector || null);
    });

    // Al cargar la página, mostramos el total de todos los contratos
    document.addEventListener('DOMContentLoaded', () => {
        actualizarGrafico(); // Llamamos a la función para mostrar el total de contratos
    });

    let ZonaPie
    const canvaZonas = document.querySelector('#zonasInsp').getContext('2d');
    const ContratosZonas = {!!json_encode($conteoContratosPorZona) !!};

    const zona = [];
    const data = [];
    for (let i = 0; i < ContratosZonas.length; i++) {
        zona.push(ContratosZonas[i].zona);
        data.push(ContratosZonas[i].contratos);
    }

        ZonaPie = new Chart(zonasInsp, {
        type: 'pie',
        data: {
            labels: zona,
            datasets: [{
                label: 'Contratos por Zona',
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(123, 200, 87, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(123, 200, 87, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    formatter: (value, context) => {
                        return value; // Mostrar el valor de la cantidad de contratos en el gráfico
                    }
                }
            }
        }
    });
    window.addEventListener('resize', redibujarGraficos);

function redibujarGraficos() {
    // Destruir gráficos existentes para evitar superposición de gráficos viejos
    if (ZonaPie) ZonaPie.destroy();
    if (pieChart) pieChart.destroy();
    if (stackedBar) stackedBar.destroy();

    // Configurar de nuevo `stackedBar` con sus datos y opciones
    const canva = document.querySelector('#inspeccionesDiarias').getContext('2d');
    const labels = {!!json_encode($produccionInspector) !!};
    Chart.register(ChartDataLabels);
    stackedBar = new Chart(canva, {
        type: 'bar',
        data: {
            labels: labels.map(inspector => inspector.nombres),
            datasets: [{
                label: 'Inspecciones',
                data: labels.map(inspector => inspector.contratos),
                backgroundColor: 'rgba(255, 99, 132, 0.2)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true
                }
            },
            plugins: {
                annotation: {
                    annotations: {
                        line1: {
                            type: 'line',
                            mode: 'horizontal',
                            scaleID: 'y',
                            value: meta, // Valor de la línea de meta
                            borderColor: 'rgba(75, 192, 192, 1)',
                            borderWidth: 2,
                            label: {
                                content: 'META',
                                enabled: true,
                                position: 'end'
                            }
                        }
                    }
                },
                datalabels: {
                    anchor: 'end',
                    align: 'top',
                    formatter: (value) => value // Mostrar el valor encima de las barras
                }
            }
        }
    });

    // Calcular los datos para `pieChart` de categorías de contratos
    let contratosComerciales = 0;
    let contratosResidenciales = 0;
    const inspectorCedula = inspectorSelect.value || null; // Obtener el inspector seleccionado

    // Lógica para contar contratos comerciales y residenciales según `actualizarGrafico`
    contratosCategoria.forEach(item => {
        if (inspectorCedula === null || item.CC_OPERARIO === inspectorCedula) {
            if (item.CATEGORIA === 'COMERCIAL') {
                contratosComerciales++;
            } else if (item.CATEGORIA === 'RESIDENCIAL') {
                contratosResidenciales++;
            }
        }
    });

    // Mostrar alerta si el inspector seleccionado no tiene contratos
    if (inspectorCedula && contratosComerciales === 0 && contratosResidenciales === 0) {
        Swal.fire({
            icon: 'warning',
            title: 'Sin contratos',
            text: 'Este inspector no tiene contratos comerciales ni residenciales.',
            confirmButtonText: 'Aceptar'
        });
    }

    // Configurar `pieChart` para las categorías de contratos con los datos actualizados
    pieChart = new Chart(canvaCategoria, {
        type: 'pie',
        data: {
            labels: ['Comerciales', 'Residenciales'],
            datasets: [{
                label: 'Categorías de Contratos',
                data: [contratosComerciales, contratosResidenciales],
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    formatter: (value) => value // Mostrar el valor de la cantidad de contratos
                }
            }
        }
    });

    // Configurar `ZonaPie` para contratos por zona
    ZonaPie = new Chart(canvaZonas, {
        type: 'pie',
        data: {
            labels: zona,
            datasets: [{
                label: 'Contratos por Zona',
                data: data,
                backgroundColor: [
                    'rgba(255, 99, 132, 0.2)',
                    'rgba(54, 162, 235, 0.2)',
                    'rgba(123, 200, 87, 0.2)'
                ],
                borderColor: [
                    'rgba(255, 99, 132, 1)',
                    'rgba(54, 162, 235, 1)',
                    'rgba(123, 200, 87, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            plugins: {
                datalabels: {
                    formatter: (value) => value // Mostrar el valor de contratos en el gráfico
                }
            }
        }
    });
}

</script>
@stop
