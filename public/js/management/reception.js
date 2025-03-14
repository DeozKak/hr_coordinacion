$(document).ready(function(){

    let selectChoices = $('#ccOperario');
    let totalResults = $('.totalResults');

    new TomSelect(selectChoices, {
        plugins: ['remove_button'],
        maxItems: null,
        render: {
            no_results: function () {
                return `<div class="create">No hay resultados</div>`;
            },
        },
    });

    let inputsFilters = $('#ordenTrabajo, #ordenExterna, #numeroSolicitud, #contrato, #numActa');

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
            "#",
            "Orden principal",
            "Orden segundaria",
            "Número solicitud",
            "Contrato",
            "Direccion",
            "Codigo tecnico",
            "Tipo",
            "Estado receptión",
            "Fecha recepcion",
            "Acta",
        ],
    ];

    let contarinerReception = $('#tableReception');
    let loaderPageReception = $('#loaderPageReception');
    let cardReception = $('.cardReception');

    let hotReception = new Handsontable(contarinerReception[0], {
        language: "es-MX",
        readOnly: true,
        height: "650px",
        manualColumnMove: false,
        nestedHeaders: nestedHeaders,
        rowHeaders: false,
        colHeaders: true,
        licenseKey: "non-commercial-and-evaluation",
        manualColumnResize: true,
        manualRowResize: true,
    });

    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    const maxFilas = 200;
    let lastScrollTop = 0;
    let currentPage = 1;
    let cantScrollDown = 0;
    let cantScrollUp = 0;

    function scrollPage() {
        hotReception.addHook(
            "afterScrollVertically",
            debounce(function () {
                const currentScrollTop = hotReception.view._wt.wtOverlays.topOverlay.getScrollPosition();
                const totalRows = hotReception.countRows();

                // Scroll hacia abajo
                if (currentScrollTop > lastScrollTop && hotReception.view._wt.wtTable.getLastVisibleRow() >= totalRows - 1) {
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
                if (currentScrollTop < lastScrollTop && hotReception.view._wt.wtTable.getFirstVisibleRow() === 0 && currentPage > 1) {
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
        $('#formSearchReception :input').not(':button, #codeTechnician-ts-control').each(function() {
            let value = $(this).val();
            if (typeof value === 'string') {
                value = value.trim();
            }
            if(value != ""){
                datosFormulario[$(this).attr('id')] = value;
            }
        });

        let url;
        if(Object.keys(datosFormulario).length === 0){
            url = urlReception
        }else{
            url = urlFilter
        }

        $.get({
            url: url,
            data: {
                pagina: pagina,
                datosFormulario: datosFormulario
            },
            success: function (response) {
                const nuevosDatos = response.data;
                if(nuevosDatos.length > 0){

                    let datosExistentes = hotReception.getData();
    
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
                        datosExistentes = nuevosDatosSinDuplicados.concat(datosExistentes);
    
                        // Limitar a un máximo de maxFilas
                        if (datosExistentes.length > maxFilas) {
                            datosExistentes = datosExistentes.slice(0, maxFilas);
                        }
    
                        hotReception.scrollViewportTo({ row: 124 }); // Ajustar el valor según sea necesario
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
                            const filasAEliminar = datosExistentes.length - maxFilas;
                            datosExistentes = datosExistentes.slice(filasAEliminar);
                        }
                        
                        hotReception.scrollViewportTo({ row: 75 });
                    }
                    
                    hotReception.loadData(datosExistentes);
                }else{

                    if (!esScrollHaciaArriba && currentPage > 1) {
                        currentPage--;
                    }
                    overlay.style.display = 'none';
                }
                overlay.style.display = 'none';
            }
        })
    }

    setTimeout(() => {
        loaderPageReception.show()
    },500)

    $.get({
        url: urlReception,
        success: function (response) {
            cardReception.show()
            loaderPageReception.hide()
            hotReception.loadData(response.data);
            totalResults.text(`Total registros: ${response.totalResults}`)
        }
    })

    // funcion de filtrar
    $(document).on('click', '.btnSearchReception', function(){

        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block';

        let datosFormulario = {};
        $('#formSearchReception :input').not(':button, #codeTechnician-ts-control').each(function() {
            let value = $(this).val()
            if(value != ""){
                datosFormulario[$(this).attr('id')] = value;
            }
        });

        $.get({
            url: urlFilter,
            data: {
                datosFormulario: datosFormulario,
            },
            success: function (response) {

                hotReception.loadData(response.data);
                totalResults.text(`Total registros: ${response.totalResults}`)

                if(response.data.length === 0){
                    Swal.fire({
                        icon: 'warning',
                        title: 'Advertencia',
                        text: 'No se encontraron datos con los filtros seleccionados'
                    })
                    totalResults.text(`Total registros: 0`)
                }
                
                loaderPageReception.hide()
                overlay.style.display = 'none';
            }
        })  
    })

    $(document).on('click', '.btnClearReception', function(){
        $('#formSearchReception :input').not(':button, #codeTechnician-ts-control').each(function() {
            $(this).val('');
        });

        $('#formSearchReception .ts-wrapper').each(function() {
            let tomSelect = $(this).prev()[0].tomselect;
            if(tomSelect) {
                tomSelect.clear();
            }
        });
    })
})