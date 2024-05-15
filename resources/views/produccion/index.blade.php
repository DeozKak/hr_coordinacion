@extends('adminlte::page')

@section('title', 'Producción')

@section('content_header')
<h1>Producción</h1>
@endsection

@section('content')

<div class="card">
    <div class="card-body">
        <x-adminlte-card title="Total Inspecciones por Operario" theme="purple" icon="fas fa-hard-hat">
            <canvas id="inspeccionesDiarias"></canvas>
        </x-adminlte-card>
    </div>
</div>
@stop

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-annotation/3.0.1/chartjs-plugin-annotation.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/chartjs-plugin-datalabels/2.2.0/chartjs-plugin-datalabels.min.js"></script>
<script>
    const canva =document.querySelector('#inspeccionesDiarias').getContext('2d');
    const labels = {!! json_encode($produccionInspector) !!};
    Chart.register(ChartDataLabels);
    const stackedBar = new Chart(canva, {
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
                            content: 'Meta',
                            enabled: true
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
</script>
@stop