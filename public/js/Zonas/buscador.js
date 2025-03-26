let hot;
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

    // Inicializar Handsontable cuando se presiona el botón Buscar
    let table = document.getElementById('table');

    hot = new Handsontable(table, {
        colHeaders: ['Municipio', 'Grupo', 'Sub Grupo', 'Barrio'],
        columns:[
            // declaracion de tipo de datos columnas y propiedades
            {type: 'text', data:row => row.tbl_localidades_municipio?.nombre ?? ''},
            {type: 'text', data:row => row.tbl_grupo?.grupo ?? ''},
            {type: 'text', data:row => row.tbl_subgrupo?.subgrupo ?? ''},
            {type: 'text', data:row => row.tbl_barrios?.barrio ?? ''},
        ],
        rowHeaders: true,
        contextMenu: true,
        stretchH: 'all',
        height: '350px',
        licenseKey: 'non-commercial-and-evaluation',
    });

    // Evento para mostrar la tabla al hacer clic en el botón Buscar
    $("#btnBuscar").click(function () {
        message.css('display', 'block'); //
        message.css('display', 'none');
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
            hot.loadData(response);
            console.log(response)
        }, error(xhr, status) {
            console.log(xhr.responseText);
            let message = $('#message');
            message.css('display', 'block');
            message.addClass('alert alert-danger'); // Agrega las clases de Bootstrap
            message.html(xhr.responseJSON.error);

        }
    })
}


