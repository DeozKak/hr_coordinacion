$(document).ready(function(){

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
    let falseTrue = $('#nextScroll');

    function scrollPage() {
        hotReception.addHook(
            "afterScrollVertically",
            debounce(function () {
                // let value = buscador.val();
                // let tipe = columnasBuscar.val();
                const currentScrollTop = hotReception.view._wt.wtOverlays.topOverlay.getScrollPosition();
                const totalRows = hotReception.countRows();
                // let ruta = "";

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

    function cargarPagina(pagina, esScrollHaciaArriba, valor, tipo) {
        const overlay = document.getElementById('overlay');
        overlay.style.display = 'block'; // Mostrar el overlay
        $.get({
            url: url,
            data: {
                pagina: pagina,
            },
            success: function (response) {
                const nuevosDatos = response.data;
                console.log(nuevosDatos)
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
        url: url,
        success: function (response) {
            // console.log(response)
            cardReception.show()
            loaderPageReception.hide()
            hotReception.loadData(response.data);
        }
    })
})