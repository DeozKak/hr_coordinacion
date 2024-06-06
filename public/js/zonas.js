document.addEventListener('DOMContentLoaded', () => {
    let respuesta;
    let idCorteDetalles;
    let headers = [];
    let zonas = document.querySelector('#zonas');

    zonas = new Handsontable(zonas, {
        readOnly: true,
        rowHeaders: true,
        filters: true,
        height: '300px',
        licenseKey: 'non-commercial-and-evaluation',
    });
    console.log("zonas");

    const idCorteDetallesInput = document.querySelector('#id_corte_detalles');

    if (idCorteDetallesInput) { // Verificar si el elemento existe
        idCorteDetalles = idCorteDetallesInput.value;
    }

    $.ajax({
        url: urlZonas,
        type: 'GET',
        data: { idCorteDetalles },
        dataType: 'json',
        success: function (response) {
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

            const combinedDataForHandsontable = response.residencial.concat(response.comercial);
           
            zonas.loadData(combinedDataForHandsontable);
            zonas.alter('insert_row_above', 3);

            $('#loader').hide();
            $('#overlay').hide();
          
        },
        error: function (xhr, status, error) {
            console.log(xhr.responseText);
        }
    });

   



});