document.addEventListener('DOMContentLoaded', () => {

    tabla = document.querySelector('#tabla_programacion');




    H_tabla = new Handsontable(tabla, {
        readOnly: false,
        columns: [
            { data: 'ID', title: 'ID', readOnly: true, },
            {
                data: 'CONTRATO',
                title: 'CONTRATO',

            },
            { data: 'ID_TIPO_TRABAJO', title: 'TIPO DE OBRA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA', title: 'FECHA', type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date()
            },
            { data: 'CELULAR', title: 'CELULAR' },
            { data: 'NOMBRE', title: 'NOMBRE USUARIO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'NUMERO_ORDEN', title: 'ORDEN DE TRABAJO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'DIRECCION', title: 'DIRECCION', readOnly: true, },
            { data: 'BARRIO', title: 'BARRIO', readOnly: true, },
            { data: 'DESC_LOCALIDAD', title: 'CIUDAD', readOnly: true, }, // Ajustado al nombre en tu respuesta (asumiendo que contiene la ciudad)
            { data: 'ACTIVA', title: 'ACTIVA', readOnly: true, },
            { data: 'SUSPENSION', title: 'SUSPENSION', readOnly: true, },
            { data: 'NOM_CATE', title: 'CATEGORIA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA AGENDAMIENTO', title: 'FECHA AGENDAMIENTO', type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date()
            },
            {
                data: 'OBSERVACIONES', // O la columna que deseas ajustar
                title: 'OBSERVACIONES',
                width: 200, // Ancho máximo horizontal deseado
                wordWrap: true, // Habilita el ajuste de línea
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    // Renderizador personalizado para ajustar el alto de la celda
                    Handsontable.renderers.TextRenderer.apply(this, arguments); // Renderiza el texto
                    td.style.height = 'auto'; // Permite que la altura se ajuste al contenido
                },
                className: 'htCenter'
            },
            { data: 'PORQUE SE PROGRAMO', title: 'PORQUE SE PROGRAMO', className: 'htCenter', readOnly: true, },
            {
                data: 'TECNICO',
                title: 'TECNICO',
                type: 'dropdown',
                source: nombresTecnicos,
                width: 200, // Ancho en píxeles
                className: 'htCenter',

            },
            {
                data: 'HORA INICIO',
                title: 'HORA INICIO',
                type: 'time',
                timeFormat: 'hh:mm:ss a',
                correctFormat: true,
                defaultDate: '08:00:00' // Hora inicial por defecto (opcional)
            },
            {
                data: 'HORA FINAL',
                title: 'HORA FINAL',
                type: 'time',
                timeFormat: 'hh:mm:ss a',
                correctFormat: true,
                defaultDate: '17:00:00' // Hora final por defecto (opcional)
            }
        ],
        rowHeaders: true,
        filters: true,
        height: '300px',
        licenseKey: 'non-commercial-and-evaluation',
        afterChange: function (changes, source) {

            if (source === 'edit' || source == 'CopyPaste.paste') {
                if (changes[0][1] === 'CONTRATO') {
                    let row = changes[0][0];

                    let value = changes[0][3];

                    let id = H_tabla.getDataAtRow(row)[0];
                    let url = document.getElementById('busqueda').value;
                    if (value == '' || value == null) {
                        borrarFilaBD(row);
                        // Borrar el contenido de la fila
                        const numCols = H_tabla.countCols();
                        H_tabla.setDataAtCell(row, 0, '');
                        for (let col = 2; col < numCols; col++) {
                            H_tabla.setDataAtCell(row, col, ''); // Establecer cada celda en vacío
                        }
                        return;
                    }
                    const url_busqueda = url.replace(":id", value);

                    $.ajax({
                        url: url_busqueda,
                        type: 'GET',
                        success: function (response) {
                           
                            // Asegurarse de que la respuesta es un objeto
                            if (typeof response === 'object') {

                                const columnMap = {
                                    ID_TIPO_TRABAJO: 2,
                                    NOMBRE: 5,
                                    NUMERO_ORDEN: 6,
                                    DIRECCION: 7,
                                    BARRIO: 8,
                                    DESC_LOCALIDAD: 9,
                                    DESC_ESTADO_PROD: 10,
                                    ACTIVA: 11,
                                    NOM_CATE: 12,
                                };

                                const porqueSeProgramoIndex = H_tabla.propToCol('PORQUE SE PROGRAMO'); // Obtener el índice de la columna
                                H_tabla.setDataAtCell(row, porqueSeProgramoIndex, user.name);
                                // Actualizar celdas en Handsontable
                                for (const key in columnMap) {
                                    if (response.hasOwnProperty(key)) {
                                        if (key === 'DESC_ESTADO_PROD') {
                                            const estado = response[key].toLowerCase();
                                            H_tabla.setDataAtCell(row, columnMap[key], estado === 'activo' ? 'Si' : 'No');
                                           

                                            if (estado === 'suspendido') {
                                                H_tabla.setDataAtCell(row, columnMap['ACTIVA'], 'Si');
                                            }
                                            if (estado === 'activo') {
                                                H_tabla.setDataAtCell(row, columnMap['ACTIVA'], 'No');
                                            }
                                        } else {
                                            H_tabla.setDataAtCell(row, columnMap[key], response[key]);
                                        }
                                    }
                                }
                               
                              guardarProgramacion(row);
                              
                           
                               
                          
                          
                            } else {
                                alert('No se encontró registro con el contrato ingresado');
                                // Borrar el contenido de la fila
                                const numCols = H_tabla.countCols(); // Obtener el número de columnas
                                H_tabla.setDataAtCell(row, 0, '');
                                for (let col = 2; col < numCols; col++) {
                                    H_tabla.setDataAtCell(row, col, '');
                                }
                                console.error("La respuesta del servidor no es un objeto válido:", response);
                            }

                        }, error: function (xhr, status, error) {
                            // Borrar el contenido de la fila
                            alert('No se encontró registro con el contrato ingresado');
                            const numCols = H_tabla.countCols(); // Obtener el número de columnas
                            H_tabla.setDataAtCell(row, 0, '');
                            for (let col = 1; col < numCols; col++) {
                                H_tabla.setDataAtCell(row, col, '');
                            }
                            console.log(xhr.responseText);
                        }

                    });
                }
            }
        }
    });


});

function guardarProgramacion(row) {

    const token = document.getElementById('token').value;
    const url = document.getElementById('url_store').value;
    let rowData = H_tabla.getDataAtRow(row);
 
    if (rowData[0] === null || rowData[0] === '') {


        $.ajax({
            url: url,
            type: 'POST',
            data: {
                data: rowData,
                tabla: tabla_id,
                _token: token
            },
            success: function (response) {

                if (response.error) {
                   console.log(response.error);
                   const numCols = H_tabla.countCols(); // Obtener el número de columnas
                   H_tabla.setDataAtCell(row, 0, '');
                   for (let col = 2; col < numCols; col++) {
                       H_tabla.setDataAtCell(row, col, '');
                   }
                   H_tabla.setDataAtCell(row, 1, '');
                   alert(response.error);
                }
                if (response.id) {
                    H_tabla.setDataAtCell(row, 0, response.id);
                    H_tabla.alter('insert_row_above', H_tabla.countRows() + 1);
                }
               
            
            }, error: function (xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    }

}

function borrarFilaBD(row) {
    const token = document.getElementById('token').value;
    const url = document.getElementById('url_destroy').value;
    let rowData = H_tabla.getDataAtRow(row);
  
    if (rowData[0] === null || rowData[0] === '') {
        return;
    }
    $.ajax({
        url: url,
        type: 'DELETE',
        data: {
            data: rowData[0],
            _token: token
        },
        success: function (response) {
           
            if (response.id) {
                H_tabla.setDataAtCell(row, 0, null);
            }
            alert('Datos eliminados correctamente');
        }, error: function (xhr, status, error) {
            console.log(xhr.responseText);
        }
    });
}