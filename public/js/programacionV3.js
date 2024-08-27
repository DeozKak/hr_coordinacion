let columnasEditables;

if(ver_programacion === "true"){
    columnasEditables = ['TECNICO','CELULAR', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'HORA_INICIO', 'HORA_FINAL'];
}else{
    columnasEditables = ['CELULAR', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'HORA_INICIO', 'HORA_FINAL'];
}

document.addEventListener('DOMContentLoaded', () => {

    tabla = document.querySelector('#tabla_programacion');

   

    H_tabla = new Handsontable(tabla, {

        columns: [
            { data: 'id', title: 'ID', readOnly: true, },
            {
                data: 'CONTRATO',
                title: 'CONTRATO',
                validator: function (value, callback) {
                    let soloNumerosRegex = /^[0-9]+$/;
                    if (value === "" || value === null || soloNumerosRegex.test(value)) {
                        callback(true);
                    } else {
                        alert('Por favor, ingrese solo números en el campo CONTRATO.');
                        callback(false);
                    }
                },

            },
            { data: 'TIPO_TRABAJO', title: 'TIPO DE OBRA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA', title: 'FECHA', type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date(),
                readOnly: true,
            },
            {
                data: 'CELULAR', title: 'CELULAR', readOnly: true, validator: function (value, callback) {
                    let soloNumerosRegex = /^[0-9]+$/;
                    if ((value === "" || value === null || soloNumerosRegex.test(value)) && 
                    (value === "" || value === null || value.length === 10)){
                        callback(true);
                    } else {
                        alert('Por favor, ingrese solo números en el campo CELULAR y asegúrese de que tenga 10 dígitos.');
                        callback(false);
                    }
                },
            },
            { data: 'NOMBRE_USUARIO', title: 'NOMBRE USUARIO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'ORDEN_TRABAJO', title: 'ORDEN DE TRABAJO', readOnly: true, }, // Ajustado al nombre en tu respuesta
            { data: 'DIRECCION', title: 'DIRECCION', readOnly: true, },
            { data: 'BARRIO', title: 'BARRIO', readOnly: true, },
            { data: 'CIUDAD', title: 'CIUDAD', readOnly: true, }, // Ajustado al nombre en tu respuesta (asumiendo que contiene la ciudad)
            { data: 'ACTIVA', title: 'ACTIVA', readOnly: true, },
            { data: 'SUSPENDIDO', title: 'SUSPENSION', readOnly: true, },
            { data: 'CATEGORIA', title: 'CATEGORIA', readOnly: true, }, // Ajustado al nombre en tu respuesta
            {
                data: 'FECHA_AGENDAMIENTO',
                title: 'FECHA AGENDAMIENTO',
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
                correctFormat: true,
                readOnly: true,
                datePickerConfig: {
                    minDate: new Date(), // Esto establece la fecha mínima como el día de inicio del corte 
                },
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
                defaultDate: '07:59:00 a.m.',
                readOnly: true, // Permitimos la edición para el combobox
                editor: 'select', // Usamos el editor 'select' para el combobox
                selectOptions: ['07:59:00 a.m.', '01:59:00 p.m.'], // Opciones predefinidas
            },
            {
                data: 'HORA_FINAL',
                title: 'HORA FINAL',
                type: 'time',
                timeFormat: 'hh:mm:ss a',
                correctFormat: true,
                defaultDate: '04:59:00 p.m.',
                readOnly: true, // Permitimos la edición para el combobox
                editor: 'select', // Usamos el editor 'select' para el combobox
                selectOptions: ['11:59:00 a.m.', '04:59:00 p.m.'], // Opciones predefinidas
            }
        ],

        data: tabla_data,
        rowHeaders: true,
        filters: true,
        height: '450px',
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

                    if (view === "") {
                        columnasEditables.forEach(colEditable => {
                            H_tabla.getCellMeta(row, H_tabla.propToCol(colEditable)).readOnly = false;
                        });
                    }

                    $.ajax({
                        url: url_busqueda,
                        type: 'GET',
                        success: function (response) {
                            if (response.errors) {
                                alert(response.errors);
                                H_tabla.setDataAtCell(row, 1, '');
                                return;
                            }
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
                                    ID_TECNICO: 16,
                                };
                                H_tabla.setDataAtCell(row, 3, formatearFecha(new Date())); // Establecer la fecha actual
                                H_tabla.setDataAtCell(row, 15, user.name); // Colocar Nombre de usuario

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
                                setTimeout(() => {
                                    guardarProgramacion(row);
                                }, 200);

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
        readOnly: view,
    });

    function formatearFecha(fecha) {
        const year = fecha.getFullYear();
        const month = String(fecha.getMonth() + 1).padStart(2, '0'); // Los meses van de 0 a 11
        const day = String(fecha.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

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
    //validacion de vista o editar
    if (view === "") {
        ajustarReadOnlyDespuesDeCargarDatos();
    }
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
if (view === "") {
    document.getElementById('btnGuardar').addEventListener('click', function () {
        $('#loader').show();
        $('#overlay').show();
        if (!validarFilasCompletas()) {
            $('#loader').hide();
            $('#overlay').hide(); 
            alert("hay campos incompletos o horas incorrectas");
            return; // Detener el envío de la solicitud AJAX si hay filas incompletas
        }

        const url_index = document.getElementById('url_index').value; 
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
                $('#loader').hide();
                $('#overlay').hide();
                if (response.ok) {
                    window.location.href = url_index;
                }
                if (response.error) {
                    alert(response.error);
                }
            }, error: function (xhr, status, error) {
                $('#loader').hide();
                $('#overlay').hide();
                console.log(xhr.responseText);
            }
        });


    });
}
// Función para validar que todas las filas estén llenas (ignorando filas vacías)
function validarFilasCompletas() {
    const data = H_tabla.getData();
    let hayFilasConDatos = false;
    
    for (let i = 0; i < data.length; i++) {
        const fila = data[i];
        let filaTieneDatos = false;

        for (let j = 0; j < fila.length; j++) {
            if (fila[j] !== '' && fila[j] !== null && fila[j] !== undefined) {
                filaTieneDatos = true;
                hayFilasConDatos = true;
                break;
            }
        }

        if (filaTieneDatos) {
            for (let j = 0; j < fila.length; j++) {
                if (fila[j] === '' || fila[j] === null || fila[j] === undefined) {
                    return false;
                }
            }

            // Nueva validación para las horas
            if (fila[17] === '01:59:00 p.m.' && fila[18] === '11:59:00 a.m.') {
                return false;
            }
        }
    }

    return hayFilasConDatos;
}