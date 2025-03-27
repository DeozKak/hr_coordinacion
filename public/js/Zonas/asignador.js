function generarSelectores() {

    //Guarda cada array en variables data es una variable resultado de una consulta
    let municipios_sin_grupo = data.municipios_sin_grupo;
    let municipios = data.municipios;
    let barrios_d = data.barrios_d;
    let barrios_a = data.barrios_a;
    let grupos = data.grupos;
    let subgrupos = data.subgrupos;

    municipios = buscarIdsEnMunicipios(municipios_sin_grupo, municipios);

    let modalAsignador = $('#AsignadorModal');
    modalAsignador.modal();
    const container = $("#selectores-container");
    container.empty(); // Limpia el contenido anterior

    municipios.forEach(municipio => {

        const row = $("<div>").addClass("row mb-3").attr('id', municipio.id_detalle); // mb-3 para margen inferior
        container.append(row);


        // Selector de Municipio
        const municipioCol = $("<div>").addClass("col-md-3");
        const municipioSelect = $("<select>").addClass("form-control").attr('id', 'municipio').prop("disabled", true);
        $("<option>").text(municipio.nombre).attr('value', municipio.id).appendTo(municipioSelect);
        municipioCol.append(municipioSelect);
        row.append(municipioCol);

        //-----------------------------búsqueda grupo por sede-----------------------
        let grupo_filter = buscarIdsEnGrupos(grupos, municipio);

        // Selector de Grupo
        const grupoCol = $("<div>").addClass("col-md-3");
        const grupoSelect = $("<select>").addClass("form-control").attr('id', 'grupo');
        $("<option>").text("Seleccione Grupo").attr('value', '').appendTo(grupoSelect);
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
        const subgrupoSelect = $("<select>").addClass("form-control").attr('id', 'subgrupo');
        $("<option>").text("Seleccione Subgrupo").attr('value', '').appendTo(subgrupoSelect);
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
        let barrio_filter = buscarIdsEnBarrios(barrios_a, municipio);
        // Selector de Barrio
        const barrioCol = $("<div>").addClass("col-md-3");
        console.log(barrio_filter);
        const barrioSelect = $("<select>").addClass("form-control").attr('id', 'barrio').prop("disabled", false);
        if (barrio_filter.length === 0) {
            $("<option>").text("Seleccione Barrio").attr('value', '').appendTo(barrioSelect);
        } else {
            barrio_filter.forEach(barrio => {
                $("<option>").text(barrio.barrio).attr('value', barrio.id).appendTo(barrioSelect)
            })
            barrioSelect.prop("disabled", true);
        }
        barrios_d.forEach(barrio => $("<option>").text(barrio.barrio).attr('value', barrio.id).appendTo(barrioSelect));
        barrioCol.append(barrioSelect);
        row.append(barrioCol);
    });
    //se asigna evento a botón guardar para guardar todos los cambios
    $('#asignarGrupo').on('click', function () {
        //llama función para guardar a base de datos
        asignar(modalAsignador);
    });
}


function buscarIdsEnMunicipios(municipiosSinGrupo, municipios) {
    let idsEncontrados = [];
    municipiosSinGrupo.forEach(municipioSinGrupo => {
        let idMun = municipioSinGrupo.id_mun;

        municipios.forEach(municipio => {
            if (municipio.id === idMun) {
                let nuevoMunicipio = {
                    ...municipio, id_detalle: municipioSinGrupo.id,
                    id_barrio: municipioSinGrupo.id_barrio,
                    id_grupo: municipioSinGrupo.id_grupo,
                    id_subGrupo: municipioSinGrupo.id_subGrupo
                };
                idsEncontrados.push(nuevoMunicipio);

            }
        });
    });

    return idsEncontrados;
}

function buscarIdsEnGrupos(grupos, municipio) {
    let array = []
    let id_sede = municipio.id_sede;

    grupos.forEach(grupo => {
        if (grupo.id_sede === id_sede) {
            array.push(grupo);
        }
    })


    return array;
}

function buscarIdsEnBarrios(barrios, municipio) {
    let array = []
    let id_barrio = municipio.id_barrio;

    barrios.forEach(barrio => {
        if (barrio.id === id_barrio) {
            array.push(barrio);
        }
    })
    return array;
}

function asignar(modal) {
    let data = [];
    //busca clase fila del modal para sacar datos
    let form = modal.find('.row');
    //itera sobre cada fila encontrada
    form.each(function (index) {
        //ignora la primera, puesto que no contiene nada
        if (index !== 0) {
            //recolecta información
            let row = {
                id:         $(this).attr('id'),
                municipio:  $(this).find('#municipio').val(),
                grupo:      $(this).find('#grupo').val(),
                subgrupo:   $(this).find('#subgrupo').val(),
                barrio:     $(this).find('#barrio').val()
            };
            //inserta los datos en el array final
            data.push(row);
        }
    });
    // procede a hacer la petición de actualización
    $.ajax({
        url: 'zonas/asignar',
        type: 'POST',
        data: {
            asignaciones: data, // array con los datos recolectados
            _token: $('#token').val() //token
        },
        success: function (response) {
            topAlert('success',response.success);
            modal.modal('hide');
        }, error: function (xhr, status) {
            alerta('error', 'Error', xhr.responseJSON.error)
        }
    })
}
