document.addEventListener("DOMContentLoaded", function() {
    const container = document.querySelector('#example');

    let hot = new Handsontable(container, {
        data: [],
        rowHeaders: true,
        colHeaders: [],
        height: 'auto',
        autoWrapRow: true,
        autoWrapCol: true,
        licenseKey: 'non-commercial-and-evaluation',
        readOnly: true // Por defecto, todo está en solo lectura
    });

    const headersGeneral = [
        'Meses', 'INSPECCION RP/AS/NV RESIDENCIAL', 'INSPECCION COMERCIAL RP/AS/NV', 'INSPECCION INDUSTRIAL',
        'TOTAL', 'No Inspectores', 'valor', 'PROMEDIO DIARIO UND', 'PROMEDIO POR INSPECTOR UND', 'PROMEDIO DIARIO $',
        'PROMEDIO POR INSPECTOR $', 'META E&C', '% CUMPL', 'META GDO', '% CUMPL'
    ];

    const headersEnero = [
        'Fechas', 'RP/ AS / NV  METRO RES', 'RP/ AS / NV  NORTE RES', 'RP/ AS / NV  CAUCA', 'RP/ AS / NV  METRO COM',
        'RP/ AS / NV  NORTE COM', 'RP/ AS / NV  CAUCA', 'facturacion Valle del cauca', 'Inspectores', 'promedio',
        'cantidad ejecutada', 'diferencia', 'cantidad proyectada', 'valor proyectado', '%', 'valor ejecutado', '%'
    ];

    // Más encabezados para otros meses si es necesario
    const headersFebrero = headersEnero;
    const headersMarzo = headersEnero;
    const headersAbril = headersEnero;
    const headersMayo = headersEnero;
    const headersJunio = headersEnero;
    const headersJulio = headersEnero;
    const headersAgosto = headersEnero;
    const headersSeptiembre = headersEnero;
    const headersOctubre = headersEnero;
    const headersNoviembre = headersEnero;
    const headersDiciembre = headersEnero;

    const loadData = (url, headers) => {
        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('La respuesta de la red no fue correcta');
                }
                return response.json();
            })
            .then(data => {
                hot.updateSettings({
                    colHeaders: headers,
                    data: data
                });
                hot.render(); // Forzar redibujado
                handleButtonStates(url);
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Hubo un problema al cargar los datos.');
            });
    };
    const handleButtonStates = (url) => {
        const editButton = document.querySelector('.btn14');
        if (!editButton) {
            console.error('No se encontró el botón de edición.');
            return;
        }

        const isMonthView = url.includes('enero') || url.includes('febrero') || url.includes('marzo') || url.includes('abril') || url.includes('mayo') || url.includes('junio') || url.includes('julio') || url.includes('agosto') || url.includes('septiembre') || url.includes('octubre') || url.includes('noviembre') || url.includes('diciembre');

        editButton.disabled = !isMonthView;

        if (isMonthView) {
            editButton.addEventListener('click', () => {
                const columns = hot.getSettings().columns || [];
                hot.updateSettings({
                    readOnly: false,
                    columns: columns.map((col, index) => {
                        if (index === 12 || index === 13) { // Ajusta estos índices según la posición de 'cantidad proyectada' y 'valor proyectado'
                            return { readOnly: false };
                        }
                        return col;
                    })
                });
            });
        } else {
            editButton.removeEventListener('click', () => {});
        }
    };
    
    document.querySelectorAll('button').forEach(button => {
        button.addEventListener('click', () => {
            const url = button.getAttribute('data-url');
            let headers;

            if (!url) {
                console.error('El botón no tiene un atributo data-url válido.');
                return;
            }

            switch (button.className) {
                case 'btn1':
                    headers = headersGeneral;
                    break;
                case 'btn2':
                    headers = headersEnero;
                    break;
                case 'btn3':
                    headers = headersFebrero;
                    break;
                case 'btn4':
                    headers = headersMarzo;
                    break;
                case 'btn5':
                    headers = headersAbril;
                    break;
                case 'btn6':
                    headers = headersMayo;
                    break;
                case 'btn7':
                    headers = headersJunio;
                    break;
                case 'btn8':
                    headers = headersJulio;
                    break;
                case 'btn9':
                    headers = headersAgosto;
                    break;
                case 'btn10':
                    headers = headersSeptiembre;
                    break;
                case 'btn11':
                    headers = headersOctubre;
                    break;
                case 'btn12':
                    headers = headersNoviembre;
                    break;
                case 'btn13':
                    headers = headersDiciembre;
                    break;
                default:
                    headers = headersGeneral;
            }

            loadData(url, headers);
        });
    });

    // Cargar datos iniciales si es necesario
    const btn1 = document.querySelector('.btn1');
    if (btn1 && btn1.getAttribute('data-url')) {
        loadData(btn1.getAttribute('data-url'), headersGeneral);
    }
});

