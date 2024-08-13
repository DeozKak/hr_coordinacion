
document.addEventListener('DOMContentLoaded', function() {

    document.getElementById('btnBuscar').addEventListener('click', function() {
        const fechaSeleccionada = document.getElementById('fecha').value;

            $.ajax({
                url: document.getElementById('url_busqueda').value, 
                method: 'POST',
                data: {
                    _token: document.getElementById('token').value,
                    fecha: fechaSeleccionada
                },
                success: function(response) {
                   
                    

                    // Creamos la tabla Handsontable con los datos recibidos
                    hot = new Handsontable(document.getElementById('buscador'), {
                        data: response.data, // Suponemos que la respuesta tiene un array 'data'
                        colHeaders: response.columnas, // Suponemos que la respuesta tiene un array 'columnas'
                        rowHeaders: true,
                        readOnly: true,
                        height: '300px',
                        hiddenColumns: {
                            columns: [0], 
                            columns: [19]
                        },
                        licenseKey: 'non-commercial-and-evaluation'
                   
                    });
                },
                error: function(xhr, status, error) {
                    // Manejo de errores en caso de que la petición falle
                    console.error(xhr.responseText);
                    alert('Ocurrió un error al obtener los datos.');
                }
            });
    });

});