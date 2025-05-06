let hot;
let municipios;

//----------------------   Buscador HOT ----------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    //inicializar TomSelect
    const SelectMunicipio_b = new TomSelect("#buscarMunicipio", { maxItems: 1, create: false, placeholder: "Seleccione un municipio" });
    const SelectBarrio_b = new TomSelect("#buscarBarrio", { maxItems: 1, create: false, placeholder: "Seleccione un barrio" });
    const SelectGrupo_b = new TomSelect("#buscarGrupo", { maxItems: 1, create: false, placeholder: "Seleccione un grupo" });
    const SelectSubGrupo_b = new TomSelect("#buscarSubGrupo", { maxItems: 1, create: false, placeholder: "Seleccione un sub grupo" });

    //div para mensajes de error
    let message = $('#message');
    // Ocultar la tabla al cargar la página
    $("#table").hide();

    let table = document.getElementById('table');

    hot = new Handsontable(table, {
        colHeaders: ['ID', 'Municipio', 'Grupo', 'Sub Grupo', 'Barrio'],
        rowHeaders: true,
        contextMenu: true,
        stretchH: 'all',
        height: 350,
        readOnly: true,
        licenseKey: 'non-commercial-and-evaluation',
        hiddenColumns: {
            columns: [0], // Oculta la columna con índice 0 ("ID")
        },
        afterChange: function (changes, source) {
            if (source !== 'edit') {
                return;
            }
            if (changes[0][1] === 'barrio' && changes[0][3] !== '') {
                //se obtiene el id del registro para poder modificar en BD
               const id = this.getDataAtCell(changes[0][0], 0);
               // se obtiene el nombre del barrio para cambiar
               const barrio = changes[0][3]
                asignar_barrio(barrio,id);
            }
        }

    });

    // Evento para mostrar la tabla al hacer clic en el botón Buscar
    $("#btnBuscar").click(function () {
        const b_municipio = document.getElementById('buscarMunicipio').value;
        const b_grupo = document.getElementById('buscarGrupo').value;
        const b_subgrupo = document.getElementById('buscarSubGrupo').value;
        const b_barrio = document.getElementById('buscarBarrio').value;

        busqueda(b_municipio, b_grupo, b_subgrupo, b_barrio);

        $("#table").fadeIn(); // Muestra la tabla con efecto de desvanecimiento
    });
});

function busqueda(municipio, grupo, subgrupo, barrio) {
    $.ajax({
        url: 'zonas/buscador',
        type: 'GET',
        data: {
            municipio: municipio,
            grupo: grupo,
            subgrupo: subgrupo,
            barrio: barrio
        },
        success: function (response) {
            let barrios_dis = [];
            const processedData = response.data.map(row => {
                return {
                    ...row,
                    id: row.id ?? '',
                    municipio: row.tbl_localidades_municipio?.nombre ?? '',
                    grupo: row.tbl_grupo?.grupo ?? '',
                    subgrupo: row.tbl_subgrupo?.subgrupo ?? '',
                    barrio: `${row.tbl_barrios?.id ?? ''}${row.tbl_barrios?.barrio ? '. ' + row.tbl_barrios.barrio : ''}`,
                };
            });

            hot.loadData(processedData)

            // Actualizar la variable barrios_dis con los nuevos datos recibidos
            let barrios_JSON = response.barrios;
            barrios_dis = barrios_JSON.map(barrios_JSON => barrios_JSON.id + '. ' + barrios_JSON.barrio);
            const barrios_dis_con_vacio = ['', ...barrios_dis];
            actualizar_barrios(barrios_dis_con_vacio);

        },
        error: function (xhr, status) {
            console.log(xhr.responseText);
            let message = $('#message');
            message.css('display', 'block');
            message.addClass('alert alert-danger');
            message.html(xhr.responseJSON.error);
        }
    });
}

function actualizar_barrios(barrios_dis_con_vacio) {
    hot.updateSettings({
        columns: [
            { data: 'id', type: 'text' },
            { data: 'municipio', type: 'text' },
            { data: 'grupo', type: 'text' },
            { data: 'subgrupo', type: 'text' },
            {
                data: 'barrio',
                renderer: function (instance, td, row, col, prop, value, cellProperties) {
                    if (value === '' || value === null) {
                        Handsontable.renderers.DropdownRenderer.apply(this, arguments);
                    } else {
                        Handsontable.renderers.TextRenderer.apply(this, arguments);
                    }
                },
                type: 'dropdown',
                source: barrios_dis_con_vacio,
                allowEmpty: true,
            }
        ],
        cells: function (row, col) {
            const cellProperties = {};

            if (col === 4) { // Columna de "barrio"
                const cellValue = hot.getDataAtCell(row, col);

                if (cellValue === '' || cellValue === null) {
                    cellProperties.readOnly = false; // Editable si está vacío
                } else {
                    cellProperties.readOnly = true; // Solo lectura si tiene un valor
                }
            }

            return cellProperties;
        }

    });


}

function asignar_barrio(barrio,id) {

    $.ajax({
        url: document.getElementById('url_asignarBarrio').value,
        method: "POST",
        data: {
            barrio: barrio,
            id: id,
            _token: document.getElementById('token').value,
        },
        success: function (response) {
            console.log(response);

        },error: function (xhr, status, error) {
            console.log(xhr.responseText);
        }

    })

}
