let hot;
let municipios;
let SelectMunicipio_b;
let SelectBarrio_b;
let SelectGrupo_b;
let SelectSubGrupo_b;
let SelectInspectores_b;
//----------------------   Buscador HOT ----------------------------------------
document.addEventListener('DOMContentLoaded', function () {

    //inicializar TomSelect
     SelectMunicipio_b = new TomSelect("#buscarMunicipio", { maxItems: 1, create: false, placeholder: "Seleccione un municipio" });
     SelectBarrio_b = new TomSelect("#buscarBarrio", { maxItems: 1, create: false, placeholder: "Seleccione un barrio" });
     SelectGrupo_b = new TomSelect("#buscarGrupo", { maxItems: 1, create: false, placeholder: "Seleccione un grupo" });
     SelectSubGrupo_b = new TomSelect("#buscarSubGrupo", { maxItems: 1, create: false, placeholder: "Seleccione un sub grupo" });
     SelectInspectores_b = new TomSelect("#buscarInspector", { maxItems: 1, create: false, placeholder: "Seleccione un Inspector" })


    SelectMunicipio_b.on('change', actualizar_selects);
    SelectBarrio_b.on('change', actualizar_selects);
    SelectGrupo_b.on('change', actualizar_selects);
    SelectSubGrupo_b.on('change', actualizar_selects);
    SelectInspectores_b.on('change', actualizar_selects);


    //div para mensajes de error
    let message = $('#message');
    // Ocultar la tabla al cargar la página
    $("#table").hide();

    let table = document.getElementById('table');

    hot = new Handsontable(table, {
        colHeaders: ['ID', 'Municipio', 'Grupo', 'Sub Grupo', 'Barrio','Inspectores Asgnados'],
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
        const b_inspector = document.getElementById('buscarInspector').value;

        busqueda(b_municipio, b_grupo, b_subgrupo, b_barrio, b_inspector);

        $("#table").fadeIn(); // Muestra la tabla con efecto de desvanecimiento
    });
});

function busqueda(municipio, grupo, subgrupo, barrio, inspector) {
    $.ajax({
        url: 'zonas/buscador',
        type: 'GET',
        data: {
            municipio: municipio,
            grupo: grupo,
            subgrupo: subgrupo,
            barrio: barrio,
            inspector: inspector
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
            console.log(processedData);

            hot.loadData(processedData)
            // Actualizar la variable barrios_dis con los nuevos datos recibidos
            let barrios_JSON = response.barrios;
            barrios_dis = barrios_JSON.map(barrios_JSON => barrios_JSON.id + '. ' + barrios_JSON.barrio);
            const barrios_dis_con_vacio = ['', ...barrios_dis];
            actualizar_barrios(barrios_dis_con_vacio);
            let message = $('#message');
            message.css('display', 'none');
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
            },
            {
                renderer: function(instance, td, row, col, prop, value, cellProperties) {
                    td.innerHTML = '<a href="#" class="enlace-ver-inspectores" data-row="' + hot.getDataAtCell(row,0) + '">Ver</a>';
                    td.style.textAlign = 'center';
                    td.className = 'htCenter';
                },
                readOnly: true,

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
            if (col === 5) {
                cellProperties.readOnly = true;
            }

            return cellProperties;
        }

    });
// asigna evento para ver los inspectores asignados al subgrupo seleccionado
    if (!hot.rootElement.hasAttribute('data-event-inspectores')) {
        hot.rootElement.setAttribute('data-event-inspectores', 'ok');
        hot.rootElement.addEventListener('click', function (event) {
            if (event.target && event.target.classList.contains('enlace-ver-inspectores')) {

                event.preventDefault();
                const id_detalle = event.target.getAttribute('data-row');

                mostrarModalInspectores(id_detalle);
            }
        });
    }



}

function actualizar_selects() {

    const municipio = SelectMunicipio_b.getValue();      // Valor actual de municipio
    const barrio    = SelectBarrio_b.getValue();         // Valor actual de barrio
    const grupo     = SelectGrupo_b.getValue();          // Valor actual de grupo
    const subgrupo  = SelectSubGrupo_b.getValue();
    const inspector  = SelectInspectores_b.getValue();

    $.ajax({
        url: 'zonas/selects',
        type: 'GET',
        data: {
            municipio: municipio,
            barrio: barrio,
            grupo: grupo,
            subgrupo: subgrupo,
            inspector: inspector
        },
        success: function (response) {

            const municipios = extraerUnicos(response.data || [], 'tbl_localidades_municipio');
            const barrios    = extraerUnicos(response.data || [], 'tbl_barrios');
            const grupos     = extraerUnicos(response.data || [], 'tbl_grupo');
            const subgrupos  = extraerUnicos(response.data || [], 'tbl_subgrupo');
            const inspector  = extraerInspectores(response.data || [], 'inspectores');

            actualizarTomSelect(SelectMunicipio_b, municipios, 'id', 'nombre');
            actualizarTomSelect(SelectBarrio_b, barrios, 'id', 'barrio');
            actualizarTomSelect(SelectGrupo_b, grupos, 'id', 'grupo');
            actualizarTomSelect(SelectSubGrupo_b, subgrupos, 'id', 'subgrupo');
            actualizarTomSelect(SelectInspectores_b, inspector, 'id', 'nombre');


        },error: function (xhr, status) {
            console.log(xhr.responseText);
        }
    })

}

function extraerUnicos(lista, campo) {
    // filtra items válidos y elimina duplicados por ID
    const map = new Map();

    lista.forEach(item => {

        let obj = item[campo];
        if (obj && obj.id && !map.has(obj.id)) {
            map.set(obj.id, obj);
        }
    });
    return Array.from(map.values());
}

function extraerInspectores(lista, campo) {
    const map = new Map();

    lista.forEach(item => {
        let listadoInspectores = item[campo];

        // Validamos que sea un array antes de intentar recorrerlo
        if (Array.isArray(listadoInspectores)) {
            listadoInspectores.forEach(i => {
                if (i && i.id && !map.has(i.id)) {
                    // Aquí formateamos el nombre unificando ID, Apellidos y Nombres
                    i.nombre = `${i.id}. ${i.apellidos} ${i.nombres}`;
                    map.set(i.id, i);
                }
            });
        }
    });
    return Array.from(map.values());
}

function actualizarTomSelect(selectInstance, data, valueField = 'id', labelField = 'nombre') {
    // Limpiar opciones previas
    selectInstance.clearOptions();
    // Agregar nuevas opciones
    data.forEach(item => {
        selectInstance.addOption({
            value: item[valueField],
            text: item[labelField]
        });
    });
    // Actualiza el boceto visual si hay algo seleccionado
    selectInstance.refreshOptions(false);
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

function mostrarModalInspectores(idDetalle) {
    $.ajax({
        url: 'zonas/'+ idDetalle +'/responsablesInsp', // Ajusta la ruta si es necesario
        method: 'GET',
        success: function(response) {
            let inspectores = response.inspectores;
            let contenido = "";
            if (Array.isArray(inspectores) && inspectores.length > 0) {
                contenido = "<ul>" + inspectores.map(i => "<li>"+ i.id +". "+ i.apellidos +" " + i.nombres + "</li>").join('') + "</ul>";
            } else {
                contenido = "No hay inspectores asignados.";
            }
            Swal.fire({
                title: 'Inspectores asignados al grupo: '+response.grupo.grupo+' | sub grupo: '+response.subgrupo.subgrupo,
                html: contenido,
                icon: 'info',
                confirmButtonText: 'Cerrar'
            });
        },
        error: function(xhr) {
            Swal.fire({
                title: 'Error',
                text: 'No se pudieron obtener los inspectores asignados.',
                icon: 'error',
                confirmButtonText: 'Cerrar'
            });
        }
    });
}

