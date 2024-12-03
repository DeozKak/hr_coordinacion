let hot;
document.addEventListener('DOMContentLoaded', function () {

    const table = document.getElementById('table');
    hot = new Handsontable(table, {
        data: [],
        rowHeaders: true,
        columns: [
            {data: 'cc_operario', readOnly: true, visible: false},
            { data: 'nombre_completo', readOnly: true },
            { data: 'saldoAnteriorAmarillo', readOnly: true},
            { data: 'saldoAnteriorRojo', readOnly: true },
            { 
                data: 'AMARILLOS', 
                validator: function(value, callback) {
                  if (/^\d+(\.\d+)?$/.test(value)) {
                    callback(true); 
                  } else {
                    callback(false, 'Por favor, ingrese solo números'); 
                  }
                },allowInvalid: false
              },
              { 
                data: 'ROJOS', 
                validator: function(value, callback) {
                  if (/^\d+(\.\d+)?$/.test(value)) {
                    callback(true); 
                  } else {
                    callback(false, 'Por favor, ingrese solo números');
                  }
                },allowInvalid: false
              },
            { data: 'lunesCert', readOnly: true },
            { data: 'lunesRech', readOnly: true },
            { data: 'lunesMatriz', readOnly: true },
            { data: 'martesCert', readOnly: true },
            { data: 'martesRech', readOnly: true },
            { data: 'martesMatriz', readOnly: true },
            { data: 'miercolesCert', readOnly: true },
            { data: 'miercolesRech', readOnly: true },
            { data: 'miercolesMatriz', readOnly: true },
            { data: 'juevesCert', readOnly: true },
            { data: 'juevesRech', readOnly: true },
            { data: 'juevesMatriz', readOnly: true },
            { data: 'viernesCert', readOnly: true },
            { data: 'viernesRech', readOnly: true },
            { data: 'viernesMatriz', readOnly: true },
            { data: 'sabadoCert', readOnly: true },
            { data: 'sabadoRech', readOnly: true },
            { data: 'sabadoMatriz', readOnly: true },
            { data: 'domingoCert', readOnly: true },
            { data: 'domingoRech', readOnly: true },
            { data: 'domingoMatriz', readOnly: true },
            { data: 'saldoAmarillo', readOnly: true },
            { data: 'saldoRojo', readOnly: true },
        ],
        dropdownMenu: true,
        columnSorting: true,
        hiddenColumns: {
            columns: [0],
          },
        filters: true,
        height: '450px',
        allowInvalid: false,
        licenseKey: 'non-commercial-and-evaluation',
        contextMenu: true,
        manualRowMove: true,
        manualColumnMove: true,
        manualRowResize: true,
        afterChange: function (changes, source) {
            if (source === 'edit') { // Verificar si el cambio fue por edición del usuario
                changes.forEach(([row, prop, oldValue, newValue]) => {
                    if (prop === 'AMARILLOS' || prop === 'ROJOS') {
                        const cc_operario = hot.getDataAtCell(row, 0);
                        const data = {
                            row: row,
                            prop: prop,
                            oldValue: oldValue,
                            newValue: newValue,
                            id: id_semana,
                            cc_operario: cc_operario
                        };
                        sendData(data);
                    }
                }
                )
            }
        }
    });

    getData();

});
function getData() {
    $.ajax({
        url: document.getElementById('url_data').value,
        method: 'POST',
        data: {
            _token: document.getElementById('token').value,
        },
        success: function (response) {
            const dataForHandsontable = Object.values(response.registros).sort((a, b) => {
                const nombreA = a.nombre_completo.toLowerCase();
                const nombreB = b.nombre_completo.toLowerCase();
                return nombreA.localeCompare(nombreB);
              });
            hot.updateSettings({
                nestedHeaders: response.nestedHeaders,
                fixedColumnsLeft: 1
            })
            hot.loadData(dataForHandsontable);
           
        }, error: function (xhr, status, error) { console.log(xhr.responseText); }

    });
}

function sendData(data) {

    $.ajax({
        url: document.getElementById('url_update').value,
        method: 'POST',
        data: {
            row: data.row,
            prop: data.prop,
            oldValue: data.oldValue,
            newValue: data.newValue,
            id_semana: data.id,
            cc_operario: data.cc_operario,
            _token: document.getElementById('token').value,
        },
        success: function (response) {
            if (response.message === 'OK') {
                console.log("Atualizacion exitosa")
            }
            if (response.error) {
                alert(response.error);
            }
        }, error: function (xhr, status, error) { console.log(xhr.responseText); }

    });
}