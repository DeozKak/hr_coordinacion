let stackedBar;
const canva = document.querySelector('#inspeccionesDiarias').getContext('2d');

// Acceder a las variables definidas en window.appData
const meta = window.appData.meta;
const contratosCategoria = window.appData.contratosCategoria;
const labels = window.appData.labels;

// Selección de los contextos de los canvas
/*const canvaCategoriaInsp = document.querySelector('#categoriaInsp').getContext('2d');
const canvaZonasInsp = document.querySelector('#zonasInsp').getContext('2d');
const canvaCategoriaContratos = document.querySelector('#categoriaContratos')?.getContext('2d');
const canvaZonasContratos = document.querySelector('#zonaContratos')?.getContext('2d');*/
let btnComparar;
let btnCompararCortes;
let inspectorSelectStackedbar;
let cortesComparisonSelectStackedbar;
let tomSelectInstanceStackedbar;
let tomSelectInspectores;
//variable global para guardar valores seleccionados en cortesComparisonSelectStackedbar
let valoresSelectStackedbar = [];
// Variable para guardar el estado anterior de los valores seleccionados
let valoresPreviosStackedbar = [];
//variable global para guardar valores seleccionados en CortesComparisonSelect
let valoresCortesComparisonSelect = [];
let valoresSelectInspectores = [];

let comparisonBar; // Variable global para la gráfica de comparación
let corteComparadoTotales = {}; // Almacena totales de inspecciones para cortes seleccionados
const corteActualTotal = labels.reduce((acc, curr) => acc + curr.contratos, 0); // Total del corte actual

let inspectorCedula = null; // Variable para almacenar el inspector seleccionado
let contratosComerciales = 0;
let contratosResidenciales = 0;

// Configuración de colores predefinidos
const predefinedColors = [
    'rgba(54, 162, 235, 0.5)', // Azul
    'rgba(75, 192, 192, 0.5)', // Verde Agua
    'rgba(203, 72, 155, 0.5)', // Rosa
    'rgba(153, 102, 255, 0.5)', // Morado
    'rgba(255, 159, 64, 0.5)', // Naranja
    'rgba(0, 165, 37, 0.5)'  // Jade
];

const predefinedBorderColors = [
    'rgba(54, 162, 235, 1)', // Azul
    'rgba(75, 192, 192, 1)', // Verde Agua
    'rgba(203, 72, 155, 1)', // Rosa
    'rgba(153, 102, 255, 1)', // Morado
    'rgba(255, 159, 64, 1)', // Naranja
    'rgba(0, 165, 37, 1)'  // Jade
];

const actualizarGrafico = (labels, data, titulo, mostrarDatalabels = true, mostrarCortesTooltip = false) => {
    if (data[0] > 0) {
        stackedBar.data.labels = labels;
        stackedBar.data.datasets[0].data = data;
        stackedBar.options.plugins.title.text = titulo;

        // Actualizar la opción personalizada para el tooltip
        stackedBar.options.mostrarCortesTooltip = mostrarCortesTooltip;

        // Activar o desactivar datalabels dinámicamente
        stackedBar.options.plugins.datalabels.display = mostrarDatalabels;
        stackedBar.update(); // Redibujar el gráfico
    }
};


Chart.register(ChartDataLabels);
const chartColors = {
    primary: 'rgba(51, 88, 244, 0.8)',
    primaryLight: 'rgba(51, 88, 244, 0.6)',
    grid: '#e9ecef',
    text: '#525f7f',
    tooltipBg: '#2c3e50'
};
stackedBar = new Chart(canva, {
    type: 'bar',
    data: {
        labels: labels.map(inspector => `${inspector.nombres}`), // Etiquetas únicas para cada barra
        datasets: [{
            label: 'Corte actual',
            data: labels.map(inspector => inspector.contratos),
            backgroundColor: chartColors.primaryLight,
            borderColor: chartColors.primary,
            borderWidth: 2,
            borderRadius: 6, // Barras redondeadas
            barPercentage: 0.7,
        }]
    },
    options: {
        layout: {
            padding: {
                top: 20,
                bottom: 20
            }
        },
        scales: {
            x: {
                grid: {
                    display: false // Un look más limpio sin líneas de cuadrícula verticales
                },
                stacked: false,
                barPercentage: 0.5, // Controla el ancho de la barra
                categoryPercentage: 0.8, // Espaciado entre barras
                title: {
                    display: true,
                    text: 'Inspectores',
                    font: {
                        size: 14,
                        family: 'inherit',
                    }
                },
                ticks: {
                    color: chartColors.text,
                    font: {
                        size: 12,
                        family: 'inherit',
                    },
                    autoSkip: false // Evita que se salten etiquetas
                }
            },
            y: {
                border: {
                    dash: [5, 5] // Línea del eje Y punteada
                },
                grid: {
                    color: chartColors.grid // Líneas de cuadrícula más claras
                },
                beginAtZero: true,
                title: {
                    display: true,
                    text: 'Cantidad de Inspecciones',
                    font: {
                        size: 14,
                        family: 'inherit',
                    }
                },
                ticks: {
                    padding: 10,
                    color: chartColors.text,
                    font: {
                        family: 'inherit'
                    },
                    callback: function (value) {
                        return value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            title: {
                display: true,
                text: 'Inspecciones Totales por Inspector',
                font: {
                    size: 16
                }
            },
            legend: {
                position: 'top',
                labels: {
                    font: {
                        family: 'inherit', // Usa la fuente del tema
                        weight: '600'
                    }
                },
                color: chartColors.text
            },
            tooltip: {
                backgroundColor: chartColors.tooltipBg,

                callbacks: {
                    title: (tooltipItems) => {
                        // Accede al datasetIndex y dataIndex para obtener el nombre del corte
                        const datasetIndex = tooltipItems[0].datasetIndex;
                        const dataset = stackedBar.data.datasets[datasetIndex];

                        // Verifica si existe el dataset correspondiente
                        if (stackedBar.options.mostrarCortesTooltip && dataset) {
                            return dataset.label; // Muestra el nombre del corte
                        }
                        return tooltipItems[0].label; // Valor por defecto
                    },
                    label: (tooltipItem) => {
                        // Opcional: Formatea el dato asociado al tooltip
                        const value = tooltipItem.raw; // El valor de la barra
                        return `Total inspecciones: ${value}`;
                    },
                    titleFont: {
                        family: 'inherit',
                        size: 14,
                        weight: 'bold'
                    },
                    bodyFont: {
                        family: 'inherit',
                        size: 12
                    },
                    padding: 10,
                    cornerRadius: 8,
                    boxPadding: 4
                }
            },
            annotation: {
                annotations: {
                    line1: {
                        type: 'line',
                        mode: 'horizontal',
                        scaleID: 'y',
                        value: meta,
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
                formatter: (value) => value.toLocaleString(), // Mostrar números con formato
                font: {
                    size: 12,
                },
                color: 'black',
                display: true // Mostrar datalabels por defecto
            }
        },
        animation: {
            duration: 1000,
            easing: 'easeInOutQuad'
        }
    },
    maintainAspectRatio: false
});

document.addEventListener('DOMContentLoaded', () => {
    inspectorSelectStackedbar = document.getElementById('inspectorSelectStackedbar');
    cortesComparisonSelectStackedbar = document.getElementById('cortesComparisonSelectStackedbar');
    btnCompararCortes = document.getElementById('btnCompararCortes');
    btnComparar = document.getElementById('btnComparar');
    let btnRestaurar = document.getElementById('btnRestaurar');
    //listener para verificar si hay inspector seleccionado
    inspectorSelectStackedbar.addEventListener('change', async (event) => {
        setTimeout(() => {
            //modifica tomSelect de cortes para cambiar vista de comparacion con inspectores selecionados
            if (valoresSelectInspectores.length === 0) {
                tomSelectInstanceStackedbar.destroy();
                InitTomSelect(1);
                valoresSelectStackedbar = [];

            } else {

                if(tomSelectInstanceStackedbar.settings.maxItems !== 6){
                    //destruye instancia de tomSelect y vuelve a inicializar con el maximo de items diferentes
                    tomSelectInstanceStackedbar.destroy();
                    InitTomSelect(6);
                    valoresSelectStackedbar = [];
                }
            }
        }, 100);

    });
    btnComparar.addEventListener('click', async (event) => {
        //validaciones generales
        //primero no hay nada si ambos selects están vacios

        if (inspectorSelectStackedbar.value === '' && valoresSelectStackedbar.length === 0) {
            //restaurarGraficoPrincipal();
            //ActualizarDatosGraficosInspectores([],null)
            //validación para comparar con todos los inspectores
        } else if (inspectorSelectStackedbar.value === '' && valoresSelectStackedbar.length > 0) {

            ActualizarDatosGraficosInspectores(valoresSelectStackedbar, null);
            //condicional para comparar por inspector pero el corte actual
        } else if (valoresSelectInspectores.length > 0 && valoresSelectStackedbar.length === 0) {
            await BuscarResultados(valoresSelectInspectores)
            //ActualizarDatosGraficosInspectores(valoresSelectStackedbar, valoresSelectInspectores);
            //condicional para comparar por inspector
        } else if (valoresSelectInspectores.length > 0 && valoresSelectStackedbar.length > 0) {
            await BuscarResultados(valoresSelectInspectores)
            ActualizarDatosGraficosInspectores(valoresSelectStackedbar, valoresSelectInspectores);
        } else {
            //si no cumple ninguna de las anteriores validaciones restaura la grafica
            // await BuscarResultados("");
            //ActualizarDatosGraficosInspectores([],null)
        }
    });

    btnRestaurar.addEventListener('click', async (event) => {
        valoresSelectStackedbar = [];
        valoresSelectInspectores = [];
        tomSelectInstanceStackedbar.destroy();
        tomSelectInspectores.destroy();
        InitTomSelect(1);
        InitTomSelectInspectores();
        restaurarGraficoPrincipal();

    })
    //inicializar TomSelects y graficos

    //inicializar tomselect, el parametro es el numero maximo de items
    InitTomSelect(1);
    InitTomSelectCortes();
    InitTomSelectInspectores();
    // Mostrar la barra del corte actual al cargar la página
    actualizarGraficoComparacion()

    btnCompararCortes.addEventListener('click', async (event) => {
        console.log(valoresCortesComparisonSelect);
        actualizarDatosGraficoComparacion(valoresCortesComparisonSelect);
    })


});


// Función para actualizar o dibujar la gráfica de comparación
function actualizarGraficoComparacion() {
    const ctx = document.getElementById('comparacionInspecciones').getContext('2d');

    // Obtén el nombre del corte actual (puedes ajustar este valor según tu fuente de datos actual)
    const corteActualNombre = 'Corte Actual'; // Reemplaza con el nombre dinámico si está disponible

    // Define los datos para la gráfica
    const labels = Object.keys(corteComparadoTotales); // Nombres de los cortes como etiquetas (sin el corte actual)
    const dataCorteActual = [corteActualTotal]; // Datos del corte actual


    // Crear datasets dinámicos
    const datasets = [
        {
            label: corteActualNombre,
            data: dataCorteActual.concat(new Array(labels.length).fill(null)), // Agrega null para alinear con otros datasets
            backgroundColor: 'rgba(255, 99, 132, 0.5)', // Rojo
            borderColor: 'rgba(255, 99, 132, 1)', // Rojo
            borderWidth: 1
        },
        ...labels.map((label, index) => ({
            label: label, // Nombre del corte
            data: [null, ...Array(labels.length).fill(0).map((_, i) => i === index ? corteComparadoTotales[label] : null)],
            backgroundColor: predefinedColors[index % predefinedColors.length],
            borderColor: predefinedBorderColors[index % predefinedBorderColors.length],
            borderWidth: 1
        }))
    ];

    // Crear o actualizar la gráfica
    if (!comparisonBar) {
        comparisonBar = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: [corteActualNombre, ...labels], // Incluye el corte actual y los comparados
                datasets: datasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,    // Configura una relación de aspecto fija
                aspectRatio: 2,
                scales: {
                    x: {
                        stacked: true,
                        barPercentage: 1.0, // Las barras ocuparán todo el ancho
                        categoryPercentage: 1.0 // Las barras se expandirán dentro de la categoría
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Inspecciones'
                        }
                    }
                },
                plugins: {
                    legend: {
                        position: 'top'
                    },
                    title: {
                        display: true,
                        text: 'Comparación Total de Inspecciones'
                    },
                    tooltip: {
                        callbacks: {
                            title: (tooltipItems) => {
                                // Retorna el título del tooltip: el nombre del corte
                                return tooltipItems[0].label;
                            },
                            label: (tooltipItem) => {
                                // Agrega una segunda línea con el texto "Total inspecciones: valor"
                                const value = tooltipItem.raw; // Obtiene el valor crudo
                                return `Total inspecciones: ${value}`;
                            }
                        }
                    }
                },

            }
        });
    } else {
        comparisonBar.data.labels = [corteActualNombre, ...labels];
        comparisonBar.data.datasets = datasets;
        comparisonBar.update();
    }
}


window.addEventListener('resize', redibujarGraficos);

function redibujarGraficos(labels, meta, contratosCategoria) {

    const canva = document.querySelector('#inspeccionesDiarias').getContext('2d');
    Chart.register(ChartDataLabels);

    // Si el stackedBar no existe, lo creamos
    if (!stackedBar) {
        stackedBar = new Chart(canva, {
            type: 'bar',
            data: {
                labels: labels.map(inspector => inspector.nombres),
                datasets: [{
                    label: 'Corte actual',
                    data: labels.map(inspector => inspector.contratos),
                    backgroundColor: 'rgba(255, 99, 132, 0.5)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                layout: {
                    padding: {
                        top: 20,
                        bottom: 20
                    }
                },
                barPercentage: 0.9,
                categoryPercentage: 0.8,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Inspectores',
                            font: {
                                size: 14
                            }
                        },
                        ticks: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Cantidad de Inspecciones',
                            font: {
                                size: 14
                            }
                        },
                        ticks: {
                            callback: function (value) {
                                return value.toLocaleString(); // Formato de número
                            }
                        }
                    }
                },
                plugins: {
                    title: {
                        display: true,
                        text: 'Inspecciones Totales por Inspector',
                        font: {
                            size: 16
                        }
                    },
                    legend: {
                        position: 'top',
                        labels: {
                            font: {
                                size: 12
                            }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            title: (tooltipItems) => {
                                return tooltipItems[0].label; // Nombre del inspector
                            },
                            label: (tooltipItem) => {
                                const value = tooltipItem.raw; // Valor de inspecciones
                                return `Total inspecciones: ${value}`;
                            }
                        }
                    },
                    annotation: {
                        annotations: {
                            line1: {
                                type: 'line',
                                mode: 'horizontal',
                                scaleID: 'y',
                                value: meta, // Línea de meta
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
                        formatter: () => '', // Ocultar números en la gráfica general
                        font: {
                            size: 12,
                        },
                        color: 'black',
                        display: true,
                    }
                },
                animation: {
                    duration: 1000,
                    easing: 'easeInOutQuad'
                }
            }
        });
    } else {
        // Si el gráfico ya existe, actualizamos sus datos y configuraciones
        stackedBar.data.labels = labels.map(inspector => inspector.nombres);
        stackedBar.data.datasets[0].data = labels.map(inspector => inspector.contratos);
        stackedBar.options.plugins.annotation.annotations.line1.value = meta; // Actualizar la línea de meta
        stackedBar.options.plugins.title.text = 'Inspecciones Totales por Inspector'; // Título general
        stackedBar.options.plugins.datalabels.formatter = () => ''; // Ocultar números en la vista general

        stackedBar.update(); // Redibujar el gráfico
    }

    const canvaCategoria = document.querySelector('#categoriaContratos').getContext('2d');
    if (!pieChart) {
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
    } else {
        // Si ya existe el gráfico, solo actualizamos los datos
        pieChart.data.datasets[0].data = [contratosComerciales, contratosResidenciales];
        pieChart.update();
    }

    document.addEventListener('DOMContentLoaded', () => {
        const canvasElement = document.querySelector('#zonaContratos');

        if (!canvasElement) {
            console.error("El canvas con ID 'zonaContratos' no está presente en el DOM.");
            return;
        }

        const canvaZonas = canvasElement.getContext('2d');

        if (!ZonaPie) {
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
        } else {
            // Si ya existe el gráfico, solo actualizamos los datos
            ZonaPie.data.datasets[0].data = data;
            ZonaPie.update();
        }
    });

    // Si el inspector ha sido seleccionado, actualizamos los contratos comerciales y residenciales
    if (inspectorCedula) {
        contratosComerciales = 0;
        contratosResidenciales = 0;

        // Lógica para contar contratos comerciales y residenciales según `actualizarGrafico`
        contratosCategoria.forEach(item => {
            if (item.CC_OPERARIO === inspectorCedula) {
                if (item.CATEGORIA === 'COMERCIAL') {
                    contratosComerciales++;
                } else if (item.CATEGORIA === 'RESIDENCIAL') {
                    contratosResidenciales++;
                }
            }
        });

        // Actualizamos el gráfico de torta (pieChart) con los nuevos datos
        pieChart.data.datasets[0].data = [contratosComerciales, contratosResidenciales];
        pieChart.update();
    }
}

function InitTomSelect(MaxItems) {
    // Configuración de TomSelect
    tomSelectInstanceStackedbar = new TomSelect(cortesComparisonSelectStackedbar, {
        plugins: ['remove_button'], // Habilitar botón para eliminar opciones
        maxItems: MaxItems, // Permitir múltiples selecciones
        create: false, // No permitir crear nuevas opciones
        onChange: function (values) {
            valoresSelectStackedbar = values;
        }
    });
}

function InitTomSelectInspectores() {
    // Configuración de TomSelect
    tomSelectInspectores = new TomSelect(inspectorSelectStackedbar, {
        plugins: ['remove_button'], // Habilitar botón para eliminar opciones
        maxItems: 6, // Permitir múltiples selecciones
        create: false, // No permitir crear nuevas opciones
        onChange: function (values) {
            valoresSelectInspectores = values;
        }
    });
}

function InitTomSelectCortes() {
    // Inicializar TomSelect con las configuraciones deseadas
    let selectChoices = $('#cortesComparisonSelect')[0];
    const tomSelectInstance = new TomSelect(selectChoices, {
        plugins: ['remove_button'], // Habilitar botón para eliminar opciones
        maxItems: 6, // Permitir múltiples selecciones
        create: false, // No permitir crear nuevas opciones
        onChange: function (values) {
            valoresCortesComparisonSelect = values;
        },
    });
}

async function actualizarDatosCortesStackedBar(corteIds, inspectorCedula) {

    const cortesSinDatos = []; // Acumular cortes sin datos aquí

    // Obtener el corte actual desde el atributo del selector
    const corteActualId = $('#cortesSelect').data('corte-actual');

    const fetchPromises = corteIds.map(async (corteId,index) => {
        // Omitir la solicitud si el corte seleccionado es el mismo que el actual
        if (corteId === corteActualId) {
            console.log(`El corte ${corteId} es el mismo que el corte actual (${corteActualId}). Omitiendo solicitud.`);
            return null;
        }

        try {
            const response = await fetch(`produccion/getCorteData`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({
                    id: corteId,
                    inspector_cc: inspectorCedula,
                }),
            });

            const data = await response.json();

            if (data.error) {
                console.error(`Error al obtener datos para el corte ${corteId}:`, data.error);
                return null;
            }

            // Si el controlador devuelve un mensaje indicando que no se hace comparación
            if (data.message) {
                console.log(data.message);
                return null;
            }

            // console.log("Datos recibidos para el corte:", data);

            const nombreCorte = data.nombreCorte;
            const ccInspector = $('#inspectorSelectStackedbar').val(); // Cedula del inspector seleccionado

            if (ccInspector.length === 0) {
                    return {
                        label: nombreCorte,
                        data:labels.map(inspector => {
                            const match = data.produccionInspector.find((d) => d.cedula === inspector.cedula);
                            return match ? match.contratos || 0 : 0;
                        }),
                        backgroundColor: predefinedColors[corteIds.indexOf(corteId) % predefinedColors.length],
                        borderColor: predefinedBorderColors[corteIds.indexOf(corteId) % predefinedBorderColors.length],
                        borderWidth: 1,
                    };
            } else {
                // Mapa de producción (el mismo de antes)

                const produccionMap = data.produccionInspector.reduce((mapa, insp) => {

                        mapa[insp.cedula] = insp.contratos;
                    return mapa;

                }, {});

                let finalData = [];

                ccInspector.forEach(cedula => {

                    // 2. Buscamos el inspector completo en el arreglo `labels` para obtener su nombre.
                    const inspectorInfo = labels.find(inspector => inspector.cedula === cedula);

                    // 3. Consultamos los contratos en nuestro mapa eficiente (esto es rápido).
                    const contratos = produccionMap[cedula];

                    // 4. Verificamos que encontramos toda la información necesaria.
                    if (inspectorInfo && contratos !== undefined) {
                        // Como el bucle sigue el orden de 'ccInspector', los datos se agregan en ese orden.
                        finalData.push(contratos);
                    }
                });

                return {
                    label: nombreCorte,
                    data: finalData, // <--- Este arreglo ya está en el orden que quieres.
                    backgroundColor: predefinedColors[corteIds.indexOf(corteId) % predefinedColors.length],
                    borderColor: predefinedBorderColors[corteIds.indexOf(corteId) % predefinedBorderColors.length],
                    borderWidth: 1,
                };
            }
        } catch (error) {
            console.error(`Error al procesar el corte ${corteId}:`, error);
            return null;
        }
    });

    // Esperar todas las promesas
    const datasets = await Promise.all(fetchPromises);

    // Filtrar datasets válidos y actualizar el gráfico
    stackedBar.data.datasets = [stackedBar.data.datasets[0], ...datasets.filter((dataset) => dataset)];
    stackedBar.update();

    // Mostrar una sola alerta consolidada si hay cortes sin datos
    if (cortesSinDatos.length > 0) {
        await Swal.fire({
            title: 'Advertencia',
            html: `Los siguientes cortes no tienen datos disponibles:<br><strong>${cortesSinDatos.join(
                ', '
            )}</strong>`,
            icon: 'warning',
            confirmButtonText: 'Aceptar',
        });
    }
}

function actualizarDatosGraficoComparacion(values) {
    // Mostrar el indicador de carga
    Swal.fire({
        title: 'Cargando',
        text: 'Agregando el corte seleccionado...',
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading()
        }
    })

    // Limpiar `corteComparadoTotales` para reflejar la selección actual
    corteComparadoTotales = {};

    if (values.length === 0) {
        // Si no hay selecciones, limpiar y deshabilitar pestaña
        if (comparisonBar) {
            actualizarGraficoComparacion();
        }
        // Ocultar el indicador de carga
        Swal.close()
        return;
    }

    fetch(`produccion/getCorteTotalData`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        },
        body: JSON.stringify({cortes: values})
    })
        .then(response => response.json())
        .then(data => {

            if (data.error) {
                console.error('Error en la solicitud:', data.error);
                Swal.fire('Error', 'Ocurrió un error al obtener los datos.', 'error');
                return;
            }

            if (data.message) {
                console.log(data.message);
                return;
            }
            // Procesar datos y actualizar el gráfico
            values.map(corteId => {
                data.map(corte => {
                    if (Number(corteId) === Number(corte.id)) {
                        corteComparadoTotales[corte.nombreCorte] = corte.totalContratos;
                    }
                });
            });

            // Llamar a la función para actualizar la gráfica
            actualizarGraficoComparacion();
        })
        .catch((error) => {
            console.error('Error al procesar la solicitud:', error);
            Swal.fire('Error', 'No se pudo completar la solicitud.', 'error');
        })
        .finally(() => {
            Swal.close(); // Finalmente siempre oculta el indicador de carga
        });


}

async function BuscarResultados(inspectorCedula) {
    const loadingModal = Swal.fire({
        title: 'Cargando',
        text: 'Cargando el inspector seleccionado...',
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    //await new Promise(resolve => setTimeout(resolve, 3500)); // Simulamos un pequeño retraso


    if (inspectorCedula !== '') {
        // Inicializas el array vacío
        let selectedInspector = [];

        // Usamos forEach porque solo queremos iterar, no transformar el array original
        inspectorCedula.forEach(inspectorS => {
            // Buscamos el inspector correspondiente en 'labels'
            const inspectorEncontrado = labels.find(inspector => inspector.cedula === inspectorS);

            // Si se encontró, lo agregamos al array con push()
            if (inspectorEncontrado) {
                selectedInspector.push(inspectorEncontrado);
            }
        });


        // console.log(selectedInspector)
        if (!selectedInspector) {
            loadingModal.close();
            await Swal.fire({
                title: 'Sin inspecciones',
                text: 'El inspector seleccionado no tiene contratos asociados en uno o mas cortes.',
                icon: 'warning',
                confirmButtonText: 'Aceptar'
            });
            return;
        }

        let newLabels = []
        let newData = []
        let tituloBase = "Inspecciones Totales de "
        // 1. Crea un array solo con los nombres.
        const nombresDeInspectores = selectedInspector.map(inspector => inspector.nombres);

        // 2. Une los nombres con ", " y añádelos al título base.
        let titulo = tituloBase + nombresDeInspectores.join(', ');

        selectedInspector.map(inspector => {

            newLabels.push([inspector.nombres]);
            newData.push([inspector.contratos]);

        })
        actualizarGrafico(newLabels, newData, titulo, true, true);


    } else {
        loadingModal.close();
        const allLabels = labels.map(inspector => inspector.nombres);
        const allData = labels.map(inspector => inspector.contratos);
        const tituloOriginal = 'Inspecciones Totales por Inspector';

        // Mantener el tooltip estándar para el gráfico principal
        actualizarGrafico(allLabels, allData, tituloOriginal, true, false);
    }
    if (inspectorCedula === '') {
        // Mostrar el indicador de carga
        const loadingModal = Swal.fire({
            title: 'Cargando',
            text: 'Restaurando gráfica principal...',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Simular un pequeño retraso para una transición visual suave
        //await new Promise(resolve => setTimeout(resolve, 2000));


        // Limpiar el TomSelect
        tomSelectInstanceStackedbar.clear();

        // Cerrar el indicador de carga
        loadingModal.close();
        return;
    }
    loadingModal.close();

}

function ActualizarDatosGraficosInspectores(values, inspectorCedula) {

    // Mostrar indicador de carga antes de cargar datos para nuevos valores
    Swal.fire({
        title: 'Cargando',
        text: 'Agregando el corte seleccionado con los datos del inspector...',
        showConfirmButton: false,
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });

    // Actualizar los datos de comparación en el stackedBar
    actualizarDatosCortesStackedBar(values, inspectorCedula)
        .then(() => {
            stackedBar.update(); // Refrescar el gráfico
            Swal.close();
        })
        .catch((error) => {
            console.error('Error al actualizar datos:', error);
            Swal.close();
            Swal.fire('Error', 'Ocurrió un problema al cargar los datos.', 'error');
        });


    // Actualizar valores previos para la próxima detección
    valoresPreviosStackedbar = [...values];
}

async function restaurarGraficoPrincipal() {
    try {
        // Obtener los datos generales (todos los inspectores)
        const allLabels = labels.map(inspector => inspector.nombres);
        const allData = labels.map(inspector => inspector.contratos);
        const tituloOriginal = 'Inspecciones Totales por Inspector';
        // Restauramos el gráfico principal con todos los inspectores
        actualizarGrafico(allLabels, allData, tituloOriginal, true);

        // Obtener los datos para el corte actual
        // const datasetCorteActual = await obtenerDatosComparados([corte_id]);
        /*
                // Verificar si el corte comparado ya está en los datasets
                if (datasetComparacion && datasetComparacion.length > 0 && !datasetYaAgregado(datasetComparacion)) {
                    stackedBar.data.datasets.push(...datasetComparacion); // Añadir los datos del corte comparado
                }*/

        // Verificar si el corte actual ya está en los datasets
        /* if (datasetCorteActual && datasetCorteActual.length > 0 && !datasetYaAgregado(datasetCorteActual)) {
             stackedBar.data.datasets.push(...datasetCorteActual); // Añadir los datos del corte actual
         }*/
        mantenerSoloPrimerBarra();
        // Actualizar el gráfico
        stackedBar.update();
    } catch (error) {
        console.error('Error al restaurar el gráfico principal:', error);
    }
}

function mantenerSoloPrimerBarra() {
    if (stackedBar && stackedBar.data.datasets.length > 1) {
        // Mantener solo el primer dataset (la primera barra)
        stackedBar.data.datasets = [stackedBar.data.datasets[0]];

        // Actualizar el gráfico
        stackedBar.update();
    }
}



