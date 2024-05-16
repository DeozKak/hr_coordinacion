@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción Corte {{$corte->fecha_inicio}}  a  {{$corte->fecha_fin}}</h1>
@endsection

@section('content')
<script src="{{asset('js/produccionIndex.js')}}"></script>
<div class="card">
    <div class="card-body">
        <x-adminlte-card title="Total Inspecciones por Operario" theme="info" icon="fas fa-hard-hat">
            <canvas id="inspeccionesDiarias"></canvas>
        </x-adminlte-card>


    </div>
    <div class="row">
        <div class="col-6">
            <div class="card">
                <div class="card-body">
                    <x-adminlte-card title="Categorias Inspecciones" theme="info" icon="fas fa-code-branch" header-class="text-uppercase rounded-bottom border-info">
                        <canvas id="categoriaInsp"></canvas>
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

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
    let stackedBar;
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
                                value: 180, // Valor de la línea de meta
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 2,
                                label: {
                                    content: 'META',
                                    enabled: true, // Habilitar la visualización del label
                                    position: 'end' // Posición del label (puedes ajustar según tu preferencia)
                                }
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, context) => {
                            return value; // Mostrar el valor de los contratos encima de las barras
                        }
                    }
                }
            }
        });
        let pieChart
        const canvaCategoria = document.querySelector('#categoriaInsp').getContext('2d');
        const contratosCategoria = {!!json_encode($contratosCategoria) !!};

        // Contar la cantidad de contratos por categoría
        let totalContratos = 0;
        let contratosComerciales = 0;
        let contratosResidenciales = 0;

        Object.values(contratosCategoria).forEach(item => {
            totalContratos++;
            if (item.CATEGORIA === 'COMERCIAL') {
                contratosComerciales++;
            } else if (item.CATEGORIA === 'RESIDENCIAL') {
                contratosResidenciales++;
            }
        });

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
            ZonaPie.destroy();
            pieChart.destroy();
            stackedBar.destroy();
            
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
                                value: 180, // Valor de la línea de meta
                                borderColor: 'rgba(75, 192, 192, 1)',
                                borderWidth: 2,
                                label: {
                                    content: 'META',
                                    enabled: true, // Habilitar la visualización del label
                                    position: 'end' // Posición del label (puedes ajustar según tu preferencia)
                                }
                            }
                        }
                    },
                    datalabels: {
                        anchor: 'end',
                        align: 'top',
                        formatter: (value, context) => {
                            return value; // Mostrar el valor de los contratos encima de las barras
                        }
                    }
                }
            }
        });

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
        }
</script>
@stop