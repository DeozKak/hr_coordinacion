document.addEventListener('DOMContentLoaded', () => {

    tabla = document.querySelector('#tabla_programacion');


    headers = ['CONTRATO', 'TIPO DE OBRA', 'FECHA', 'CELULAR', 'NOMBRE USUARIO', 'ORDEN DE TRABAJO', 'DIRECCION', 'BARRIO', 'CIUDAD',
        'ACTIVA', 'SUSPENSION', 'CATEGORIA', 'FECHA AGENDAMIENTO', 'OBSERVACIONES', 'PORQUE SE PROGRAMO', 'TECNICO', 'HORA INICIO', 'HORA FINAL'
    ]
    H_tabla = new Handsontable(tabla, {
        readOnly: false,
        columns: [
            {
                data: 'CONTRATO',
                title: 'CONTRATO',
                validator: function(value, callback) {
                    if (/^\d+$/.test(value)) { // Verificar si son solo dígitos
                        callback(true); // Valor válido
                    } else {
                        callback(false); // Valor inválido
                    }
                }
            },
            { data: 'ID_TIPO_TRABAJO', title: 'TIPO DE OBRA' }, // Ajustado al nombre en tu respuesta
            { data: 'FECHA', title: 'FECHA' ,type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date()}, 
            { data: 'CELULAR', title: 'CELULAR' },
            { data: 'NOMBRE', title: 'NOMBRE USUARIO' }, // Ajustado al nombre en tu respuesta
            { data: 'NUMERO_ORDEN', title: 'ORDEN DE TRABAJO' }, // Ajustado al nombre en tu respuesta
            { data: 'DIRECCION', title: 'DIRECCION' },
            { data: 'BARRIO', title: 'BARRIO' },
            { data: 'DESC_LOCALIDAD', title: 'CIUDAD' }, // Ajustado al nombre en tu respuesta (asumiendo que contiene la ciudad)
            { data: 'ACTIVA', title: 'ACTIVA' },
            { data: 'SUSPENSION', title: 'SUSPENSION' },
            { data: 'NOM_CATE', title: 'CATEGORIA' }, // Ajustado al nombre en tu respuesta
            { data: 'FECHA AGENDAMIENTO', title: 'FECHA AGENDAMIENTO',type: 'date',    // Desplegable de fechas
                dateFormat: 'YYYY-MM-DD', // Formato de fecha (ajusta si es necesario)
                correctFormat: true,       // Corrección automática de formato
                defaultDate: new Date() },
            { data: 'OBSERVACIONES', title: 'OBSERVACIONES' },
            { data: 'PORQUE SE PROGRAMO', title: 'PORQUE SE PROGRAMO' },
            { data: 'TECNICO', title: 'TECNICO' },
            { data: 'HORA INICIO', title: 'HORA INICIO' },
            { data: 'HORA FINAL', title: 'HORA FINAL' }
        ],
        rowHeaders: true,

        filters: true,
        height: '300px',
        licenseKey: 'non-commercial-and-evaluation',
        afterChange: function (changes, source) {
          
            if (source === 'edit' && changes[0][1] === 'CONTRATO') {
                console.log(changes);
                let row = changes[0][0];

                let value = changes[0][3];
                let id = H_tabla.getDataAtRow(row)[0];
                let url = document.getElementById('busqueda').value;
                if (value == '') {
                    return;
                }
                const url_busqueda = url.replace(":id", value);

                $.ajax({
                    url: url_busqueda,
                    type: 'GET',
                    success: function (response) {
                        console.log(response);
                        // Asegurarse de que la respuesta es un objeto
                        if (typeof response === 'object') {
                            
                            const columnMap = {
                                ID_TIPO_TRABAJO:1,
                                NOMBRE: 4,      
                                NUMERO_ORDEN: 5,
                                DIRECCION: 6,
                                BARRIO: 7,
                                DESC_LOCALIDAD: 8,
                                DESC_ESTADO_PROD: 9,
                                ACTIVA: 10,
                                NOM_CATE: 11,
                            };

                            // Actualizar celdas en Handsontable
                            for (const key in columnMap) {
                                if (response.hasOwnProperty(key)) {
                                    if (key === 'DESC_ESTADO_PROD') {
                                        const estado = response[key].toLowerCase();
                                        H_tabla.setDataAtCell(row, columnMap[key], estado === 'activo' ? 'Si' : 'No');
                                        console.log(estado);
                                        // Colocar "No" en la columna 10 si DESC_ESTADO_PROD es "Si"
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
                        } else {
                            console.error("La respuesta del servidor no es un objeto válido:", response);
                        }
                    }, error: function (xhr, status, error) {
                        console.log(xhr.responseText);

                    }

                });
            }
        }
    });


});