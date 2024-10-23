function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = [
        "indice",
        "orden",
        "contrato",
        "producto",
        "numero_solicitud",
        "tipo_solicitud",
        "NIT_CC",
        "nombre_lugar",
        "departamento",
        "localidad",
        "sector_operativo",
        "direccion",
        "consecutivo_ruta",
        "telefono",
        "medidor",
        "categoria",
        "unidad_operativa",
        "tipo_trabajo",
        "fecha_asignacion",
        "observacion_solicitud",
        "orden_solicitud_externa",
        "tipo_solicitud_externa",
        "fecha_solicitud_externa",
        "observacion_externa",
        "fecha_reasignacion_externa",
        "FECHA_AGENDAMIENTO",
        "jornada",
        "CELULAR",
        "OBSERVACIONES",
        "estado_programacion",
        "codigo_tecnico",
        "fecha_asignacion_inspector"
    ];

    return Object.keys(jsonData).map((key) => {
        const fila = jsonData[key];
        return columnasDeseadas.map((columna) => fila[columna]);
    });
}

document.addEventListener("DOMContentLoaded", function () {
    let nestedHeaders = [
        [
            {
                label: "",
                colspan: 4,
            },
            {
                label: "1. ASIGNACION BASE OSF",
                colspan: 16,
            },
            {
                label: "2. INFORMACIÓN COMPLEMENTARIA 12161",
                colspan: 5,
            },
            {
                label: "3. PROGRAMACIÓN DE ORDENES",
                colspan: 5,
            },
            {
                label: "4. ASIGNACIÓN INSPECTOR",
                colspan: 3,
            },
        ],
        [
            "#",
            "Orden",
            "Contrato",
            "Producto",
            "Numero solicitud",
            "Tipo solicitud",
            "Cedula",
            "Nombre",
            "Departamento",
            "Localidad",
            "Barrio",
            "Dirección",
            "Consecutivo Ruta",
            "Telefono",
            "Medidor",
            "Categoria",
            "Unidad",
            "Tipo trabajo",
            "Fecha asignación",
            "Observación solicitud",
            "Orden externa",
            "Tipo solicitud",
            "Fecha solicitud",
            "Observación externa",
            "Fecha reasignación",
            "Fecha programación",
            "Jornada",
            "Telefono usuario",
            "Descripción programacion",
            "Estado programación",
            "Asignación inspector",
            "Fecha asignación inspector",
        ],
    ];

    const container = document.querySelector("#prueba");

    let loader1 = document.getElementById('loader1');

    let buscador = $("#inputBuscar");
    let columnasBuscar = $("#tipoColumnas");
    let falseTrue = $("#falseTrue");
    let cantScrollUp = 0;
    let cantScrollDown = 0;
    let currentPage = 1;
    const maxFilas = 200;
    let hot;
    let overlay = $(".overlay");

    function initHandsontable() {
        hot = new Handsontable(container, {
            language: "es-MX",
            readOnly: true,
            height: "650px",
            manualColumnMove: false,
            nestedHeaders: nestedHeaders,
            afterGetColHeader: function (col, TH) {
                if (col >= 0 && col <= 19) {
                    // Aplica estilos a las primeras 4 columnas
                    TH.style.backgroundColor = "#C4D79B"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                // #FABF8F
                if (col >= 20 && col <= 24) {
                    TH.style.backgroundColor = "#B1A0C7"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                if (col >= 25 && col <= 29) {
                    TH.style.backgroundColor = "#FABF8F"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                if(col >= 30){
                    TH.style.backgroundColor = "#95B3D7"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
            },
            rowHeaders: false,
            colHeaders: true,
            filters: false,
            licenseKey: "non-commercial-and-evaluation",
            fixedColumnsStart: 4,
            dropdownMenu: false,
            manualColumnResize: true,
            manualRowResize: true,
        });
    }

    initHandsontable();

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    let lastScrollTop = 0;

    function scrollPage() {
        hot.addHook(
            "afterScrollVertically",
            debounce(function () {
                let value = buscador.val();
                let tipe = columnasBuscar.val();
                let flag = falseTrue.val();
                const currentScrollTop =
                    hot.view._wt.wtOverlays.topOverlay.getScrollPosition();
                const totalRows = hot.countRows();
                let ruta = "";

                // Scroll hacia abajo
                if (
                    currentScrollTop > lastScrollTop &&
                    hot.view._wt.wtTable.getLastVisibleRow() >= totalRows - 1
                ) {
                    if (cantScrollDown === 0 && cantScrollUp === 1) {
                        currentPage = currentPage + 2;
                        cantScrollDown = 1;
                        cantScrollUp = 0;
                    } else {
                        currentPage++;
                    }
                    if (flag === "false") {
                        ruta = url;
                    } else {
                        ruta = url2;
                    }
                    cargarPagina(currentPage, false, ruta, value, tipe);
                }

                // Scroll hacia arriba
                if (
                    currentScrollTop < lastScrollTop &&
                    hot.view._wt.wtTable.getFirstVisibleRow() === 0 &&
                    currentPage > 1
                ) {
                    if (
                        cantScrollUp === 0 ||
                        (cantScrollUp === 1 && cantScrollDown === 1)
                    ) {
                        if (currentPage === 2) {
                            return;
                        } else {
                            currentPage = currentPage - 2;
                            cantScrollUp = 1;
                            cantScrollDown = 0;
                        }
                    } else {
                        currentPage--;
                    }
                    if (flag === "false") {
                        ruta = url;
                    } else {
                        ruta = url2;
                    }
                    cargarPagina(currentPage, true, ruta, valor, tipo);
                }
                lastScrollTop = currentScrollTop;
            }, 100)
        );
    }
    scrollPage();

    function cargarPagina(pagina, esScrollHaciaArriba, url, valor, tipo) {
        $.ajax({
            url: url,
            method: "GET",
            data: { pagina: pagina, valor: valor, tipo: tipo },
            success: function (response) {
                const nuevosDatos = convertirJSONaArray2D(response.data);
                let datosExistentes = hot.getData();
                let estadoProgramacion = response.estadoProgramacion;
                let tecnicos = response.inspectores;
                let codigoTecnicos = tecnicos.map((item) => item.id+"-"+item.nombres+" "+item.apellidos);

                if (esScrollHaciaArriba) {
                    // Eliminar posibles duplicados antes de concatenar
                    const nuevosDatosSinDuplicados = nuevosDatos.filter(
                        (nuevaFila) =>
                            !datosExistentes.some(
                                (filaExistente) =>
                                    JSON.stringify(filaExistente) ===
                                    JSON.stringify(nuevaFila)
                            )
                    );

                    // Concatenar nuevos datos al inicio
                    datosExistentes =
                        nuevosDatosSinDuplicados.concat(datosExistentes);

                    // Limitar a un máximo de maxFilas
                    if (datosExistentes.length > maxFilas) {
                        datosExistentes = datosExistentes.slice(0, maxFilas);
                    }

                    hot.scrollViewportTo({ row: 124 }); // Ajustar el valor según sea necesario
                } else {
                    // Agregar nuevos datos al final y verificar duplicados
                    const nuevosDatosSinDuplicados = nuevosDatos.filter(
                        (nuevaFila) =>
                            !datosExistentes.some(
                                (filaExistente) =>
                                    JSON.stringify(filaExistente) ===
                                    JSON.stringify(nuevaFila)
                            )
                    );

                    datosExistentes = datosExistentes.concat(
                        nuevosDatosSinDuplicados
                    );

                    // Eliminar filas excedentes si supera el límite
                    if (datosExistentes.length > maxFilas) {
                        const filasAEliminar =
                            datosExistentes.length - maxFilas;
                        datosExistentes = datosExistentes.slice(filasAEliminar);
                    }

                    hot.scrollViewportTo({ row: 75 }); // Ajustar el valor según sea necesario
                }

                // Primero, mapea el array a un objeto con claves más descriptivas
                datosExistentes = datosExistentes.map((fila) => {
                    return {
                        0:fila[0],
                        1:fila[1],
                        2:fila[2],
                        3:fila[3],
                        4:fila[4],
                        5:fila[5],
                        6:fila[6],
                        7:fila[7],
                        8:fila[8],
                        9:fila[9],
                        10:fila[10],
                        11:fila[11],
                        12:fila[12],
                        13:fila[13],
                        14:fila[14],
                        15:fila[15],
                        16:fila[16],
                        17:fila[17],
                        18:fila[18],
                        19:fila[19],
                        20:fila[20],
                        21:fila[21],
                        22:fila[22],
                        23:fila[23],
                        24:fila[24],
                        25:fila[25],
                        26:fila[26],
                        27:fila[27],
                        28:fila[28],
                        "estado_programacion":fila[29],
                        "codigo_tecnico":fila[30], 
                        31:fila[31],
                    };
                });

                datosExistentes = datosExistentes.map((fila) => {
                    let codigoActual = "" + fila['codigo_tecnico'];
                    // Buscar el técnico en el formato correcto
                    let tecnicoSeleccionado = codigoTecnicos.find((tecnico) => tecnico.startsWith(codigoActual));
                    // Si encuentra una coincidencia, actualiza el valor, si no, lo deja igual
                    if (tecnicoSeleccionado) {
                        fila['codigo_tecnico'] = tecnicoSeleccionado.split('-')[0];
                    }
                    return fila;
                });

                hot.updateSettings({
                    columns: [
                        {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                        {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                        {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                        {}, {},
                        {
                            data: 'estado_programacion',
                            type: 'dropdown',
                            source: estadoProgramacion,
                            readOnly: false,
                            strict: true,
                            allowInvalid: false,
                            filter: false,
                        },
                        {
                            data: 'codigo_tecnico',
                            type: 'dropdown',
                            source: codigoTecnicos,
                            readOnly: false,
                            strict: true,
                            className: 'tecnico',
                            filter: false,
                        },
                        {},
                    ],
                });

                // Cargar los datos actualizados y renderizar
                hot.loadData(datosExistentes);
            },
            error(xhr, status, error) {
                console.log(xhr.responseText);
            },
        });
    }

    setTimeout(() => {
        loader1.style.display = 'block';
    },500)
    // Carga inicial de datos
    $.ajax({
        url: url,
        method: "GET",
        success: function (response) {
            container.style.display = "block";
            loader1.style.display = 'none';

            let datos = convertirJSONaArray2D(response.data);
            let estadoProgramacion = response.estadoProgramacion;
            let tecnicos = response.inspectores;
            let codigoTecnicos = tecnicos.map((item) => item.id+"-"+item.nombres+" "+item.apellidos);

            // Primero, mapea el array a un objeto con claves más descriptivas
            datos = datos.map((fila) => {
                return {
                    0:fila[0],
                    1:fila[1],
                    2:fila[2],
                    3:fila[3],
                    4:fila[4],
                    5:fila[5],
                    6:fila[6],
                    7:fila[7],
                    8:fila[8],
                    9:fila[9],
                    10:fila[10],
                    11:fila[11],
                    12:fila[12],
                    13:fila[13],
                    14:fila[14],
                    15:fila[15],
                    16:fila[16],
                    17:fila[17],
                    18:fila[18],
                    19:fila[19],
                    20:fila[20],
                    21:fila[21],
                    22:fila[22],
                    23:fila[23],
                    24:fila[24],
                    25:fila[25],
                    26:fila[26],
                    27:fila[27],
                    28:fila[28],
                    "estado_programacion":fila[29],
                    "codigo_tecnico":fila[30], 
                    31:fila[31],
                };
            });

            datos = datos.map((fila) => {
                let codigoActual = "" + fila['codigo_tecnico'];
                // Buscar el técnico en el formato correcto
                let tecnicoSeleccionado = codigoTecnicos.find((tecnico) => tecnico.startsWith(codigoActual));
                // Si encuentra una coincidencia, actualiza el valor, si no, lo deja igual
                if (tecnicoSeleccionado) {
                    fila['codigo_tecnico'] = tecnicoSeleccionado.split('-')[0];
                }
                return fila;
            });

            hot.updateSettings({
                columns: [
                    {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                    {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                    {}, {}, {}, {}, {}, {}, {}, {}, {}, 
                    {}, {},
                    {
                        data: 'estado_programacion',
                        type: 'dropdown',
                        source: estadoProgramacion,
                        readOnly: false,
                        strict: true,
                        allowInvalid: false,
                        filter: false,
                    },
                    {
                        data: 'codigo_tecnico',
                        type: 'dropdown',
                        source: codigoTecnicos,
                        readOnly: false,
                        strict: true,
                        className: 'tecnico',
                        filter: false,
                    },
                    {},
                ],
            });
            hot.loadData(datos);
        },
        error(xhr, status, error) {
            console.log(xhr.responseText);
        },
    });

    let valor = "";
    let tipo = "";
    $(document).on("change", "#columnasBuscar", function () {
        tipo = $(this).val();

        if (tipo != "") {
            buscador.removeAttr("readonly");
        }

        if (valor != "") {
            setTimeout(() => {
                overlay.show();
                buscar(valor, tipo, buscador);
            }, 0);
        } else {
            buscador.on("input", function () {
                valor = $(this).val();

                const isTipoValido = (tipo, valor) => {
                    const longMinima =
                        tipo === "8" ? 4 : tipo === "1" || tipo === "2" ? 4 : 6;
                    return valor.length > 0 && valor.length < longMinima;
                };

                if (isTipoValido(tipo, valor)) {
                    return;
                }

                buscador.attr("value", valor);
                columnasBuscar.attr("value", tipo);
                falseTrue.attr("value", "true");
                setTimeout(() => {
                    overlay.show();
                    buscar(valor, tipo, buscador);
                }, 1000);
            });
        }
    });

    function buscar(valor, tipo, buscador) {
        $.get({
            url: url2,
            data: { valor: valor, tipo: tipo },
            success: function (response) {
                setTimeout(() => {
                    const datos = convertirJSONaArray2D(response.data);
                    if (datos.length === 0) {
                        Swal.fire({
                            position: "top-end",
                            type: "warning",
                            title: "No se encontraron datos",
                            toast: true,
                            timer: 3000,
                        });
                        const encabezados = new Array(hot.countCols()).fill("");
                        hot.loadData([encabezados]);
                    } else {
                        hot.loadData(datos);
                    }
                }, 1000);
            },
            error(xhr, status, error) {
                console.log(xhr.responseText);
            },
            complete: function () {
                overlay.hide();
            },
        });
    }

    hot.addHook('afterChange', function(changes, source) {
        if (source === 'programmatic') {
            return;
        }
        if (changes) {
            changes.forEach(function([row, prop, oldValue, newValue]) {
                let ordenEnviar = hot.getDataAtRow(row, 1);
                let token = $('#tokenCoordinacionRP').val();
                let valorSeleccion = newValue;

                if (prop === 'codigo_tecnico') {
                    codigoTecnico = valorSeleccion.split('-');
                    if(codigoTecnico[0] != ""){
                        if(valorSeleccion.includes(codigoTecnico[0])){
                           
                            let tiempoTranscurrido = Date.now();
                            let hoy = new Date(tiempoTranscurrido);
                            let fechaActual = hoy.toISOString().split('T')[0];

                            hot.setDataAtCell(row, 30, codigoTecnico[0], 'programmatic');
                            hot.setDataAtCell(row, 31, fechaActual, 'programmatic');

                            setTimeout(() => {
                                $.post({
                                    url: url3,
                                    data: {
                                        codigoTecnico : codigoTecnico[0],
                                        ordenEnviar: ordenEnviar[1],
                                        _token: token
                                    },
                                    success: function (response) {
                                        if(response == 3){
                                            Swal.fire({
                                                position: "top-end",
                                                type: "warning",
                                                title: "El codigo del tecnico es incorrecto",
                                                toast: true,
                                                timer: 3000,
                                            });
                                            // ponemos la celda de color rojo is invalid
                                            hot.setDataAtCell(row, 30, "", 'programmatic');
                                            hot.setDataAtCell(row, 31, "", 'programmatic');
                                        }
                                    },
                                    error(xhr, status, error) {
                                        // console.log(xhr.responseText);
                                    },
                                })
                            },500)
                        }
                    }
                }else if(prop === 'estado_programacion'){
                    if(valorSeleccion != ""){
                        $.post({
                            url: url3,
                            data: {
                                estado: valorSeleccion,
                                ordenEnviar: ordenEnviar[1],
                                _token: token
                            },
                            success: function (response) {
                                
                            },
                            error(xhr, status, error) {
                                Swal.fire({
                                    position: "top-end",
                                    type: "error",
                                    title: error,
                                    toast: true,
                                    timer: 3000,
                                });
                            },
                        })
                    }
                }
            });
        }
    });
});