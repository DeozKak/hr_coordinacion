
function generarSelectores() {

    // convarsion String a JSON desde la vista
    municipios_sin_grupo = JSON.parse(municipios_sin_grupo);
    //Guarda cada array en variables data es una variable resultado de una consulta
    let municipios = data.municipios;
    let barrios = data.barrios;
    let grupos = data.grupos;
    let subgrupos = data.subgrupos;

    municipios = buscarIdsEnMunicipios(municipios_sin_grupo, municipios);

    let modalAsignador = $('#AsignadorModal');
    modalAsignador.modal();
    const container = $("#selectores-container");
    container.empty(); // Limpia el contenido anterior

    municipios.forEach(municipio => {

        const row = $("<div>").addClass("row mb-3").attr('id',municipio.id_detalle); // mb-3 para margen inferior
        container.append(row);


        // Selector de Municipio
        const municipioCol = $("<div>").addClass("col-md-3");
        const municipioSelect = $("<select>").addClass("form-control").attr('id','municipio').prop("disabled", true);
        $("<option>").text(municipio.nombre).attr('value', municipio.id).appendTo(municipioSelect);
        municipioCol.append(municipioSelect);
        row.append(municipioCol);

        //-----------------------------búsqueda grupo por sede-----------------------
        let grupo_filter = buscarIdsEnGrupos(grupos, municipio);

        // Selector de Grupo
        const grupoCol = $("<div>").addClass("col-md-3");
        const grupoSelect = $("<select>").addClass("form-control").attr('id','grupo');
        $("<option>").text("Seleccione Grupo").attr('value','').appendTo(grupoSelect);
        grupo_filter.forEach(grupo => {
            let option_Grupo = $("<option>").text(grupo.grupo).attr('value', grupo.id);
            if (municipio.id_grupo === grupo.id) {
                option_Grupo.prop("selected", true);
            }
            option_Grupo.appendTo(grupoSelect);
        });

        grupoCol.append(grupoSelect);
        row.append(grupoCol);
        //----------------------------------------------------------------------
        //--------------búsqueda subgrupo por sede------------------------------
        let subgrupo_filter = buscarIdsEnGrupos(subgrupos, municipio);

        // Selector de Subgrupo
        const subgrupoCol = $("<div>").addClass("col-md-3");
        const subgrupoSelect = $("<select>").addClass("form-control").attr('id','subgrupo');
        $("<option>").text("Seleccione Subgrupo").attr('value','').appendTo(subgrupoSelect);
        subgrupo_filter.forEach(subgrupo => {
            const option_subgrupo = $("<option>").text(subgrupo.subgrupo).attr('value', subgrupo.id);
            if (municipio.id_subGrupo === subgrupo.id) { // Asumiendo que existe grupo.municipio_id
                option_subgrupo.prop("selected", true);
            }
            option_subgrupo.appendTo(subgrupoSelect);
        });
        subgrupoCol.append(subgrupoSelect);
        row.append(subgrupoCol);
        //---------------------------------------------------------------------
        // -----------------------------búsqueda barrio-------------------------
        let barrio_filter = buscarIdsEnBarrios(barrios, municipio);
        // Selector de Barrio
        const barrioCol = $("<div>").addClass("col-md-3");
        const barrioSelect = $("<select>").addClass("form-control").attr('id','barrio').prop("disabled", true);
        barrio_filter.forEach(barrio => $("<option>").text(barrio.barrio).attr('value',barrio.id).appendTo(barrioSelect));
        barrioCol.append(barrioSelect);
        row.append(barrioCol);
    });
    //se asigna evento a boton guardar para guardar todos los cambios

    $('#asignarGrupo').on('click', function () {
        asignar(modalAsignador);
    });
}


function buscarIdsEnMunicipios(municipiosSinGrupo, municipios) {
    let idsEncontrados = [];
    municipiosSinGrupo.forEach(municipioSinGrupo => {
        let idMun = municipioSinGrupo.id_mun;

        municipios.forEach(municipio => {
            if (municipio.id === idMun) {
                let nuevoMunicipio = {...municipio, id_detalle: municipioSinGrupo.id,
                    id_barrio: municipioSinGrupo.id_barrio,
                    id_grupo: municipioSinGrupo.id_grupo,
                    id_subGrupo: municipioSinGrupo.id_subGrupo};
                idsEncontrados.push(nuevoMunicipio);

            }
        });
    });

    return idsEncontrados;
}

function buscarIdsEnGrupos(grupos,municipio) {
    let array = []
        let id_sede = municipio.id_sede;

        grupos.forEach(grupo => {
            if(grupo.id_sede === id_sede) {
                array.push(grupo);
            }
        })


    return array;
}

function buscarIdsEnBarrios(barrios, municipio) {
    let array = []
    let id_barrio = municipio.id_barrio;

    barrios.forEach(barrio => {
        if(barrio.id === id_barrio) {
            array.push(barrio);
        }
    })
    return array;
}

function asignar(modal) {
    let data = [];

    let form = modal.find('.row');
    form.each(function (index) {
        if(index !== 0) {
           let row = [$(this).attr('id'),
            $(this).find('#municipio').val(),
            $(this).find('#grupo').val(),
            $(this).find('#subgrupo').val(),
            $(this).find('#barrio').val()];
            data.push(row);
        }
    });

    $.ajax({
        url: 'zonas/asignar',
        type: 'POST',
        data: {
            data: data
        },
        success: function (response) {
            console.log(response)
        }
    })
}
