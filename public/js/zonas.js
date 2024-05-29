
document.addEventListener('DOMContentLoaded', () => {
    let respuesta;
    let headers = [];
    let zonas = document.querySelector('#zonas');

    zonas = new Handsontable(zonas, {
        readOnly: true,
        rowHeaders: true,
        filters: true,
        licenseKey: 'non-commercial-and-evaluation',
        columns: [
            {data: 'zona', type: 'text' },
            {},
            {},
            {},
            {},
        ],
    });

    $.ajax({
        url: 'zonas',
        type: 'GET',
        dataType: 'json',
        success: function (response) {
            console.log(response);
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
            const headerAdicional = { label: '', colspan: 1 };

            headers = [headerAdicional,...resultados.map(item => ({ label: item.label, colspan: item.colspan }))];
            const datosDias = response.diasIntermedios.map(item => item.nombreDia + ' ' + item.dias);
            const datosAdicionales = ['Nombre Zona'];
            headers.push(datosDias);
            datosDias.unshift(...datosAdicionales);
            zonas.updateSettings({
                nestedHeaders: [headers, headers[3]]
            });
            zonas.loadData(response.zonas);
        },
        error: function (xhr, status, error) {
            console.log(xhr.responseText);
        }
    });

   



});