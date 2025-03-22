
// Datos de ejemplo (reemplaza con los datos reales del servidor)

/*const municipios = ["Municipio 1", "Municipio 2", "Municipio 3", "Municipio 4", "Municipio 5"];
const grupos = ["Grupo A", "Grupo B", "Grupo C"];
const subgrupos = ["Subgrupo 1", "Subgrupo 2", "Subgrupo 3"];
const barrios = ["Barrio X", "Barrio Y", "Barrio Z"];*/

function generarSelectores() {
    console.log(municipios,barrios,grupos,subgrupos);
    let modalAsignador = $('#AsignadorModal');
    modalAsignador.modal();
    const container = $("#selectores-container");
    container.empty(); // Limpia el contenido anterior

    municipios.forEach(municipio => {
        const row = $("<div>").addClass("row mb-3"); // mb-3 para margen inferior
        container.append(row);

        // Selector de Municipio
        const municipioCol = $("<div>").addClass("col-md-3");
        const municipioSelect = $("<select>").addClass("form-control");
        $("<option>").text(municipio).appendTo(municipioSelect);
        municipioCol.append(municipioSelect);
        row.append(municipioCol);

        // Selector de Grupo
        const grupoCol = $("<div>").addClass("col-md-3");
        const grupoSelect = $("<select>").addClass("form-control");
        grupos.forEach(grupo => $("<option>").text(grupo).appendTo(grupoSelect));
        grupoCol.append(grupoSelect);
        row.append(grupoCol);

        // Selector de Subgrupo
        const subgrupoCol = $("<div>").addClass("col-md-3");
        const subgrupoSelect = $("<select>").addClass("form-control");
        subgrupos.forEach(subgrupo => $("<option>").text(subgrupo).appendTo(subgrupoSelect));
        subgrupoCol.append(subgrupoSelect);
        row.append(subgrupoCol);

        // Selector de Barrio
        const barrioCol = $("<div>").addClass("col-md-3");
        const barrioSelect = $("<select>").addClass("form-control");
        barrios.forEach(barrio => $("<option>").text(barrio).appendTo(barrioSelect));
        barrioCol.append(barrioSelect);
        row.append(barrioCol);
    });
}
