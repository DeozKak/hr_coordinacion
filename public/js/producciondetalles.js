document.addEventListener('DOMContentLoaded', () => {

    const detalles = document.querySelector('#detalles');
    const url = document.querySelector('#id_produccion').value;
    let json = [];
    let headers = [];
    let rows = [];
    let currentMonthDays = [];
    $.ajax({
        url: url, // Ruta al archivo PHP que realiza la consulta a la base de datos
        type: 'GET',
        success: function (response) {
            rows = response.produccionInspector.map(item => ({ cedula: item.cedula, nombres: item.nombres }));
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
            console.log(resultados);
            const headerAdicional = { label: '', colspan: 2 };
            headers = [headerAdicional, ...resultados.map(item => ({ label: item.label, colspan: item.colspan }))];
            const datosAdicionales = ['CC', 'INSPECTORES CONTRATO CALI'];
            const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
            headers.push(datosDias);
            datosDias.unshift(...datosAdicionales);  
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
            nestedRows: true,
            rowHeaders: true,
            nestedHeaders:[headers,headers[3]],
            height: '550px',
            data: rows,
            autoWrapRow: true,
            autoWrapCol: true,
            licenseKey: 'non-commercial-and-evaluation' // for non-commercial use only
        });
    }, 500);
});