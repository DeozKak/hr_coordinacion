function convertirJSONaArray2D(jsonData) {
    const columnasDeseadas = [
        // 1. ASIGNACION BASE OSF
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
        // 2. INFORMACIÓN COMPLEMENTARIA 12161
        "orden_solicitud_externa",
        "tipo_solicitud_externa",
        "fecha_solicitud_externa",
        "observacion_externa",
        "fecha_reasignacion_externa",
        // 3. PROGRAMACIÓN DE ORDENES
        "FECHA_AGENDAMIENTO",
        "jornada",
        "CELULAR",
        "OBSERVACIONES",
        "estado_programacion",
        // 4. ASIGNACIÓN INSPECTOR
        "codigo_tecnico",
        "fecha_asignacion_inspector",
        // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
        "estado_recepcion",
        "fecha_recepcion",
        "cantidad_vne",
        "ultima_vne",
        "fecha_ultima_vne",
        "inspector_ultima_vne",
        "compilado_observacion",
        "causa_cierre",
        "fecha_solicitud_cierre",
        // 6.GESTIÓN REALIZADA OFICINA
        "num_acta",
        "validacion_formato",
        "observacion_rechazo",
        //7. FORMULACIÓN Y CALCULO
        "dia_ingreso",
        "tipo_orden",
        "sede",
        "grupo",
        "subgrupo",
        "meses",
        "fecha_vence_certificado",
        "dias_ejecutar",
        "cumplimiento_politicas",
        "cartera",
        "consumo",
        "fecha_ult_cert",
        "estado_gestion",
        "ult_comentario",
        "nom_inspector",
        "dias_gestion_actual",
        "fecha_actual",
        "marca"
    ];

    return Object.keys(jsonData).map((key) => {
        const fila = jsonData[key];
        return columnasDeseadas.map((columna) => fila[columna]);
    });
}

document.addEventListener("DOMContentLoaded", function () {

    let codeTechnician = $('#codigo_tecnico');
    let totalResults = $('.totalResults');

    let idSede = $('#id_sede');
    let idGrupo = $('#id_grupo');
    let idSubGrupo = $('#id_subGrupo')

    codeTechnician.each(function () {
        new TomSelect(this, {
            plugins: ['remove_button'],
            maxItems: null,
            render: {
                no_results: function () {
                    return `<div class="create">No hay resultados</div>`;
                },
            },
        });
    })

    let selectSubGroup = new TomSelect(idSubGrupo, {
        plugins: ['remove_button'],
        maxItems: null,
        render: {
            no_results: function () {
                return `<div class="create">No hay resultados</div>`;
            },
        },
    })

    let selectGroup = new TomSelect(idGrupo, {
        plugins: ['remove_button'],
        maxItems: null,
        render: {
            no_results: function () {
                return `<div class="create">No hay resultados</div>`;
            },
        },
    });

    let selectSede = new TomSelect(idSede, {
        plugins: ['remove_button'],
        maxItems: null,
        render: {
            no_results: function () {
                return `<div class="create">No hay resultados</div>`;
            },
        },
    });

    selectGroup.on('item_remove', function(){
        getDataGroups(2)
    })

    selectGroup.on('blur', function(){
        getDataGroups(2)
    })


    selectSede.on('item_remove', function() {
        getDataGroups(1)
    })
    selectSede.on('blur', function() {
        getDataGroups(1)
    })

    function getDataGroups(type){
        let selectedValues;
        let concat;
        let urlSend;
        if(type == 1){
            selectedValues = selectSede.getValue();
            concat = '?idSede='
            urlSend = url4
        }else{
            selectedValues = selectGroup.getValue()
            concat = '?idGrupo='
            urlSend = url5
        }
        let url = urlSend + concat + encodeURIComponent(selectedValues);
        fetch(url)
            .then(response => response.json())
            .then(data => {
                if(data.tipo == 1){
                    let groups = data.grupos
                    selectGroup.clearOptions();
                    groups.forEach(group => {
                        selectGroup.addOption({ value: group.id, text: group.grupo });
                    });
                }else{
                    let subGroups = data.subGrupo
                    selectSubGroup.clearOptions();
                    subGroups.forEach(subGroup => {
                        selectSubGroup.addOption({ value: subGroup.id, text: subGroup.subgrupo });
                    });
                }
            })
            .catch(error => {
                console.error('Error al hacer la solicitud:', error); // Manejar errores
            });
    }

    let inputsFilters = $('#orden, #orden_solicitud_externa, #contrato');

    inputsFilters.each(function () {
        new TomSelect(this, {
            plugins: ['remove_button'],
            persist: false,
            delimiter: ',',
            create: true,
            maxItems: null,
            render: {
                no_results: function () {
                    return '';
                },
                option_create: function (data, escape) {
                    return `<div class="create">Añadir <strong>${escape(data.input)}</strong></div>`;
                },
            },
            createFilter: (input) => {
                return /^\d+$/.test(input);
            },
            onInitialize: function() {
                // Agregar evento paste al input de control
                this.control_input.addEventListener('paste', (e) => {
                    e.preventDefault();

                    // Obtener el texto pegado
                    let pastedText = (e.clipboardData || window.clipboardData).getData('text');

                    // Separar el texto por espacios o comas
                    let valores = pastedText.split(/[\s,]+/);

                    // Filtrar valores vacíos y agregar cada valor
                    valores.forEach(valor => {
                        valor = valor.trim();
                        if (valor && /^\d+$/.test(valor)) {
                            this.addOption({
                                value: valor,
                                text: valor
                            });
                            this.addItem(valor);
                        }
                    });
                });
            }
        });
    });

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
                colspan: 2,
            },
            {
                label: "5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO",
                colspan: 9
            },
            {
                label: "6. GESTIÓN REALIZADA OFICINA",
                colspan: 3
            },
            {
                label: "7. FORMULACIÓN Y CALCULO",
                colspan: 18
            }
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
            "Estado recepción",
            "Fecha recepción",
            "# VNE",
            "Estado ultima VNE",
            "Fecha ultima VNE",
            "Inspector ultima VNE",
            "Compilado observación",
            "Causa cierre",
            "Fecha solicitud de cierre",
            "Acta real",
            "Validación formato",
            "Observacion rechazo",
            "Día ingreso",
            "Tipo orden",
            "Sede",
            "Grupo",
            "Sub grupo",
            "Meses",
            "Fecha vence certificado",
            "Días para ejecutar",
            "Cumplimiento politicas",
            "Cartera",
            "Consumo",
            "Fecha ultimo certificado",
            "Estado gestion",
            "Observacion OSF",
            "Nombre inspector",
            "Días gestion actual",
            "Fecha actual",
            "Marca"
        ],
    ];

    const container = document.querySelector("#prueba");

    let loaderPageCoordination = document.getElementById('loaderPageCoordination');
    let cardCoordination = document.querySelector('.cardCoordination');

    let cantScrollUp = 0;
    let cantScrollDown = 0;
    let currentPage = 1;
    const maxFilas = 200;
    let hot;

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
                if(col >= 30 && col <= 31){
                    TH.style.backgroundColor = "#95B3D7"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                if(col >= 32 && col <= 40){
                    TH.style.backgroundColor = "#C0504D"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                if(col >= 41 && col <= 43){
                    TH.style.backgroundColor = "#8064A2"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
                if(col >= 44){
                    TH.style.backgroundColor = "#92CDDC"; // Cambia el color de fondo
                    TH.style.color = "white"; // Cambia el color del texto
                    TH.style.fontWeight = "bold";
                }
            },
            rowHeaders: false,
            colHeaders: true,
            filters: false,
            licenseKey: "non-commercial-and-evaluation",
            fixedColumnsStart: 5,
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
                const currentScrollTop = hot.view._wt.wtOverlays.topOverlay.getScrollPosition();
                const totalRows = hot.countRows();

                // Scroll hacia abajo
                if (currentScrollTop > lastScrollTop && hot.view._wt.wtTable.getLastVisibleRow() >= totalRows - 1) {
                    if (cantScrollDown === 0 && cantScrollUp === 1) {
                        currentPage = currentPage + 2;
                        cantScrollDown = 1;
                        cantScrollUp = 0;
                    } else {
                        currentPage++;
                    }
                    cargarPagina(currentPage, false);
                }

                // Scroll hacia arriba
                if (currentScrollTop < lastScrollTop && hot.view._wt.wtTable.getFirstVisibleRow() === 0 && currentPage > 1) {
                    if (cantScrollUp === 0 ||(cantScrollUp === 1 && cantScrollDown === 1)) {
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
                    cargarPagina(currentPage, true);
                }
                lastScrollTop = currentScrollTop;
            }, 100)
        );
    }
    scrollPage();

    function cargarPagina(pagina, esScrollHaciaArriba) {
        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        let datosFormulario = {};

        // Recolectar datos del formulario
        $('#formSearchCoordination :input').not(':button, #tokenCoordinacionRP').each(function() {
            let value = $(this).val();
            if (value != "") {
                datosFormulario[$(this).attr('id')] = value;
            }
        });

        // Reorganizar los datos en el formato deseado
        let datosCombinados = {
            datos: {
                id_grupo: datosFormulario['id_grupo'] || [],
                id_subGrupo: datosFormulario['id_subGrupo'] || []
            }
        };

        delete datosFormulario['id_grupo'];
        delete datosFormulario['id_subGrupo'];

        datosFormulario = {
            ...datosFormulario,
            ...datosCombinados
        };

        let url;
        if(Object.keys(datosFormulario).length === 1){
            url = url1
        }else{
            url = url2
        }

        $.ajax({
            url: url,
            method: "GET",
            data: {
                pagina: pagina,
                datosFormulario: datosFormulario
            },
            success: function (response) {
                const nuevosDatos = convertirJSONaArray2D(response.data);
                let tiempoTranscurrido = Date.now();
                let hoy = new Date(tiempoTranscurrido).toISOString().split('T')[0];
                let datosExistentes = hot.getData();
                let estadoProgramacion = response.estadoProgramacion;
                let tecnicos = response.inspectores;
                let codigoTecnicos = tecnicos.map((item) => item.id+"-"+item.nombres+" "+item.apellidos);
                let causaCierre = response.causasCierre
                causaCierre = causaCierre.map((item) => item.id+"-"+item.causa_cierre)
                causaCierre.unshift('Seleccione...')

                if(nuevosDatos.length > 0){
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
                            // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                            32:fila[32],
                            33:fila[33],
                            34:fila[34],
                            35:fila[35],
                            36:fila[36],
                            37:fila[37],
                            38:fila[38],
                            "causa_cierre":fila[39],
                            "fecha_solicitud_cierre":fila[40],
                            // 6.GESTIÓN REALIZADA OFICINA
                            41:fila[41],
                            42:fila[42],
                            43:fila[43],
                            //7. FORMULACIÓN Y CALCULO
                            44:fila[44],
                            45:fila[45],
                            46:fila[46],
                            47:fila[47],
                            48:fila[48],
                            49:fila[49],
                            50:fila[50],
                            51:fila[51],
                            52:fila[52],
                            53:fila[53],
                            54:fila[54],
                            55:fila[55],
                            56:fila[56],
                            57:fila[57],
                            58:fila[58],
                            'dias_gestion_actual':fila[59],
                            'fecha_actual':fila[60],
                        };
                    });

                    datosExistentes = datosExistentes.map((fila) => {
                        let codigoCausa = fila["causa_cierre"];
                        let codigoActual = "" + fila['codigo_tecnico'];
                        // Buscar el técnico en el formato correcto
                        let tecnicoSeleccionado = codigoTecnicos.find((tecnico) => tecnico.startsWith(codigoActual));
                        let causaSeleccionada = causaCierre.find((causa) => causa.startsWith(codigoCausa))
                        // Si encuentra una coincidencia, actualiza el valor, si no, lo deja igual
                        if (tecnicoSeleccionado) {
                            fila['codigo_tecnico'] = tecnicoSeleccionado.split('-')[0];
                        }

                        if(causaSeleccionada){
                            fila['causa_cierre'] = causaSeleccionada;
                        }

                        if(fila[31] != null){

                            let fechaAsignacion = new Date(fila[31])
                            let fechaActual = new Date(hoy)
                            let diferenciaMilisegundos = fechaActual - fechaAsignacion

                            let diferenciaDias = diferenciaMilisegundos / (1000 * 60 * 60 * 24);

                            fila['dias_gestion_actual'] = diferenciaDias
                        }else{
                            fila['dias_gestion_actual'] = ""
                        }

                        fila['fecha_actual'] = hoy;

                        return fila;
                    });

                    hot.updateSettings({
                        columns: [
                            // 1. ASIGNACION BASE OSF
                            {}, {}, {}, {}, {}, {}, {}, {}, {},
                            {}, {}, {}, {}, {}, {}, {}, {}, {},
                            {},{},
                            // 2. INFORMACIÓN COMPLEMENTARIA 12161
                            {}, {}, {}, {}, {},
                            // 3. PROGRAMACIÓN DE ORDENES
                            {}, {}, {}, {},
                            {
                                data: 'estado_programacion',
                                type: 'dropdown',
                                source: estadoProgramacion,
                                readOnly: false,
                                strict: true,
                                allowInvalid: false,
                                filter: false,
                            },
                            // 4. ASIGNACIÓN INSPECTOR
                            {
                                data: 'codigo_tecnico',
                                type: 'dropdown',
                                source: codigoTecnicos,
                                readOnly: false,
                                strict: true,
                                className: 'tecnico',
                                filter: false,
                            },{},
                            // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                            {},{},{},{},{},{},{},
                            {
                                data: 'causa_cierre',
                                type: 'dropdown',
                                source: causaCierre,
                                readOnly: false,
                                strict: true,
                                allowInvalid: false,
                                filter: false,
                            },
                            {
                                data: 'fecha_solicitud_cierre',
                                type: 'date',
                                dateFormat: 'YYYY-MM-DD',
                                correctFormat: true,
                                readOnly: false, // Cambia a 'false' si deseas permitir la edición manual
                            },
                            // 6.GESTIÓN REALIZADA OFICINA
                            {},{},{},
                            //7. FORMULACIÓN Y CALCULO
                            {},{},{},{},{},{},{},{},{},{},{},{},{},{},{},
                            {
                                data: 'dias_gestion_actual',
                                type: 'text',
                                readOnly: true,
                            },
                            {
                                data: 'fecha_actual',
                                type: 'text',
                                readOnly: true,
                            },
                            {
                                data: 'marca',
                                type: 'checkbox',
                            }
                        ],
                    });

                    // Cargar los datos actualizados y renderizar
                    hot.loadData(datosExistentes);
                }else{
                    if (!esScrollHaciaArriba && currentPage > 1) {
                        currentPage--;
                    }
                    overlay.style.display = 'none';
                }
                overlay.style.display = 'none';
            },
            error(xhr, status, error) {
                console.log(xhr.responseText);
            },
        });
    }

    setTimeout(() => {
        loaderPageCoordination.style.display = 'block';
    },500)

    // Carga inicial de datos
    $.ajax({
        url: url1,
        method: "GET",
        success: function (response) {
            cardCoordination.style.display = 'block';
            loaderPageCoordination.style.display = "none";

            let tiempoTranscurrido = Date.now();
            let hoy = new Date(tiempoTranscurrido).toISOString().split('T')[0];
            let datos = convertirJSONaArray2D(response.data);
            let estadoProgramacion = response.estadoProgramacion;
            let tecnicos = response.inspectores;
            let codigoTecnicos = tecnicos.map((item) => item.id+"-"+item.nombres+" "+item.apellidos);
            let causaCierre = response.causasCierre
            causaCierre = causaCierre.map((item) => item.id+"-"+item.causa_cierre)
            causaCierre.unshift('Seleccione...')

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
                    // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                    32:fila[32],
                    33:fila[33],
                    34:fila[34],
                    35:fila[35],
                    36:fila[36],
                    37:fila[37],
                    38:fila[38],
                    "causa_cierre":fila[39],
                    "fecha_solicitud_cierre":fila[40],
                    // 6.GESTIÓN REALIZADA OFICINA
                    41:fila[41],
                    42:fila[42],
                    43:fila[43],
                    //7. FORMULACIÓN Y CALCULO
                    44:fila[44],
                    45:fila[45],
                    46:fila[46],
                    47:fila[47],
                    48:fila[48],
                    49:fila[49],
                    50:fila[50],
                    51:fila[51],
                    52:fila[52],
                    53:fila[53],
                    54:fila[54],
                    55:fila[55],
                    56:fila[56],
                    57:fila[57],
                    58:fila[58],
                    'dias_gestion_actual':fila[59],
                    'fecha_actual':fila[60],
                    'marca':fila[61],
                };
            });

            datos = datos.map((fila) => {
                let codigoCausa = fila["causa_cierre"];
                let codigoActual = "" + fila['codigo_tecnico'];
                // Buscar el técnico en el formato correcto
                let tecnicoSeleccionado = codigoTecnicos.find((tecnico) => tecnico.startsWith(codigoActual));
                let causaSeleccionada = causaCierre.find((causa) => causa.startsWith(codigoCausa))
                // Si encuentra una coincidencia, actualiza el valor, si no, lo deja igual
                if (tecnicoSeleccionado) {
                    fila['codigo_tecnico'] = tecnicoSeleccionado.split('-')[0];
                }

                if(causaSeleccionada){
                    fila['causa_cierre'] = causaSeleccionada;
                }

                if(fila[31] != null){

                    let fechaAsignacion = new Date(fila[31])
                    let fechaActual = new Date(hoy)
                    let diferenciaMilisegundos = fechaActual - fechaAsignacion

                    let diferenciaDias = diferenciaMilisegundos / (1000 * 60 * 60 * 24);

                    fila['dias_gestion_actual'] = diferenciaDias
                }else{
                    fila['dias_gestion_actual'] = ""
                }

                fila['marca'] = fila['marca'] === 1 ? true : false;

                fila['fecha_actual'] = hoy;

                return fila;
            });

            hot.updateSettings({
                columns: [
                    // 1. ASIGNACION BASE OSF
                    {}, {}, {}, {}, {}, {}, {}, {}, {},
                    {}, {}, {}, {}, {}, {}, {}, {}, {},
                    {},{},
                    // 2. INFORMACIÓN COMPLEMENTARIA 12161
                    {}, {}, {}, {}, {},
                    // 3. PROGRAMACIÓN DE ORDENES
                    {}, {}, {}, {},
                    {
                        data: 'estado_programacion',
                        type: 'dropdown',
                        source: estadoProgramacion,
                        readOnly: false,
                        strict: true,
                        allowInvalid: false,
                        filter: false,
                    },
                    // 4. ASIGNACIÓN INSPECTOR
                    {
                        data: 'codigo_tecnico',
                        type: 'dropdown',
                        source: codigoTecnicos,
                        readOnly: false,
                        strict: true,
                        className: 'tecnico',
                        filter: false,
                    },{},
                    // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                    {},{},{},{},{},{},{},
                    {
                        data: 'causa_cierre',
                        type: 'dropdown',
                        source: causaCierre,
                        readOnly: false,
                        strict: true,
                        allowInvalid: false,
                        filter: false,
                    },
                    {
                        data: 'fecha_solicitud_cierre',
                        type: 'date',
                        dateFormat: 'YYYY-MM-DD',
                        correctFormat: true,
                        readOnly: false, // Cambia a 'false' si deseas permitir la edición manual
                    },
                    // 6.GESTIÓN REALIZADA OFICINA
                    {},{},{},
                    //7. FORMULACIÓN Y CALCULO
                    {},{},{},{},{},{},{},{},{},{},{},{},{},{},{},
                    {
                        data: 'dias_gestion_actual',
                        type: 'text',
                        readOnly: true,
                    },
                    {
                        data: 'fecha_actual',
                        type: 'text',
                        readOnly: true,
                    },
                    {
                        data: 'marca',
                        type: 'checkbox',
                        readOnly: false,
                        checkedTemplate: true,
                        uncheckedTemplate: false,
                    },
                ],
            });
            hot.loadData(datos);
            totalResults.text(`Total registros: ${response.totalResults}`)
        },
        error(xhr, status, error) {
            console.log(xhr.responseText);
        },
    });

    hot.addHook('afterChange', function(changes, source) {
        if (source === 'programmatic' || source === 'masivo') {
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
                            let nombrePartes = codigoTecnico[1].split(' ')

                            console.log(nombrePartes)

                            let nombreInspector = "";

                            if(nombrePartes.length == 4){
                                nombreInspector = nombrePartes[2]+" "+nombrePartes[3]+" "+nombrePartes[0]+" "+nombrePartes[1]
                            }else{
                                nombreInspector = nombrePartes[1]+" "+nombrePartes[2]+" "+nombrePartes[0]
                            }

                            hot.setDataAtCell(row, 30, codigoTecnico[0], 'programmatic');
                            hot.setDataAtCell(row, 31, fechaActual, 'programmatic');
                            hot.setDataAtCell(row, 58, nombreInspector, 'programmatic');

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
                                                icon: "warning",
                                                title: "El codigo del tecnico es incorrecto",
                                                toast: true,
                                                timer: 3000,
                                            });
                                            // ponemos la celda de color rojo is invalid
                                            hot.setDataAtCell(row, 30, "", 'programmatic');
                                            hot.setDataAtCell(row, 31, "", 'programmatic');
                                            hot.setDataAtCell(row, 58, "", 'programmatic')
                                        }
                                    },
                                    error(xhr, status, error) {
                                        Swal.fire({
                                            position: "top-end",
                                            icon: "error",
                                            title: error,
                                            toast: true,
                                            timer: 3000,
                                        });
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
                                    icon: "error",
                                    title: error,
                                    toast: true,
                                    timer: 3000,
                                });
                            },
                        })
                    }
                }else if(prop === 'causa_cierre'){
                    $.post({
                        url: url7,
                        data: {
                            causaCierre: valorSeleccion,
                            ordenEnviar: ordenEnviar[1],
                            _token: token
                        },
                        success: function (response) {
                            if(response == 1){
                                Swal.fire({
                                    position: "top-end",
                                    icon: "success",
                                    title: 'Causa cierre registrada con exito',
                                    toast: true,
                                    timer: 3000,
                                });
                            }else{
                                Swal.fire({
                                    position: "top-end",
                                    icon: "error",
                                    title: 'Error al guardar la causa cierre',
                                    toast: true,
                                    timer: 3000,
                                });
                            }
                        },
                        error(xhr, status, error) {
                            Swal.fire({
                                position: "top-end",
                                icon: "error",
                                title: error,
                                toast: true,
                                timer: 3000,
                            });
                        },
                    })
                }else if(prop === 'fecha_solicitud_cierre'){
                    $.post({
                        url: url8,
                        data: {
                            fechaSolicitudCierre: valorSeleccion,
                            ordenEnviar: ordenEnviar[1],
                            _token: token
                        },
                        success: function (response) {
                            if(response == 1){
                                Swal.fire({
                                    position: "top-end",
                                    icon: "success",
                                    title: 'Fecha solicitud cierre registrada con exito',
                                    toast: true,
                                    timer: 3000,
                                });
                            }else{
                                Swal.fire({
                                    position: "top-end",
                                    icon: "error",
                                    title: 'Error al guardar la Fecha solicitud cierre',
                                    toast: true,
                                    timer: 3000,
                                });
                            }
                        },
                        error(xhr, status, error) {
                            Swal.fire({
                                position: "top-end",
                                icon: "error",
                                title: error,
                                toast: true,
                                timer: 3000,
                            });
                        },
                    })
                }else if(prop === 'marca'){
                    // marcar los tipo checkbox individual
                    $.post({
                        url:url9,
                        data:{
                            ordenEnviar: ordenEnviar[1],
                            _token: token
                        },
                        success:function(response){

                        }
                    })
                }
            });
        }
    });

    $(document).on('click', '.btnSearchCoordination', function(){

        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        let datosFormulario = {};

        // Recolectar datos del formulario
        $('#formSearchCoordination :input').not(':button, #tokenCoordinacionRP').each(function() {
            let value = $(this).val();
            if (value != "") {
                datosFormulario[$(this).attr('id')] = value;
            }
        });

        // Reorganizar los datos en el formato deseado
        let datosCombinados = {
            datos: {
                id_grupo: datosFormulario['id_grupo'] || [],
                id_subGrupo: datosFormulario['id_subGrupo'] || []
            },
            dias:{
                dia_inicio: datosFormulario['diasInicio'] || [],
                dia_fin: datosFormulario['diasFin'] || [],
            }
        };


        delete datosFormulario['id_grupo'];
        delete datosFormulario['id_subGrupo'];
        delete datosFormulario['diasInicio'];
        delete datosFormulario['diasFin'];

        datosFormulario = {
            ...datosFormulario,
            ...datosCombinados
        };

        $.get({
            url: url2,
            data: {
                datosFormulario: datosFormulario,
            },
            success: function (response) {

                let tiempoTranscurrido = Date.now();
                let hoy = new Date(tiempoTranscurrido).toISOString().split('T')[0];
                let datos = convertirJSONaArray2D(response.data);
                let estadoProgramacion = response.estadoProgramacion;
                let tecnicos = response.inspectores;
                let codigoTecnicos = tecnicos.map((item) => item.id+"-"+item.nombres+" "+item.apellidos);
                let causaCierre = response.causasCierre
                causaCierre = causaCierre.map((item) => item.id+"-"+item.causa_cierre)
                causaCierre.unshift('Seleccione...')

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
                        // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                        32:fila[32],
                        33:fila[33],
                        34:fila[34],
                        35:fila[35],
                        36:fila[36],
                        37:fila[37],
                        38:fila[38],
                        "causa_cierre":fila[39],
                        "fecha_solicitud_cierre":fila[40],
                        // 6.GESTIÓN REALIZADA OFICINA
                        41:fila[41],
                        42:fila[42],
                        43:fila[43],
                        //7. FORMULACIÓN Y CALCULO
                        44:fila[44],
                        45:fila[45],
                        46:fila[46],
                        47:fila[47],
                        48:fila[48],
                        49:fila[49],
                        50:fila[50],
                        51:fila[51],
                        52:fila[52],
                        53:fila[53],
                        54:fila[54],
                        55:fila[55],
                        56:fila[56],
                        57:fila[57],
                        58:fila[58],
                        'dias_gestion_actual':fila[59],
                        'fecha_actual':fila[60],
                        'marca':fila[61],
                    };
                });

                datos = datos.map((fila) => {
                    let codigoCausa = fila["causa_cierre"];
                    let codigoActual = "" + fila['codigo_tecnico'];
                    // Buscar el técnico en el formato correcto
                    let tecnicoSeleccionado = codigoTecnicos.find((tecnico) => tecnico.startsWith(codigoActual));
                    let causaSeleccionada = causaCierre.find((causa) => causa.startsWith(codigoCausa))
                    // Si encuentra una coincidencia, actualiza el valor, si no, lo deja igual
                    if (tecnicoSeleccionado) {
                        fila['codigo_tecnico'] = tecnicoSeleccionado.split('-')[0];
                    }

                    if(causaSeleccionada){
                        fila['causa_cierre'] = causaSeleccionada;
                    }

                    if(fila[31] != null){

                        let fechaAsignacion = new Date(fila[31])
                        let fechaActual = new Date(hoy)
                        let diferenciaMilisegundos = fechaActual - fechaAsignacion

                        let diferenciaDias = diferenciaMilisegundos / (1000 * 60 * 60 * 24);

                        fila['dias_gestion_actual'] = diferenciaDias
                    }else{
                        fila['dias_gestion_actual'] = ""
                    }

                    fila['marca'] = fila['marca'] === 1 ? true : false;

                    fila['fecha_actual'] = hoy;

                    return fila;
                });

                hot.updateSettings({
                    columns: [
                        // 1. ASIGNACION BASE OSF
                        {}, {}, {}, {}, {}, {}, {}, {}, {},
                        {}, {}, {}, {}, {}, {}, {}, {}, {},
                        {},{},
                        // 2. INFORMACIÓN COMPLEMENTARIA 12161
                        {}, {}, {}, {}, {},
                        // 3. PROGRAMACIÓN DE ORDENES
                        {}, {}, {}, {},
                        {
                            data: 'estado_programacion',
                            type: 'dropdown',
                            source: estadoProgramacion,
                            readOnly: false,
                            strict: true,
                            allowInvalid: false,
                            filter: false,
                        },
                        // 4. ASIGNACIÓN INSPECTOR
                        {
                            data: 'codigo_tecnico',
                            type: 'dropdown',
                            source: codigoTecnicos,
                            readOnly: false,
                            strict: true,
                            className: 'tecnico',
                            filter: false,
                        },{},
                        // 5. RECEPCIÓN GESTIÓN REALIZADA EN CAMPO
                        {},{},{},{},{},{},{},
                        {
                            data: 'causa_cierre',
                            type: 'dropdown',
                            source: causaCierre,
                            readOnly: false,
                            strict: true,
                            allowInvalid: false,
                            filter: false,
                        },
                        {
                            data: 'fecha_solicitud_cierre',
                            type: 'date',
                            dateFormat: 'YYYY-MM-DD',
                            correctFormat: true,
                            readOnly: false, // Cambia a 'false' si deseas permitir la edición manual
                        },
                        // 6.GESTIÓN REALIZADA OFICINA
                        {},{},{},
                        //7. FORMULACIÓN Y CALCULO
                        {},{},{},{},{},{},{},{},{},{},{},{},{},{},{},
                        {
                            data: 'dias_gestion_actual',
                            type: 'text',
                            readOnly: true,
                        },
                        {
                            data: 'fecha_actual',
                            type: 'text',
                            readOnly: true,
                        },
                        {
                            data: 'marca',
                            type: 'checkbox',
                            readOnly: false,
                            checkedTemplate: true,
                            uncheckedTemplate: false,
                        },
                    ],
                });

                hot.loadData(datos);

                totalResults.text(`Total registros: ${response.totalResults}`)

                if(datos.length === 0){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Advertencia',
                        text: 'No se encontraron datos con los filtros seleccionados'
                    })
                    totalResults.text(`Total registros: 0`)
                }

                loaderPageCoordination.style.display = "none";
                overlay.style.display = 'none';
            }
        })
    })

    // funcion que limpia el formulario
    $(document).on('click', '.btnClearCoordination', function(){
        $('#formSearchCoordination :input').not(':button').each(function() {
            $(this).val('');
        });

        $('#formSearchCoordination .ts-wrapper').each(function() {
            let tomSelect = $(this).prev()[0].tomselect;
            if(tomSelect) {
                tomSelect.clear();
            }
        });
    })

    // descargar excel coodrinacion
    let descargarExcel = document.getElementById('descargarExcelCoordination')
    descargarExcel.addEventListener('click', function(){
        window.location.href = url6;
    })

    $(document).on('input', '.inputNumericoDias', function(){
        var valor = $(this).val();
        var regex = /^-?\d*$/;

        if (!regex.test(valor)) {
            $(this).val(valor.replace(/[^0-9\-]/g, ''));
        }
    })

    $(document).on('click', '#marcarTodos', function(){

        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        let estaMarcado = $(this).prop('checked');
        let token = $('#tokenCoordinacionRP').val();
        marcarTodosCheckboxes(estaMarcado);

        let datosFormulario = {};

        $('#formSearchCoordination :input').not(':button, #tokenCoordinacionRP').each(function() {
            let value = $(this).val();
            if (value != "") {
                datosFormulario[$(this).attr('id')] = value;
            }
        });

        // Reorganizar los datos en el formato deseado
        let datosCombinados = {
            datos: {
                id_grupo: datosFormulario['id_grupo'] || [],
                id_subGrupo: datosFormulario['id_subGrupo'] || []
            },
            dias:{
                dia_inicio: datosFormulario['diasInicio'] || [],
                dia_fin: datosFormulario['diasFin'] || [],
            }
        };


        delete datosFormulario['id_grupo'];
        delete datosFormulario['id_subGrupo'];
        delete datosFormulario['diasInicio'];
        delete datosFormulario['diasFin'];

        datosFormulario = {
            ...datosFormulario,
            ...datosCombinados
        };

        $.post({
            url: url10,
            data:{
                datosFormulario: datosFormulario,
                marca: estaMarcado,
                _token: token,
            },success:function(response){
                if(response == 1){
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Se marcaron todas las ordenes",
                        toast: true,
                        timer: 3000,
                    });
                }else{
                    Swal.fire({
                        position: "top-end",
                        icon: "success",
                        title: "Se desmarcaron todas las ordenes",
                        toast: true,
                        timer: 3000,
                    });
                }

                loaderPageCoordination.style.display = "none";
                overlay.style.display = 'none';
            },error:function(){
                loaderPageCoordination.style.display = "none";
                overlay.style.display = 'none';

                Swal.fire({
                    position: "top-end",
                    icon: "error",
                    title: "Error al marcar las ordenes",
                    toast: true,
                    timer: 3000,
                });
            }
        })

    })

    function marcarTodosCheckboxes(marcar) {
        const data = hot.getData(); // Obtén los datos de la tabla
        const columnaCheckbox = data[0].length - 1; // Índice de la columna 'marca' (ajústalo si es necesario)
        data.forEach((_, fila) => {
            // Establece el valor de la celda de la columna 'marca' a true o false
            hot.setDataAtCell(fila, columnaCheckbox, marcar, 'masivo');
        });
    }

    $(document).on('click', '#impresionMasiva', function (){
        let modalImpMasiva = $('#modalImpMasiva');
        modalImpMasiva.modal();
    })

    $(document).on('change', '#fechaAsigna', function(){
        let valorFecha = $(this).val()
        let divfecha = $('.divFecha')

        if(valorFecha == "si"){
            divfecha.show()
        }else{
            divfecha.hide()
        }
    })

    $(document).on('click', '#asignarOrdCercania', function(){

        let token = $('#tokenCoordinacionRP').val();

        $.post({
            url:url11,
            data:{
                _token:token,
            },
            success:function(response){
                if(response == 1){
                    Swal.fire({
                        position: "center",
                        icon: "warning",
                        title: "Sin ordenes para asignar por cercanía",
                        toast: false,
                        timer: 3000,
                    });
                }else{
                    Swal.fire({
                        position: "center",
                        icon: "success",
                        title: "Ordenes asignadas con exito",
                        toast: false,
                        timer: 3000,
                    });
                    setTimeout(() => {
                        window.location.reload();
                    },500)
                }
            }
        })
    })
    //por si el overlay no desaparece
    setTimeout(() => {
        loaderPageCoordination.style.display = "none";
    },500)

});
