let stackedBar;
const canva = document.querySelector('#inspeccionesDiarias').getContext('2d');

// Acceder a las variables definidas en window.appData
const meta = window.appData.meta;
const contratosCategoria = window.appData.contratosCategoria;
const labels = window.appData.labels;

// Selección de los contextos de los canvas
const canvaCategoriaInsp = document.querySelector('#categoriaInsp').getContext('2d');
const canvaZonasInsp = document.querySelector('#zonasInsp').getContext('2d');
const canvaCategoriaContratos = document.querySelector('#categoriaContratos')?.getContext('2d');
const canvaZonasContratos = document.querySelector('#zonaContratos')?.getContext('2d');

Chart.register(ChartDataLabels);

stackedBar = new Chart(canva, {
    type: 'bar',
    data: {
        labels: labels.map(inspector => `${inspector.nombres}`), // Etiquetas únicas para cada barra
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
        scales: {
            x: {
                stacked: false,
                barPercentage: 0.5, // Controla el ancho de la barra
                categoryPercentage: 0.8, // Espaciado entre barras
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
                    },
                    autoSkip: false // Evita que se salten etiquetas
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
                        size: 12
                    }
                }
            },
            tooltip: {
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
                    }
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
    const inspectorSelectStackedbar = document.getElementById('inspectorSelectStackedbar');

    inspectorSelectStackedbar.addEventListener('change', async (event) => {
        const inspectorCedula = event.target.value;

        // Mostrar indicador de carga al iniciar el proceso
        const loadingModal = Swal.fire({
            title: 'Cargando',
            text: 'Cargando el inspector seleccionado...',
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        await new Promise(resolve => setTimeout(resolve, 3500)); // Simulamos un pequeño retraso

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

        if (inspectorCedula !== '') {
            const selectedInspector = labels.find(inspector => inspector.cedula === inspectorCedula);

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

            const newLabels = [selectedInspector.nombres];
            const newData = [selectedInspector.contratos];
            const titulo = `Inspecciones Totales de ${selectedInspector.nombres}`;

            // Mostrar tooltips con los nombres de los cortes
            actualizarGrafico(newLabels, newData, titulo, true, true);
        } else {
            loadingModal.close();
            const allLabels = labels.map(inspector => inspector.nombres);
            const allData = labels.map(inspector => inspector.contratos);
            const tituloOriginal = 'Inspecciones Totales por Inspector';

            // Mantener el tooltip estándar para el gráfico principal
            actualizarGrafico(allLabels, allData, tituloOriginal, true, false);
        }

        loadingModal.close();
    });
});

document.addEventListener('DOMContentLoaded', () => {
    const inspectorSelectStackedbar = document.getElementById('inspectorSelectStackedbar');
    const cortesSelect = document.getElementById('cortesSelect');

    function filtrarInspectoresPorCorte(corteActualId) {
        // Obtener el ID del corte actual desde el atributo `data-corte-actual`
        const corteActual = cortesSelect.dataset.corteActual;

        // Si el corte seleccionado es el mismo que el actual, no hacer fetch
        if (corteActualId === corteActual) {
            console.log(`El corte ${corteActualId} es el mismo que el corte actual (${corteActual}). Filtrando inspectores localmente...`);

            // Filtrar inspectores para el corte actual directamente (simulación local con `labels`)
            const inspectoresValidos = labels.filter(inspector =>
                inspector.nombres &&               // `nombres` no vacío o nulo
                inspector.cedula &&                // `cedula` no vacío o nulo
                inspector.contratos !== undefined  // `contratos` está definido
            );

            // Limpiar opciones actuales del select
            inspectorSelectStackedbar.innerHTML = `<option value="">Todos los inspectores</option>`;

            // Poblar opciones con inspectores válidos
            inspectoresValidos.forEach(inspector => {
                const option = document.createElement('option');
                option.value = inspector.cedula;
                option.textContent = inspector.nombres;
                inspectorSelectStackedbar.appendChild(option);
            });

            return;
        }

        // Si no es el mismo corte, realizar la solicitud al servidor
        fetch('produccion/getCorteData', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ id: corteActualId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(`Error al obtener datos para el corte ${corteActualId}:`, data.error);
                    return;
                }

                // Manejar mensaje del servidor si el corte seleccionado es igual al actual
                if (data.message) {
                    console.log(data.message);
                    inspectorSelectStackedbar.innerHTML = `<option value="">Todos los inspectores</option>`;
                    return;
                }

                // Filtrar inspectores con contratos > 0
                const inspectoresConContratos = data.produccionInspector.filter(inspector =>
                    inspector.contratos > 0
                );

                // Filtrar inspectores para el corte actual directamente (simulación local con `labels`)
                const inspectoresValidos = labels.filter(inspector =>
                    inspector.nombres &&               // `nombres` no vacío o nulo
                    inspector.cedula &&                // `cedula` no vacío o nulo
                    inspector.contratos !== undefined  // `contratos` está definido
                );

                // Combina ambas listas si necesitas fusionarlas o mantenerlas separadas
                const inspectoresCombinados = inspectoresConContratos.filter(inspector =>
                    inspectoresValidos.some(validInspector =>
                        validInspector.nombres === inspector.nombres &&
                        validInspector.cedula === inspector.cedula
                    )
                );

                // Limpiar opciones actuales del select
                inspectorSelectStackedbar.innerHTML = `<option value="">Todos los inspectores</option>`;

                // Poblar opciones con inspectores válidos
                inspectoresCombinados.forEach(inspector => {
                    const option = document.createElement('option');
                    option.value = inspector.cedula;
                    option.textContent = inspector.nombres;
                    inspectorSelectStackedbar.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error al filtrar inspectores:', error);
            });
    }

    // Filtrar inspectores al cargar por primera vez
    const corteActualId = cortesSelect.value; // Obtener el corte actual seleccionado
    if (corteActualId) {
        filtrarInspectoresPorCorte(corteActualId);
    }

    // Actualizar inspectores al cambiar de corte
    cortesSelect.addEventListener('change', (event) => {
        const nuevoCorteId = event.target.value;
        filtrarInspectoresPorCorte(nuevoCorteId);
    });

    const cortesComparisonSelectStackedbar = document.getElementById('cortesComparisonSelectStackedbar');

    // Función para limpiar los datos de comparación
    function limpiarDatosComparacion() {
        if (stackedBar.data.datasets.length > 1) {
            stackedBar.data.datasets = [stackedBar.data.datasets[0]];
            stackedBar.update();
        }
    }

    // Variable para guardar el estado anterior de los valores seleccionados
    let valoresPreviosStackedbar = [];

    // Configuración de TomSelect
    const tomSelectInstanceStackedbar = new TomSelect(cortesComparisonSelectStackedbar, {
        plugins: ['remove_button'], // Habilitar botón para eliminar opciones
        maxItems: 6, // Permitir múltiples selecciones
        create: false, // No permitir crear nuevas opciones
        onChange: function (values) {
            // Detectar si se eliminó un valor comparando con valoresPreviosStackedbar
            const valoresEliminados = valoresPreviosStackedbar.filter(val => !values.includes(val));

            if (valoresEliminados.length > 0) {
                // Mostrar indicador de eliminación
                Swal.fire({
                    title: 'Cargando',
                    text: 'Eliminando el corte seleccionado...',
                    showConfirmButton: false,
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Simular una operación de actualización para el gráfico
                setTimeout(() => {
                    actualizarDatosCortesStackedBar(values) // Recargar datos con los valores actuales
                        .then(() => {
                            stackedBar.update(); // Refrescar el gráfico después de actualizar los datos
                            Swal.close(); // Cerrar el indicador de carga
                        })
                        .catch((error) => {
                            console.error('Error al actualizar datos:', error);
                            Swal.close();
                            Swal.fire('Error', 'Ocurrió un problema al cargar los datos.', 'error');
                        });
                }, 2000);

            } else {
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
                actualizarDatosCortesStackedBar(values)
                    .then(() => {
                        stackedBar.update(); // Refrescar el gráfico
                        Swal.close();
                    })
                    .catch((error) => {
                        console.error('Error al actualizar datos:', error);
                        Swal.close();
                        Swal.fire('Error', 'Ocurrió un problema al cargar los datos.', 'error');
                    });
            }

            // Actualizar valores previos para la próxima detección
            valoresPreviosStackedbar = [...values];
        }
    });

    // Verificar si hay un corte seleccionado previamente en el 'cortesSelect'
    const preselectedCorte = document.querySelector('#cortesSelect').value;

    if (preselectedCorte) {
        // Sincronizar el TomSelect con el valor preseleccionado del cortesSelect
        agregarCorteATomSelect(preselectedCorte);

        // Actualizar los datos del gráfico con el corte preseleccionado
        actualizarDatosCortesStackedBar([preselectedCorte]);
    }

    // Función para agregar un corte al TomSelect
    function agregarCorteATomSelect(corteId) {
        const corteActual = document.querySelector('#cortesSelect').dataset.corteActual;

        // No agregar el corte actual al selector
        if (corteId === corteActual) {
            console.log(`El corte ${corteId} es el actual y no se agregará al TomSelect.`);
            return;
        }

        // Verificar si el corte ya existe en las opciones de TomSelect
        if (!tomSelectInstanceStackedbar.options[corteId]) {
            tomSelectInstanceStackedbar.addOption({
                value: corteId,
                text: document.querySelector(`#cortesSelect option[value="${corteId}"]`).textContent
            });
        }
        tomSelectInstanceStackedbar.addItem(corteId);
    }

    // Evento de cambio en el selector de inspectores
    document.getElementById('inspectorSelectStackedbar').addEventListener('change', async (event) => {
        const inspectorCedula = event.target.value;

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
            await new Promise(resolve => setTimeout(resolve, 2000));

            // Restaurar la visibilidad de cortesSelect y limpiar TomSelect
            cortesSelect.style.display = 'block';
            tomSelectWrapper.style.display = 'none';

            // Obtener el corte preseleccionado en cortesSelect
            const preselectedCorte = document.querySelector('#cortesSelect').value;

            // Restaurar el gráfico principal con el corte preseleccionado
            if (preselectedCorte) {
                //await restaurarGraficoPrincipal(preselectedCorte);
            }

            // Limpiar el TomSelect
            tomSelectInstanceStackedbar.clear();

            // Cerrar el indicador de carga
            loadingModal.close();
            return;
        }

        // Si se selecciona un inspector específico, oculta cortesSelect y muestra TomSelect
        cortesSelect.style.display = 'none';
        tomSelectWrapper.style.display = 'block';

        // Si hay un preseleccionado en cortesSelect, sincronizar con TomSelect
        const preselectedCorte = document.querySelector('#cortesSelect').value;
        const corteActual = document.querySelector('#cortesSelect').dataset.corteActual;

        if (preselectedCorte && preselectedCorte !== corteActual) {
            agregarCorteATomSelect(preselectedCorte);
        }
    });

    // Función para restaurar el gráfico principal con el corte preseleccionado
    async function restaurarGraficoPrincipal(preselectedCorte) {

        try {


            // Obtener los datos generales (todos los inspectores)
            const allLabels = labels.map(inspector => inspector.nombres);
            const allData = labels.map(inspector => inspector.contratos);
            const tituloOriginal = 'Inspecciones Totales por Inspector';

            // Restauramos el gráfico principal con todos los inspectores
            actualizarGrafico(allLabels, allData, tituloOriginal, true);

            // Verificar si ya existe el corte preseleccionado en los datasets antes de agregarlo
            const datasetComparacion = await obtenerDatosComparados([preselectedCorte]);
            const corteActualId = document.querySelector('#cortesSelect').value; // Obtener el corte actual

            // Obtener los datos para el corte actual
            const datasetCorteActual = await obtenerDatosComparados([corteActualId]);

            // Verificar si el corte comparado ya está en los datasets
            if (datasetComparacion && datasetComparacion.length > 0 && !datasetYaAgregado(datasetComparacion)) {
                stackedBar.data.datasets.push(...datasetComparacion); // Añadir los datos del corte comparado
            }

            // Verificar si el corte actual ya está en los datasets
            if (datasetCorteActual && datasetCorteActual.length > 0 && !datasetYaAgregado(datasetCorteActual)) {
                stackedBar.data.datasets.push(...datasetCorteActual); // Añadir los datos del corte actual
            }

            // Actualizar el gráfico
            stackedBar.update();
        } catch (error) {
            console.error('Error al restaurar el gráfico principal:', error);
        }
    }

    // Función para verificar si un dataset ya ha sido agregado al gráfico
    function datasetYaAgregado(nuevoDataset) {
        return stackedBar.data.datasets.some(dataset => {
            return dataset.label === nuevoDataset[0].label; // Compara el label del corte para verificar duplicados
        });
    }

    // Función para obtener los datos comparados de los cortes seleccionados
    async function obtenerDatosComparados(corteIds) {
        const fetchPromises = corteIds.map(corteId =>
            fetch('produccion/getCorteData', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ id: corteId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(`Error al obtener datos para el corte ${corteId}:`, data.error);
                        return null;
                    }

                    return {
                        label: data.nombreCorte,
                        data: data.produccionInspector.map(d => d.contratos || 0),
                        backgroundColor: 'rgba(154, 208, 245)', // Color para corte comparado
                        borderColor: 'rgba(5, 155, 255)',
                        borderWidth: 1
                    };
                })
        );

        const datasets = await Promise.all(fetchPromises);
        return datasets.filter(dataset => dataset !== null); // Filtrar los nulos en caso de errores
    }

    // Obtener el contenedor generado por TomSelect
    const tomSelectWrapper = cortesComparisonSelectStackedbar.parentNode.querySelector('.ts-wrapper');

    // Ocultamos inicialmente el contenedor de comparación
    if (tomSelectWrapper) {
        tomSelectWrapper.style.display = 'none';
    }

    // Escuchamos el evento change del selector de inspectores
    inspectorSelectStackedbar.addEventListener('change', async (event) => {
        const inspectorCedula = event.target.value;

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
            await new Promise(resolve => setTimeout(resolve, 3000));

            // Restaurar la visibilidad de cortesSelect y limpiar TomSelect
            cortesSelect.style.display = 'block';
            tomSelectWrapper.style.display = 'none';
            tomSelectInstanceStackedbar.clear();

            // Restaurar datos del gráfico principal
            const allLabels = labels.map(inspector => inspector.nombres);
            const allData = labels.map(inspector => inspector.contratos);
            const tituloOriginal = 'Inspecciones Totales por Inspector';

            actualizarGrafico(allLabels, allData, tituloOriginal, true);

            // Cerrar el indicador de carga
            loadingModal.close();
        } else {
            // Actualizar los datos de comparación con el inspector seleccionado
            const selectedCorteIds = tomSelectInstanceStackedbar.getValue();
            if (selectedCorteIds.length > 0) {
                actualizarDatosCortesStackedBar(selectedCorteIds);
            }
        }
    });

    // Función para actualizar los datos de cualquier gráfico
    function actualizarGrafico(labels, data, titulo, mostrarDatalabels = true) {
        stackedBar.data.labels = labels;
        stackedBar.data.datasets[0].data = data;
        if (Array.isArray(labels)) {
            console.log("HOLA");
        } else {
            console.error('labels no está definido correctamente:', labels);
        }
        stackedBar.options.plugins.title.text = titulo;

        // Activar o desactivar datalabels dinámicamente
        stackedBar.options.plugins.datalabels.display = mostrarDatalabels;
        stackedBar.update(); // Redibujar el gráfico
    }

    async function actualizarDatosCortesStackedBar(corteIds, inspectorCedula) {
        const cortesSinDatos = []; // Acumular cortes sin datos aquí

        // Obtener el corte actual desde el atributo del selector
        const corteActualId = $('#cortesSelect').data('corte-actual');

        const fetchPromises = corteIds.map(async (corteId) => {
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
                    body: JSON.stringify({ id: corteId }),
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

                if (ccInspector) {
                    const match = data.produccionInspector.find((d) => d.cedula === ccInspector);

                    if (match && match.contratos > 0) {
                        return {
                            label: nombreCorte,
                            data: [match.contratos || 0],
                            backgroundColor: predefinedColors[corteIds.indexOf(corteId) % predefinedColors.length],
                            borderColor: predefinedBorderColors[corteIds.indexOf(corteId) % predefinedBorderColors.length],
                            borderWidth: 1,
                        };
                    } else {
                        // Si no hay datos, acumular el corte en la lista
                        cortesSinDatos.push(nombreCorte);
                        return null;
                    }
                } else {
                    // Si no hay inspector seleccionado, retornar datos para todos los inspectores
                    return {
                        label: nombreCorte,
                        data: labels.map((inspector) => {
                            const match = data.produccionInspector.find((d) => d.cedula === ccInspector);
                            return match ? match.contratos || 0 : 0;
                        }),
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
});

let comparisonBar; // Variable global para la gráfica de comparación
let corteComparadoTotales = {}; // Almacena totales de inspecciones para cortes seleccionados
const corteActualTotal = labels.reduce((acc, curr) => acc + curr.contratos, 0); // Total del corte actual

document.addEventListener('DOMContentLoaded', () => {
    // Mostrar la barra del corte actual al cargar la página
    actualizarGraficoComparacion();

    // Verificar si hay un corte seleccionado previamente en el 'cortesSelect'
    const preselectedCorte = document.querySelector('#cortesSelect').value;
    if (preselectedCorte) {
        agregarCorteATomSelect(preselectedCorte); // Sincronizar con el TomSelect
        actualizarDatosCorte(preselectedCorte); // Agregar barra del corte preseleccionado al gráfico
    }
});

// Función para agregar un corte al TomSelect y sincronizar gráfica
function agregarCorteATomSelect(corteId) {
    const tomSelectInstance = TomSelect.getInstance(selectChoices);
    if (!tomSelectInstance.options[corteId]) {
        // Agregar la opción al TomSelect si no existe
        tomSelectInstance.addOption({ value: corteId, text: `Corte ${corteId}` });
    }
    tomSelectInstance.addItem(corteId); // Seleccionar el corte
    tomSelectInstance.refreshOptions(false); // Refrescar opciones
}

// Función para actualizar los datos de un corte y sincronizarlos con la gráfica
function actualizarDatosCorte(corteId) {
    fetch(`produccion/getCorteData`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        body: JSON.stringify({ id: corteId })
    })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                console.error(`Error al obtener datos para el corte ${corteId}:`, data.error);
                return;
            }
            // Calcular el total de inspecciones para este corte
            const corteTotal = data.reduce((acc, curr) => acc + curr.contratos, 0);
            corteComparadoTotales[corteId] = corteTotal; // Almacenar el total en el objeto global
            actualizarGraficoComparacion(); // Actualizar gráfica con los datos más recientes
        })
        .catch(error => console.error('Error:', error));
}

// Inicializar TomSelect con las configuraciones deseadas
let selectChoices = $('#cortesComparisonSelect')[0];
const tomSelectInstance = new TomSelect(selectChoices, {
    plugins: ['remove_button'], // Habilitar botón para eliminar opciones
    maxItems: 6, // Permitir múltiples selecciones
    create: false, // No permitir crear nuevas opciones
    onChange: function (values) {
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

        toggleComparisonTab(true);

        // Llamar a la función para actualizar los datos de comparación
        actualizarDatosComparacion(values);


        // Obtener el ID del corte actual desde el atributo `data-corte-actual`
        const corteActualId = document.getElementById('cortesSelect').dataset.corteActual;

        // Iterar por cada ID seleccionado y obtener datos de inspecciones
        const fetchPromises = values.map(corteId => {
            // Verificar si el corte seleccionado es igual al actual
            if (corteId === corteActualId) {
                console.log(`El corte ${corteId} es el mismo que el corte actual (${corteActualId}). No se realiza ninguna solicitud.`);
                return Promise.resolve(); // Resolver la promesa sin hacer el fetch
            }

            return fetch(`produccion/getCorteData`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                },
                body: JSON.stringify({ id: corteId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        console.error(`Error al obtener datos para el corte ${corteId}:`, data.error);
                        return;
                    }

                    // Manejar mensaje del servidor si el corte seleccionado es igual al actual
                    if (data.message) {
                        console.log(data.message); // Log del mensaje del servidor
                        return;
                    }

                    // Calcular el total de inspecciones para este corte
                    const corteTotal = data.produccionInspector.reduce((acc, curr) => acc + curr.contratos, 0);
                    corteComparadoTotales[data.nombreCorte] = corteTotal;
                })
                .catch(error => {
                    console.error(`Error al procesar el corte ${corteId}:`, error);
                });
        });

        // Esperar a que se completen todas las solicitudes y actualizar el gráfico
        Promise.all(fetchPromises).then(() => {
            actualizarGraficoComparacion();
            // Ocultar el indicador de carga después de completar la operación
            Swal.close()
        });
    },
    onItemRemove: function (value) {
        // Mostrar el indicador de carga para la eliminación
        Swal.fire({
            title: 'Cargando',
            text: `Eliminando el corte seleccionado...`,
            showConfirmButton: false,
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        // Verificar si es el último elemento eliminado
        setTimeout(() => {
            const selectedValues = tomSelectInstance.getValue();
            if (selectedValues.length === 0) {
                // Si ya no hay cortes seleccionados, cerrar el indicador
                Swal.close();
            } else {
                console.log(`El corte ${value} eliminado. Aún hay cortes seleccionados.`);
            }

            actualizarGraficoComparacion();
        }, 2000); // Simulación de tiempo de procesamiento
    }
});

// Función para habilitar/deshabilitar pestaña de comparación
function toggleComparisonTab(enable) {
    const comparacionTab = document.querySelector('#comparacion-tab');
    if (enable) {
        comparacionTab.classList.remove('disabled');
        comparacionTab.setAttribute('aria-disabled', 'false');
    } else {
        comparacionTab.classList.add('disabled');
        comparacionTab.setAttribute('aria-disabled', 'true');
    }
}

// Función para agregar un corte al TomSelect y sincronizar gráfica
function agregarCorteATomSelect(corteId) {
    const tomSelectInstance = TomSelect.getInstance(selectChoices);
    if (!tomSelectInstance.options[corteId]) {
        // Agregar la opción al TomSelect si no existe
        tomSelectInstance.addOption({ value: corteId, text: `Corte ${corteId}` });
    }
    tomSelectInstance.addItem(corteId); // Seleccionar el corte
    tomSelectInstance.refreshOptions(false); // Refrescar opciones
}

// Función para actualizar o dibujar la gráfica de comparación
function actualizarGraficoComparacion() {
    const ctx = document.getElementById('comparacionInspecciones').getContext('2d');

    // Obtén el nombre del corte actual (puedes ajustar este valor según tu fuente de datos actual)
    const corteActualNombre = 'Corte Actual'; // Reemplaza con el nombre dinámico si está disponible

    // Define los datos para la gráfica
    const labels = Object.keys(corteComparadoTotales); // Nombres de los cortes como etiquetas (sin el corte actual)
    const dataCorteActual = [corteActualTotal]; // Datos del corte actual

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
                maintainAspectRatio: false
            }
        });
    } else {
        comparisonBar.data.labels = [corteActualNombre, ...labels];
        comparisonBar.data.datasets = datasets;
        comparisonBar.update();
    }
}


// Sincronizar cambios de `cortesSelect` con `TomSelect`
document.querySelector('#cortesSelect').addEventListener('change', function () {
    const corteId = this.value;
    const corteActualId = this.dataset.corteActual;

    // Si no se selecciona un corte, restablecer las gráficas y deshabilitar la pestaña de comparación
    if (!corteId || corteId === corteActualId) {
        toggleComparisonTab(false); // Deshabilitar la pestaña de comparación
        if (stackedBar.data.datasets.length > 1) {
            stackedBar.data.datasets.pop(); // Quita el último dataset (de comparación)
            stackedBar.update();
        }
        if (comparisonBar) {
            comparisonBar.data.datasets[0].data = [0, 0];
            comparisonBar.update();
        }
        return;
    }

    // Habilitar la pestaña de comparación
    toggleComparisonTab(true);

    // Limpiar todas las opciones actuales de TomSelect
    tomSelectInstance.clear();

    // Agregar la opción seleccionada al TomSelect si aún no existe
    if (!tomSelectInstance.options[corteId]) {
        const corteText = this.options[this.selectedIndex].text;
        tomSelectInstance.addOption({ value: corteId, text: corteText });
    }
    tomSelectInstance.addItem(corteId);
});

// Función para actualizar datos de comparación
function actualizarDatosComparacion(values) {
    // Obtener el ID del corte actual desde el atributo `data-corte-actual`
    const cortesSelect = document.getElementById('cortesSelect');
    const corteActualId = cortesSelect.dataset.corteActual;

    const fetchPromises = values.map(corteId => {
        // Verificar si el corte seleccionado es igual al actual
        if (corteId === corteActualId) {
            console.log(`El corte ${corteId} es el mismo que el corte actual (${corteActualId}). No se realizará ninguna comparación.`);

            // Si el dataset de comparación existe, eliminarlo
            if (stackedBar.data.datasets.length > 1) {
                stackedBar.data.datasets.pop();
                stackedBar.update();
                console.log('Dataset de comparación eliminado.');
            }
            return Promise.resolve(); // Resolver la promesa sin hacer el fetch
        }

        return fetch(`produccion/getCorteData`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            },
            body: JSON.stringify({ id: corteId })
        })
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(`Error al obtener datos para el corte ${corteId}:`, data.error);
                    return;
                }

                // Crear el nuevo dataset para las inspecciones comparadas
                const newDataset = {
                    label: `Corte comparado`,
                    data: labels.map(inspector => {
                        const match = data.produccionInspector.find(d => d.nombres === inspector.nombres);
                        return match ? match.contratos : 0;
                    }),
                    backgroundColor: 'rgba(54, 162, 235, 0.5)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1,
                    barPercentage: 0.9,
                    maxBarThickness: 60
                };

                // Si ya existe un dataset de comparación, lo reemplazamos; si no, lo agregamos
                if (stackedBar.data.datasets.length > 1) {
                    stackedBar.data.datasets[1] = newDataset;
                } else {
                    stackedBar.data.datasets.push(newDataset);
                }

                // Actualizar la gráfica principal
                stackedBar.update();

                const corteComparadoTotales = data.produccionInspector.reduce((acc, curr) => acc + curr.contratos, 0);
                const corteActualTotal = labels.reduce((acc, curr) => acc + curr.contratos, 0);

                actualizarGraficoComparacion(corteActualTotal, corteComparadoTotales);
            })
            .catch(error => {
                console.error(`Error al procesar el corte ${corteId}:`, error);
            });
    });

    // Ejecutar todas las promesas y manejar los resultados
    Promise.all(fetchPromises)
        .then(() => console.log('Actualización de datos de comparación completa.'))
        .catch(error => console.error('Error al actualizar los datos de comparación:', error));
}

let pieChart;
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
        pieChart = new Chart(canvaCategoriaInsp, {
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

document.addEventListener('DOMContentLoaded', () => {
    // Obtener los datos de ContratosZonas desde window.appData
    const contratosZonas = window.appData?.contratosZonas;

    if (!contratosZonas || contratosZonas.length === 0) {
        console.error("La variable 'ContratosZonas' no está definida o está vacía.");
        return;
    }

    // Crear arrays para las zonas y los datos
    const zona = [];
    const data = [];

    for (let i = 0; i < contratosZonas.length; i++) {
        zona.push(contratosZonas[i].zona);
        data.push(contratosZonas[i].contratos);
    }

    // Seleccionar el contexto del canvas
    const zonasInsp = document.querySelector('#zonasInsp')?.getContext('2d');
    if (!zonasInsp) {
        console.error("El elemento con ID 'zonasInsp' no está definido en el DOM.");
        return;
    }

    // Crear el gráfico de tipo 'pie'
    const ZonaPie = new Chart(zonasInsp, {
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
            responsive: true,
            plugins: {
                datalabels: {
                    formatter: (value, context) => {
                        return value; // Mostrar el valor de la cantidad de contratos en el gráfico
                    }
                }
            },
            maintainAspectRatio: false,
        }
    });
});

window.addEventListener('resize', redibujarGraficos);

let inspectorCedula = null; // Variable para almacenar el inspector seleccionado
let contratosComerciales = 0;
let contratosResidenciales = 0;

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

    // Función para actualizar el gráfico con datos del nuevo corte seleccionado
    document.querySelector('#cortesSelect').addEventListener('change', function () {
        const corteId = this.value;
        if (!corteId) return; // Salir si no hay un corte seleccionado

        inspectorCedula = this.value; // Al seleccionar el corte, se actualiza el inspector
        redibujarGraficos(); // Volver a redibujar los gráficos

        // Realizar una llamada AJAX para obtener los datos del nuevo corte
        fetch(`produccion/getCorteData`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}' // Token CSRF para seguridad en Laravel
            },
            body: JSON.stringify({ id: corteId })
        })
            .then(response => response.json())
            .then(data => {
                // Verificar si hay error en la respuesta
                if (data.error) {
                    alert(data.error);
                    return;
                }

                // Actualizar el gráfico `stackedBar` con los datos de inspecciones comparadas
                const newDataset = {
                    label: 'Inspecciones Comparadas',
                    data: labels.map(inspector => {
                        const match = data.find(d => d.nombres === inspector.nombres);
                        return match ? match.contratos : 0;
                    }),
                    backgroundColor: 'rgba(54, 162, 235, 0.2)',
                    borderColor: 'rgba(54, 162, 235, 1)',
                    borderWidth: 1
                };

                // Actualizar los datasets del gráfico de barras
                if (stackedBar.data.datasets.length > 1) {
                    stackedBar.data.datasets[1] = newDataset; // Reemplaza el dataset de comparación existente
                } else {
                    stackedBar.data.datasets.push(newDataset); // Agrega el dataset de comparación si no existe
                }

                // Actualizar el gráfico de barras
                stackedBar.update();
            })
            .catch(error => console.error('Error:', error));
    });
}
