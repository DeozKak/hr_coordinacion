let hot;
let dataHot;
let verMasModalInstance = null;
document.addEventListener('DOMContentLoaded', function () {

    // --- Lógica manual para cerrar el modal ---
    const btnCerrarTop = document.getElementById('btnCerrarVerMasTop');
    const btnCerrarFooter = document.getElementById('btnCerrarVerMasFooter');

    const cerrarModalVerMas = function() {
        if (verMasModalInstance) {
            verMasModalInstance.hide();
        }
    };

    if (btnCerrarTop) {
        btnCerrarTop.addEventListener('click', cerrarModalVerMas);
    }
    if (btnCerrarFooter) {
        btnCerrarFooter.addEventListener('click', cerrarModalVerMas);
    }

    // 1. Registramos el renderizador personalizado
    Handsontable.renderers.registerRenderer('verMasRenderer', function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments);

        // Validamos si la celda tiene un contenido largo
        if (value && typeof value === 'string' && value.length > 30) {
            td.innerHTML = `
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%; height: 100%; overflow: hidden; white-space: nowrap;">
                    <span title="${value}" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; flex: 1;">
                        ${value}
                    </span>
                    <button class="btn btn-xs ver-mas-btn px-1 text-primary border-0 bg-transparent" style="cursor: pointer; flex-shrink: 0; margin-left: 5px;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;

            // Quitar el padding por defecto de Handsontable para que el div ocupe bien el espacio
            td.style.paddingRight = '2px';

            const btn = td.querySelector('.ver-mas-btn');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation();
                    document.getElementById('verMasContent').textContent = value;

                    if (!verMasModalInstance) {
                        const verMasModalElement = document.getElementById('verMasModal');
                        verMasModalInstance = new bootstrap.Modal(verMasModalElement);
                    }
                    verMasModalInstance.show();
                });
            }
        }
    });


    const rangoFechasCheckbox = document.getElementById('rangoFechas');
    const fechaFinInput = document.getElementById('fechaFin');
    rangoFechasCheckbox.addEventListener('change', () => {
        if (!rangoFechasCheckbox.checked) {
            fechaFinInput.value = ''; // Borrar el valor del campo de fecha fin
        }
    });
    const sourceTecnicos = tecnicos.map(t => `${t.id}. ${t.apellidos} ${t.nombres}`);


    // 2. EVENTOS DE EXPORTACIÓN (Sácalos del AJAX, ponlos aquí afuera)
    document.getElementById('btnExportar').addEventListener('click', function () {
        if (!hot) return alert("No hay datos para exportar");

        $.ajax({
            url: document.getElementById('url_exportar').value,
            method: 'POST',
            data: {
                _token: document.getElementById('token').value,
                data: hot.getData() // Siempre toma lo que hay actualmente en la tabla
            },
            success: function (response) {
                window.location.href = response.url;
            },
            error: function (xhr) {
                alert(xhr.responseJSON.error);
            }
        });
    });

    document.getElementById('btnExportarSup').addEventListener('click', function () {
        // Usamos dataHot que se actualiza en cada búsqueda
        if (!dataHot) return alert("Realice una búsqueda primero");

        $('#loader').show();
        $('#overlay').show();

        $.ajax({
            url: document.getElementById('urlexportarSup').value,
            method: 'POST',
            data: {
                _token: document.getElementById('token').value,
                data: dataHot,
                fechaInicio: document.getElementById('fechaInicio').value,
                fechaFin: document.getElementById('fechaFin').value
            },
            success: function (response) {
                $('#loader').hide();
                $('#overlay').hide();
                window.location.href = response.url;
            },
            error: function (xhr) {
                $('#loader').hide();
                $('#overlay').hide();
                alert(xhr.responseJSON.error);
            }
        });
    });


    document.getElementById('btnBuscar').addEventListener('click', function () {

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
            success: function (response) {
                dataHot = null;
                if (hot != null) {
                    hot.destroy();
                    hot = null;
                }
                hot = new Handsontable(document.getElementById('buscador'), {
                    data: response.data,
                    colHeaders: response.columnas,
                    contextMenu: true,
                    filters: true,
                    dropdownMenu: true,
                    rowHeaders: true,
                    readOnly: true,
                    height: '450px',
                    // --- NUEVO: Evita que el texto baje de línea y ensanche la fila hacia abajo ---
                    wordWrap: false,
                    autoWrapRow: false,
                    autoWrapCol: false,
                    // --- NUEVO: Controla el ancho de las columnas ---
                    colWidths: function(index) {
                        const headerName = response.columnas[index];
                        console.log(headerName);
                        if (!headerName) return 100; // Por si acaso no hay nombre

                        // 1. A tu columna de Observaciones le damos un ancho fijo generoso
                        if (headerName.toUpperCase().includes('OBSERVACION')) {
                            return 300;
                        }

                        // 2. A la columna del Técnico también le damos buen espacio (el menú desplegable)
                        if (headerName.toUpperCase().includes('TECNICO')) {
                            return 300;
                        }

                        // 3. ¡La Magia! Para el resto, calculamos el espacio según las letras del título
                        // Multiplicamos cada letra por 8 píxeles y sumamos 40px extras para el icono de filtro
                        const anchoCalculado = (headerName.length * 8) + 40;

                        // Nos aseguramos de que ninguna columna mida menos de 100px para que no se apeñusquen
                        return Math.max(100, anchoCalculado);
                    },
                    hiddenColumns: {
                        columns: [0, 19, 20],
                    },
                    copyPaste: {
                        copyColumnGroupHeaders: true,
                        copyColumnHeaders: true,
                    },
                    licenseKey: 'non-commercial-and-evaluation',
                    cells: function (row, col) {
                        const cellProperties = {};

                        // Ajusta el índice según corresponda a "ORDEN_TRABAJO"
                        let ordenTrabajoColIndex = 'ORDEN_TRABAJO';
                        let rowData = this.instance.getSourceDataAtRow(row);

                        if (rowData && rowData[ordenTrabajoColIndex] === "N/A") {
                            cellProperties.className = (cellProperties.className ? cellProperties.className + ' ' : '') + 'filaNA';
                        }

                        // Lógica para la columna de técnicos (Menú desplegable)
                        if (permiso_modTec === 1 && col === 16) {
                            return {
                                readOnly: false,
                                type: 'dropdown',
                                source: sourceTecnicos,
                                strict: true,
                                allowInvalid: false
                            };
                        }

                        // A todas las demás columnas, les aplicamos el renderizador de "Ver Más"
                        // El renderizador por dentro decide si recorta el texto o lo deja normal
                        cellProperties.renderer = 'verMasRenderer';

                        return cellProperties;
                    }, afterChange: function (changes, source) {

                        if (source === "edit") {
                            if (changes[0][2] === changes[0][3]) {
                                return;
                            }
                            let id_reg = hot.getDataAtCell(changes[0][0], 0);
                            let value = changes[0][3];

                            enviarCambios(id_reg, value);
                        }

                    }

                });

                dataHot = hot.getData();

            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
                alert(xhr.responseJSON.error);
            }
        });






    });

    function enviarCambios(idReg, value) {
        let url = document.getElementById('url_update').value;
        url = url.replace(':id', idReg);
        $.ajax({
            url: url,
            method: 'PUT',
            data: {
                _token: document.getElementById('token').value,
                propiedad: 'TECNICO',
                valor: value
            },
            success: function (response) {
                if (response.message) {
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: response.message,
                        showConfirmButton: false,
                        toast: true,
                        timer: 2000
                    });
                }
            }, error: function (xhr, status, error) {
                alert(xhr.responseJson.error);
            }
        })
    }

});
