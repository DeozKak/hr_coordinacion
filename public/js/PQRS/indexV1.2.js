
let hot;
function diasClassRenderer(hotInstance, TD, row, col, prop, value, cellProperties) {
    Handsontable.renderers.TextRenderer.apply(this, arguments); // Renderiza normalmente

    // Remueve primero clases previas para evitar que se acumulen (opcional)
    TD.classList.remove('htDiasWarning', 'htDiasError');
    // Aplica clases según valor

    // Convertir valor a número
    const dias = Number(value);

    if (!isNaN(dias)) {
        if (dias >= 3 && dias <= 4) {
            TD.classList.add('htDiasWarning');
        } else if (dias >= 5) {
            TD.classList.add('htDiasError');
        }
    }

    return TD;
}



document.addEventListener('DOMContentLoaded', function() {
    cargarQuejas();



    $('#formulario-archivo').on('submit', function(e) {
        e.preventDefault();
        const boton = document.getElementById('btnSubir');
        boton.disabled = true;

        let formData = new FormData(this);

        $.ajax({
            url: 'pqrs/importar',
            type: 'POST',
            data: formData,
            contentType: false,
            processData: false,
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val()
            },
            success: function(response) {
                let mensajeHtml = '';

                // Mensaje de éxito genérico
                mensajeHtml += `<div class="alert alert-success">
                Archivo subido exitosamente.<br>
                Registros procesados: <strong>${response.procesados ?? 0}</strong>.
            </div>`;

                // Si hay errores, los mostramos
                if (response.errores && response.errores.length > 0) {
                    mensajeHtml += `<div class="alert alert-warning">
                    <strong>Hubo errores en las siguientes filas:</strong>
                    <ul>`;
                    response.errores.forEach(function(item) {
                        mensajeHtml += `<li>Fila ${item.fila}: ${item.error}</li>`;
                    });
                    mensajeHtml += `</ul>
                </div>`;
                }

                $('#mensaje-programaciones').html(mensajeHtml);
                boton.disabled = false;

                cargarQuejas();
            },
            error: function(xhr) {
                boton.disabled = false;
                let msg = 'Ocurrió un error al subir el archivo.';
                if (xhr.responseJSON && xhr.responseJSON.error) {
                    msg = xhr.responseJSON.error;
                }
                $('#mensaje-programaciones').html(
                    `<div class="alert alert-danger">${msg}</div>`
                );
            }
        });
    });

});

function cargarQuejas() {
    fetch('pqrs/quejas')
        .then(response => response.json())
        .then(json => {
            if (json.data) {
                const datos = json.data;
                // Obtén los campos de las columnas dinámicamente o defínelos tú según tu modelo
                const columnas = datos.length ? Object.keys(datos[0]) : [];

                // Si usas un archivo como "coordinaciontbl.js", llama aquí a tu función Handsontable:
                initHandsontableQuejas(columnas, datos);
            }
        });
}

// MODIFICA AQUÍ PARA AGREGAR EL RENDERER A LA COLUMNA "dias"
function initHandsontableQuejas(colHeaders, data) {
    Handsontable.renderers.registerRenderer('diasClassRenderer', diasClassRenderer);

    const container = document.getElementById('table');
    if (!container) return;

    if (window.hotInstance) {
        window.hotInstance.destroy();
    }

    const columns = colHeaders.map(h => {

        if (h.toLowerCase() === 'dias') {
            return { renderer: 'diasClassRenderer', readOnly: true };
        } else {
            return { readOnly: true };
        }
    });

    window.hotInstance = new Handsontable(container, {
        data: data.map(obj => colHeaders.map(c => obj[c])),
        colHeaders: colHeaders,
        rowHeaders: true,
        readOnly: true,
        columns: columns,
        height: "650px",
        licenseKey: "non-commercial-and-evaluation",
        cells: function(row, col, prop) {
            const cellProperties = {};
            // Recuerda: data ahora es un arreglo de arreglos, así que busca el índice correcto
            // Para tener el objeto original, haz:

            const rowData = data[row];

            // O agregar lógica adicional, por ejemplo con recepción
            const recepcion = rowData['recepcion'] ?? rowData['RECEPCION'];

                if (recepcion === 'MACRO') {
                    cellProperties.className = (cellProperties.className ? cellProperties.className + ' ' : '') + 'filaRecepcionMacro';
                }
                if (recepcion === 'GDW') {
                    cellProperties.className = (cellProperties.className ? cellProperties.className + ' ' : '') + 'filaRecepcionGDW';
                }


            return cellProperties;


        }
    });


}



