let columnasEditables = ['FECHA', 'CELULAR', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'TECNICO', 'HORA_INICIO', 'HORA_FINAL'];

document.addEventListener('DOMContentLoaded', () => {

    tabla = document.querySelector('#tabla_programacion');



    H_tabla = new Handsontable(tabla, {
        readOnly: false,
        columns: [
            { data: 'id', title: 'ID', readOnly: true, },
            {
                data: 'CONTRATO',
                title: 'CONTRATO',

            },
            { data: 'TIPO_TRABAJO', title: 'TIPO DE OBRA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA', title: 'FECHA', type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date(),
                readOnly: true,
            },
            { data: 'CELULAR', title: 'CELULAR', readOnly: true, },
            { data: 'NOMBRE_USUARIO', title: 'NOMBRE USUARIO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'ORDEN_TRABAJO', title: 'ORDEN DE TRABAJO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'DIRECCION', title: 'DIRECCION', readOnly: true, },
            { data: 'BARRIO', title: 'BARRIO', readOnly: true, },
            { data: 'CIUDAD', title: 'CIUDAD', readOnly: true, }, // Ajustado al nombre en tu respuesta (asumiendo que contiene la ciudad)
            { data: 'ACTIVA', title: 'ACTIVA', readOnly: true, },
            { data: 'SUSPENDIDO', title: 'SUSPENSION', readOnly: true, },
            { data: 'CATEGORIA', title: 'CATEGORIA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA_AGENDAMIENTO', title: 'FECHA AGENDAMIENTO', type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date(),
                readOnly: true,
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
                className: 'htCenter',
                readOnly: true,
            },
            { data: 'PORQUE_PROGRAMO', title: 'PORQUE SE PROGRAMO', className: 'htCenter', readOnly: true, },
            {
                data: 'TECNICO',
                title: 'TECNICO',
                type: 'dropdown',
                source: nombresTecnicos,
                width: 200, // Ancho en píxeles
                className: 'htCenter',
                readOnly: true,

            },
            {
                data: 'HORA_INICIO',
                title: 'HORA INICIO',
                type: 'time',
                timeFormat: 'hh:mm:ss a',
                correctFormat: true,
                defaultDate: '08:00:00', // Hora inicial por defecto (opcional)
                readOnly: true,
            },
            {
                data: 'HORA_FINAL',
                title: 'HORA FINAL',
                type: 'time',
                timeFormat: 'hh:mm:ss a',
                correctFormat: true,
                defaultDate: '17:00:00',
                readOnly: true,
            }
        ],
        data: tabla_data,
        rowHeaders: true,
        filters: true,
        height: '300px',
        licenseKey: 'non-commercial-and-evaluation',
        afterChange: function (changes, source) {
            if (source === 'edit' || source == 'CopyPaste.paste' || source == 'Autofill.fill') {
                if (changes[0][1] === 'CONTRATO') {
                    let row = changes[0][0];

                    let value = changes[0][3];


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


                    columnasEditables.forEach(colEditable => {
                        H_tabla.getCellMeta(row, H_tabla.propToCol(colEditable)).readOnly = false;
                    });

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

                                H_tabla.setDataAtCell(row, 15, user.name);
                                // Actualizar celdas en Handsontable
                                for (const key in columnMap) {
                                    if (response.hasOwnProperty(key)) {
                                        if (key === 'DESC_ESTADO_PROD') {
                                            const estado = response[key].toLowerCase();
                                            H_tabla.setDataAtCell(row, columnMap[key], estado === 'activo' ? 'Si' : 'No');


                                            if (estado === 'suspendido') {
                                                H_tabla.setDataAtCell(row, columnMap['ACTIVA'], 'Si');
                                            } else {
                                                H_tabla.setDataAtCell(row, columnMap['ACTIVA'], 'No');
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
        },
    });

    H_tabla.addHook('afterChange', function (cambios, origen) {
        if (origen === 'edit' || origen === 'edit' || origen == 'CopyPaste.paste' || origen == 'Autofill.fill') {
            cambios.forEach(([fila, propiedad, valorAnterior, nuevoValor]) => {
                if (columnasEditables.includes(propiedad) && nuevoValor !== valorAnterior) {
                    // Enviar solicitud AJAX para guardar el cambio
                    let id = H_tabla.getDataAtRow(fila)[0];
                    enviarCambioAlServidor(id, propiedad, nuevoValor);
                }
            });
        }
    });

    ajustarReadOnlyDespuesDeCargarDatos();

    H_tabla.alter('insert_row_above', H_tabla.countRows() + 1);
    H_tabla.alter('insert_row_above', H_tabla.countRows() + 1);

    function ajustarReadOnlyDespuesDeCargarDatos() {
        const colContrato = 1; // Índice de la columna 'CONTRATO'      
        // Recorrer los datos y ajustar readOnly
        for (let row = 0; row < tabla_data.length; row++) {
            if (tabla_data[row] && tabla_data[row][colContrato] !== null && tabla_data[row][colContrato] !== '') {
                columnasEditables.forEach(colEditable => {
                    H_tabla.getCellMeta(row, H_tabla.propToCol(colEditable)).readOnly = false;
                });
            }
        }
        H_tabla.render();
    }

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

function enviarCambioAlServidor(id, propiedad, nuevoValor) {
    const token = document.getElementById('token').value;
    const url_update = document.getElementById('url_update').value;
    const url = url_update.replace(':id', id);
    if (nuevoValor == '' || nuevoValor == null || id == '' || id == null) {
        return;
    }
    $.ajax({
        url: url,
        type: 'PUT',
        data: {
            propiedad: propiedad,
            valor: nuevoValor,
            _token: token
        },
        success: function (response) {
            if (response.error) {
                alert(response.error);
            }
        }, error: function (xhr, status, error) {
            console.log(xhr.responseText);
        }
    });
}

function borrarFilaBD(row) {
    const token = document.getElementById('token').value;
    const url = document.getElementById('url_destroy').value;
    let rowData = H_tabla.getDataAtRow(row);

    columnasEditables.forEach(colEditable => {
        H_tabla.getCellMeta(row, H_tabla.propToCol(colEditable)).readOnly = true;
    });

    H_tabla.render();

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

document.getElementById('btnGuardar').addEventListener('click', function () {

    const token = document.getElementById('token').value;
    const url_finish = document.getElementById('url_finish').value;
    const url = url_finish.replace(':id', tabla_id);
    
            $.ajax({
                url: url,
                type: 'POST',
                data: {
                    _token: token
                },
                success: function (response) {
                    if (response.error) {
                        console.log(response.error);
                        alert(response.error);
                    }
                }, error: function (xhr, status, error) {
                    console.log(xhr.responseText);
                }
            });
       
    

});