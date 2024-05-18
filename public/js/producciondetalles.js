document.addEventListener('DOMContentLoaded', () => {

    const detalles = document.querySelector('#detalles');
    const url = document.querySelector('#id_produccion').value;
    let headers = [];
    let rows = [];
    $.ajax({
        url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            console.log(response);

            rows = response.produccionInspector;
            // Extraer la propiedad nombreMes de cada objeto
            const nombresMes = response.diasIntermedios.map(item => item.nombreMes);
            // Obtener los nombres de mes únicos
            const nombresMesUnicos = [...new Set(nombresMes)];
            // Contar las repeticiones de cada mes
            const conteoRepeticiones = response.diasIntermedios.reduce((conteo, item) => {
                conteo[item.nombreMes] = (conteo[item.nombreMes] || 0) + 1;
                return conteo;
            }, {});

            // Organizar los resultados en el formato deseado
            resultados = nombresMesUnicos.map(nombreMes => ({
                label: nombreMes,
                colspan: conteoRepeticiones[nombreMes]
            }));

            const headerAdicional = { label: '', colspan: 2 };
            const headerFinal = { label: '', colspan: 7 };
            headers = [headerAdicional, ...resultados.map(item => ({ label: item.label, colspan: item.colspan })),headerFinal];
            const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
            const columnasFinales = ['SUB TOTAL', 'MATRICES', 'DOMINGOS Y FESTIVOS', 'DISEÑOS ESPECIALES', '4 O MAS RECINTOS',
            'COMERCIALES', 'TOTAL'];
            const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
            headers.push(datosDias);
            datosDias.unshift(...datosAdicionales);
            headers[4].push(...columnasFinales);
            console.log(headers);
        },
        error: function (xhr, status, error) {
            console.error(xhr.responseText);
            Swal.fire({
                type: 'error',
                title: 'Error',
                text: 'Ocurrió un error al cargar los datos de la base de datos'
            });
        }
    });
    setTimeout(() => {


        const hot = new Handsontable(detalles, {
            readOnly: true,
            manualColumnMove: false,
            rowHeaders: true,
            nestedHeaders: [headers, headers[4]],
            height: '550px',
            data: rows,
            autoWrapRow: true,
            autoWrapCol: true,
            licenseKey: 'non-commercial-and-evaluation' // for non-commercial use only
        });
    }, 6000);
});