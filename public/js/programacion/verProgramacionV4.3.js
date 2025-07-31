let hot;
document.addEventListener('DOMContentLoaded', function () {
    const rangoFechasCheckbox = document.getElementById('rangoFechas');
    const fechaFinInput = document.getElementById('fechaFin');
    rangoFechasCheckbox.addEventListener('change', () => {
        if (!rangoFechasCheckbox.checked) {
            fechaFinInput.value = ''; // Borrar el valor del campo de fecha fin
        }
    });
    const sourceTecnicos = tecnicos.map(t => `${t.id}. ${t.apellidos} ${t.nombres}`);


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

                        // Ajusta el índice 7 por el que corresponda a "ORDEN_TRABAJO" según tus columnas
                        let ordenTrabajoColIndex = 'ORDEN_TRABAJO';
                        let rowData = this.instance.getSourceDataAtRow(row);

                        if (rowData && rowData[ordenTrabajoColIndex] === "N/A") {
                            cellProperties.className = (cellProperties.className ? cellProperties.className + ' ' : '') + 'filaNA';
                        }
                        if (permiso_modTec === 1) {
                            if (col === 16) {
                                return {
                                    readOnly: false,
                                    type: 'dropdown',
                                    source: sourceTecnicos,
                                    strict: true,
                                    allowInvalid: false

                                };
                            }
                        }

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
               /* if (permiso_modTec === 1) {
                    hot.updateSettings({
                            cells: function (row, col) {
                                if (col === 16) {
                                    return {
                                        readOnly: false,
                                        type: 'dropdown',
                                        source: sourceTecnicos,
                                        strict: true,
                                        allowInvalid: false

                                    };
                                }
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
                        }
                    );
                }*/
            },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    alert(xhr.responseJSON.error);
                }
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

        document.getElementById('btnExportar').addEventListener('click', function () {

            $.ajax({
                url: document.getElementById('url_exportar').value,
                method: 'POST',
                data: {
                    _token: document.getElementById('token').value,
                    data: hot.getData()
                },
                success: function (response) {

                    window.location.href = response.url;
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                    alert(xhr.responseJSON.error);
                }
            });

        });

        document.getElementById('btnExportarSup').addEventListener('click', function () {
            $('#loader').show();
            $('#overlay').show();
            $.ajax({
                url: document.getElementById('urlexportarSup').value,
                method: 'POST',
                data: {
                    _token: document.getElementById('token').value,
                    data: hot.getData(),
                    fechaInicio: fechaInicio,
                    fechaFin: fechaFin
                },
                success: function (response) {
                    $('#loader').hide();
                    $('#overlay').hide();
                    window.location.href = response.url;
                },
                error: function (xhr, status, error) {
                    $('#loader').hide();
                    $('#overlay').hide();
                    console.error(xhr.responseText);
                    alert(xhr.responseJSON.error);
                }
            });

        });
    })
        ;

    });
