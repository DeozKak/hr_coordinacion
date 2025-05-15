let hot;
document.addEventListener('DOMContentLoaded', function() {
    const rangoFechasCheckbox = document.getElementById('rangoFechas');
    const fechaFinInput = document.getElementById('fechaFin');
    rangoFechasCheckbox.addEventListener('change', () => {
        if (!rangoFechasCheckbox.checked) {
            fechaFinInput.value = ''; // Borrar el valor del campo de fecha fin
        }
    });

    document.getElementById('btnBuscar').addEventListener('click', function() {
      
        const fechaInicio = document.getElementById('fechaInicio').value;
        let fechaFin = null;

        if (rangoFechasCheckbox.checked) {
            fechaFin = fechaFinInput.value; 
        }

        // Validaciones en JavaScript
        if (!fechaInicio) {
            alert('Por favor, seleccione una fecha de inicio.');
            return; // Detener la ejecución si no hay fecha de inicio
        }

        if (rangoFechasCheckbox.checked && !fechaFin) {
            alert('Por favor, seleccione una fecha de fin.');
            return; // Detener la ejecución si falta la fecha de fin cuando el checkbox está marcado
        }

        if (rangoFechasCheckbox.checked && fechaInicio > fechaFin) {
            alert('La fecha de inicio no puede ser posterior a la fecha de fin.');
            return; // Detener la ejecución si la fecha de inicio es mayor que la de fin
        }


        $.ajax({
            url: document.getElementById('url_busqueda').value, 
            method: 'POST',
            data: {
                _token: document.getElementById('token').value,
                fechaInicio: fechaInicio,
                fechaFin: fechaFin // Si no se selecciona rango, fechaFin será null
            },
            success: function(response) {
                hot = new Handsontable(document.getElementById('buscador'), {
                    data: response.data, 
                    colHeaders: response.columnas,
                    contextMenu: true, 
                    filters: true,
                    dropdownMenu: true,
                    rowHeaders: true,
                    readOnly: true,
                    height: '450px',
                    hiddenColumns: {
                        columns: [0,19,20],
                    },
                    copyPaste: {
                        copyColumnGroupHeaders: true,
                        copyColumnHeaders: true,
                    },
                    licenseKey: 'non-commercial-and-evaluation'
                });
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('Ocurrió un error al obtener los datos.');
            }
        });


        document.getElementById('btnExportar').addEventListener('click', function() {
           
          $.ajax({
            url: document.getElementById('url_exportar').value, 
            method: 'POST',
            data: {
                _token: document.getElementById('token').value,
                data: hot.getData()
            },
            success: function(response) {
              
                window.location.href = response.url;
            },
            error: function(xhr, status, error) {
                console.error(xhr.responseText);
                alert('Ocurrió un error al exportar los datos.');
          }
        }); 

        }); 
    });

});