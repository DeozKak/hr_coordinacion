let hot;
let tableData;
let colHeaders;
let verMasModalInstance = null;
let modalExportar;
let conteoContratos = {};

function actualizarConteoContratos(dataArray) {
    conteoContratos = {};
    const contratoColIndex = colHeaders.indexOf('CONTRATO');

    if (contratoColIndex === -1) return;

    dataArray.forEach(row => {
        const contrato = row[contratoColIndex];
        // Si el contrato existe, le sumamos 1 a su contador
        if (contrato) {
            conteoContratos[contrato] = (conteoContratos[contrato] || 0) + 1;
        }
    });
}
document.addEventListener("DOMContentLoaded",function(){

    const btnOpenExportSup = document.getElementById('openExportarSupervisoresBtn');
    const modalSupEl = new bootstrap.Modal(document.getElementById('exportarSupervisorModal'));
    const selectSup = document.getElementById('selectSupervisor');

    if (btnOpenExportSup) {
        btnOpenExportSup.addEventListener('click', function() {
            modalSupEl.show();

            // Si el select solo tiene la opción de "Cargando", traemos los datos
            if (selectSup.options.length <= 1) {
                const url = document.getElementById('url_get_supervisores').value;

                fetch(url)
                    .then(res => res.json())
                    .then(data => {
                        // Limpiamos el select
                        selectSup.innerHTML = '<option value="">-- Seleccione un Supervisor --</option>';

                        // Llenamos con los datos del servidor
                        data.forEach(sup => {
                            let opt = document.createElement('option');
                            opt.value = sup.name;
                            opt.textContent = sup.name;
                            selectSup.appendChild(opt);
                        });
                    })
                    .catch(err => {
                        console.error("Error cargando supervisores:", err);
                        selectSup.innerHTML = '<option value="">Error al cargar</option>';
                    });
            }
        });
    }

    document.getElementById('btnEjecutarExport').addEventListener('click', function() {
        const supervisor = selectSup.value;
        const btn = this;

        if (!supervisor) {
            alert("Por favor seleccione un supervisor.");
            return;
        }

        const urlExport = document.getElementById('url_export_supervisor_excel').value;
        const token = document.querySelector('input[name="_token"]').value;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        fetch(urlExport, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ supervisor_name: supervisor })
        })
            .then(async response => {
                const data = await response.json();
                if (!response.ok) {
                    throw new Error(data.error || 'Error en el servidor');
                }
                return data;
            })
            .then(data => {
                if (data.downloadUrl) {
                    // No recargamos la página actual, abrimos la descarga en un iframe oculto o link
                    const link = document.createElement('a');
                    link.href = data.downloadUrl;
                    link.download = ''; // El navegador usará el nombre del server
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);

                    modalSupEl.hide();
                }
            })
            .catch(error => {
                // Alerta profesional en caso de error o falta de datos
                console.error('Error:', error);
                alert("Error al exportar: " + error.message);
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-download"></i> Generar Excel';
            });
    });

    // --- Lógica para abrir el modal "Exportar a GDW" ---
    const btnExportarGDW = document.getElementById('openExportarGDWBtn');
    if(!permisoEditar){
        btnExportarGDW.disabled = true;
        document.getElementById('openModalBtn').disabled = true;
        document.getElementById('openHistoricoBtn').disabled = 'none';
    }
    if (btnExportarGDW) {
        btnExportarGDW.addEventListener('click', function() {
            // Obtenemos el elemento HTML del modal
            const exportarModalEl = document.getElementById('exportarGDWModal');

            // Creamos la instancia de Bootstrap Modal
             modalExportar = new bootstrap.Modal(exportarModalEl);

            // Opcional: Limpiamos el formulario antes de abrirlo por si tenía datos viejos
            document.getElementById('formExportarGDW').reset();

            // Mostramos el modal
            modalExportar.show();

            // --- Lógica para el checkbox de Exportar Pendientes ---
            const checkExportarPendientes = document.getElementById('exportar_pendientes');
            const inputFechaExportacion = document.getElementById('fecha_exportacion');

            if (checkExportarPendientes && inputFechaExportacion) {
                checkExportarPendientes.addEventListener('change', function() {
                    if (this.checked) {
                        // Si está marcado: deshabilitamos la fecha y quitamos el 'required'
                        inputFechaExportacion.disabled = true;
                        inputFechaExportacion.required = false;
                        // Opcional: limpiar el valor que tuviera
                        inputFechaExportacion.value = '';
                    } else {
                        // Si se desmarca: volvemos a habilitar y exigimos la fecha
                        inputFechaExportacion.disabled = false;
                        inputFechaExportacion.required = true;
                    }
                });
            }
        });
    }

    // --- Lógica para Exportar a GDW ---
    const formExportar = document.getElementById('formExportarGDW');
    if (formExportar) {
        formExportar.addEventListener('submit', function(e) {
            e.preventDefault();

            const btnSubmit = document.getElementById('btnSubmitExportar');
            const loader = document.getElementById('loaderExportar');
            const url = document.getElementById('url_exportar_gdw').value;
            const formData = new FormData(this);

            btnSubmit.disabled = true;
            loader.style.display = 'block';

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
                .then(response => response.json())
                .then(data => {
                    btnSubmit.disabled = false;
                    loader.style.display = 'none';

                    if(data.success) {
                        // 1. Mostramos la alerta
                        alert(`¡Éxito! Se procesaron ${data.cantidad_encontrada} registros. Descargando archivos...`);

                        // 2. FUNCIÓN CLAVE PARA DESCARGAR
                        const descargarArchivo = (url) => {
                            const link = document.createElement('a');
                            link.href = url;
                            // El atributo download fuerza al navegador a guardar el archivo
                            link.setAttribute('download', '');
                            document.body.appendChild(link);
                            link.click();
                            document.body.removeChild(link);
                        };

                        // 3. Mandamos a descargar el primer archivo usando la URL firmada
                        if (data.url_punto_interes) {
                            descargarArchivo(data.url_punto_interes);
                        }

                        // 4. Esperamos medio segundo y descargamos el segundo archivo
                        setTimeout(() => {
                            if (data.url_tareas) {
                                descargarArchivo(data.url_tareas);
                            }
                        }, 500);

                        // 5. Cerramos el modal

                        if (modalExportar) modalExportar.hide();

                    } else {
                        alert(data.mensaje || 'Hubo un problema al realizar la búsqueda.');
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    btnSubmit.disabled = false;
                    loader.style.display = 'none';
                    alert("Error al intentar procesar la solicitud.");
                });
        });
    }

 document.getElementById('openModalBtn').addEventListener('click',function(){

     const CargarModal = document.getElementById('CargarModal');
     const modalCargar = new bootstrap.Modal(CargarModal);



     modalCargar.show();

     const CargarForm = document.getElementById('CargarOSfForm');
     const errorContainerMasivo = document.createElement('div'); // Contenedor para mensajes de error
     const loaderMasivo = document.getElementById('loader');

     CargarForm.addEventListener('submit', function (event) {

         event.preventDefault();
         loaderMasivo.style.display = 'block'; // Mostrar animación de carga
         // Limpiar mensajes de error anteriores antes de enviar el formulario
         document.getElementById('submit-OSF').disabled = true;

         const formData = new FormData(this);
         const url = document.getElementById('url_import').value; // Ruta Laravel para procesar el formulario

         $.ajax({
             type: 'POST',
             url: url,
             data: formData,
             processData: false,
             contentType: false,
             success: function (response) {
                 document.getElementById('submit-OSF').disabled = false;

                 // Manejo de la respuesta exitosa (opcional)
                 errorContainerMasivo.innerHTML = '';
                 errorContainerMasivo.classList.remove('alert', 'alert-danger');
                 modalCargar.hide();
                 location.reload();
             },
             error: function (xhr, status, error) {
                 document.getElementById('submit-OSF').disabled = false;

                 if (xhr.status === 422) {

                     const errors = xhr.responseJSON.errors;

                     showValidationErrors(errors, CargarModal, errorContainerMasivo); // Mostrar errores en el modal
                 } else {
                     console.error(xhr.responseText); // Mostrar errores en la consola

                 }
             },
             complete: function () {
                 loaderMasivo.style.display = 'none';

                 CargarForm.reset(); // Limpiar el formulario
             }
         });
     });

 })
// --- Lógica para cerrar el modal de "Ver más" por IDs con JS Puro ---
    const btnCerrarTop = document.getElementById('btnCerrarVerMasTop');
    const btnCerrarFooter = document.getElementById('btnCerrarVerMasFooter');

    // Función para ocultar el modal usando la instancia guardada
    const cerrarModalVerMas = function() {
        if (verMasModalInstance) {
            verMasModalInstance.hide();
        }
    };

    // Asignamos el evento click a ambos botones
    if (btnCerrarTop) {
        btnCerrarTop.addEventListener('click', cerrarModalVerMas);
    }
    if (btnCerrarFooter) {
        btnCerrarFooter.addEventListener('click', cerrarModalVerMas);
    }

    InitializeTable();
    iniciarActualizacionAutomatica();
});


function InitializeTable(){


    // --- NUEVO RENDERIZADOR PARA LA COLUMNA CONTRATO ---
    Handsontable.renderers.registerRenderer('contratoRenderer', function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments); // Aplica el texto y formato base

        // Obtenemos los índices dinámicamente
        const recepcionCol = colHeaders.indexOf('RECEPCIÓN');
        const diasFaltantesCol = colHeaders.indexOf('DÍAS RESTANTES');

        const recepcion = instance.getDataAtCell(row, recepcionCol);
        const dias = instance.getDataAtCell(row, diasFaltantesCol);

        // Reseteamos el estilo por defecto antes de aplicar la lógica
        td.style.backgroundColor = '';
        td.style.color = '#000000'; // Aseguramos que el texto normal sea negro
        td.style.fontWeight = 'normal';
        td.title = ''; // Limpiamos tooltips anteriores

        // Reseteamos el color por defecto antes de aplicar la lógica
        td.style.backgroundColor = '';

        // 1. LÓGICA DE CONTRATOS REPETIDOS
        if (value && conteoContratos[value] > 1) {
            td.style.color = '#d32f2f'; // Texto rojo oscuro
            td.style.fontWeight = 'bold'; // Negrita para destacar
            td.title = `¡Atención! Este contrato está repetido ${conteoContratos[value]} veces.`; // Tooltip al pasar el mouse
        }

        // Lógica de colores según tus requerimientos
        if (recepcion === 'ACCEDE' || recepcion === 'NO ACCEDE') {
            td.style.backgroundColor = '#90EE90'; // Verde claro
        }else if(recepcion === 'NO PROCEDENTE'){
            td.style.backgroundColor = '#83b7f1';
        }else if (dias !== null && dias !== '') {
            const diasNum = parseInt(dias, 10);
            if(diasNum === 0){
                td.style.backgroundColor = '#ff9535';
            } else if (diasNum <= 0) {
                td.style.backgroundColor = '#ff8493'; // Rojo claro / Rosado (día 0 o menor)
            } else if (diasNum === 2 || diasNum === 1) {
                td.style.backgroundColor = '#f8f849'; // Amarillo claro
            }
        }
    });

    Handsontable.renderers.registerRenderer('verMasRenderer', function (instance, td, row, col, prop, value, cellProperties) {
        Handsontable.renderers.TextRenderer.apply(this, arguments); // Aplica el comportamiento base

        // Validamos si la celda tiene un contenido largo (ej: más de 40 caracteres)
        if (value && typeof value === 'string' && value.length > 40) {
            td.innerHTML = `
                <div class="cell-content-wrapper">
                    <span class="cell-text" title="${value}">${value}</span>
                    <button class="btn btn-xs ver-mas-btn px-1 text-primary border-0 bg-transparent" style="cursor: pointer;">
                        <i class="fas fa-eye"></i>
                    </button>
                </div>
            `;

            // Agregar evento al botón para mostrar el modal con el texto completo
            const btn = td.querySelector('.ver-mas-btn');
            if (btn) {
                btn.addEventListener('click', function (e) {
                    e.stopPropagation(); // Evitar comportamientos adicionales de la celda al hacer clic en el botón
                    document.getElementById('verMasContent').textContent = value;

                    // Si la instancia aún no existe, la creamos
                    if (!verMasModalInstance) {
                        const verMasModalElement = document.getElementById('verMasModal');
                        verMasModalInstance = new bootstrap.Modal(verMasModalElement);
                    }

                    // Mostramos el modal
                    verMasModalInstance.show();
                });
            }
        }
    });

    const columnsConfig = colHeaders.map((header) => {
        if(!permisoEditar){
            if (header === 'OBSERVACION SUPERVISOR') {
                return {
                    type: 'text',
                    readOnly: false
                };
            }
            return { readOnly: true };
        }
        if(header === 'FECHA SOLICITUD CIERRE'){

            return {
                type: 'date',
                dateFormat: 'YYYY-MM-DD',
                readOnly: false,
            };
        }
        if (header === 'ASIGNADO' || header === 'RESPONSABLE') {
            return {
                type: 'dropdown',
                source: listaInspectores,
                readOnly: false
            };
        }

        if (header === 'SUPERVISOR') {
            return { readOnly: true };
        }

        // Nueva columna RECEPCIÓN con menú desplegable
        if (header === 'RECEPCIÓN') {
            return {
                type: 'dropdown',
                source: ['', 'ACCEDE', 'NO ACCEDE', 'GDW','NO PROCEDENTE'], // Opciones
                readOnly: false
            };
        }

        // Nueva columna OBSERVACIÓN GESTIÓN como texto libre
        if (header === 'OBSERVACIÓN GESTIÓN') {
            return {
                type: 'text',
                readOnly: false
            };
        }

        if (header === 'CÓDIGO AUTORIZACIÓN') {
            return { type: 'numeric', readOnly: false };
        }

        if(header === 'MOTIVO DE PQR'){
            return {
                type: 'dropdown',
                source: [
                    '',
                    'Apelacion',
                    'Atencion brindada',
                    'Cobros ocasionados',
                    'Deja daños',
                    'Demora prestacion servicio',
                    'Error legalizacion',
                    'Inconforme con el proceso',
                    'Incumplimiento cita',
                    'Presentacion personal',
                    'Solicitud de dineros',
                    'No aplica'
                ],
                readOnly: false
            };
        }
        if (header === 'INSTRUCCIONES CAMPO') {
            return {
                type: 'text',
                readOnly: false
            };
        }
        if (header === 'OBSERVACION SUPERVISOR') {
            return {
                type: 'text',
                readOnly: false
            };
        }
        return { readOnly: true }; // El resto son solo lectura
    });
    // Contenedor de la tabla
    const container = document.getElementById('tabla');
    actualizarConteoContratos(tableData);
    if (container && typeof Handsontable !== 'undefined') {
        hot = new Handsontable(container, {
            data: tableData,
            colHeaders: colHeaders,
            columns: columnsConfig, // Añadimos la configuración
            rowHeaders: true,
            readOnly: true, // Esto se anula para las columnas que tienen readOnly: false
            height: "650px",
            width: '100%',
            filters: true,
            columnSorting: {
                initialConfig: {
                    column: colHeaders.indexOf('DÍAS RESTANTES'), // Encuentra la columna automáticamente
                    sortOrder: 'asc' // Orden ascendente (menor a mayor)
                }
            },
            fixedColumnsLeft: 2,
            dropdownMenu: true,
            manualColumnResize: true,
            manualRowResize: true,
            contextMenu: false,
            autoWrapRow: false,
            autoWrapCol: false,
            wordWrap: false,
            colWidths: function(index) {
                const headerName = colHeaders[index];

                // 1. Columnas de personal (nombres completos que necesitan espacio)
                if (headerName === 'ASIGNADO' || headerName === 'RESPONSABLE' || headerName === 'SUPERVISOR') {
                    return 250;
                }

                // 2. Columnas de observaciones (párrafos de texto grandes)
                if (headerName === 'OBSERVACIÓN SOLICITUD' || headerName === 'OBSERVACIÓN GESTIÓN' || headerName === 'OBSERVACION SUPERVISOR' || headerName === 'INSTRUCCIONES CAMPO') {
                    return 300;
                }

                // 3. ¡LA MAGIA! Calculamos el ancho dinámico para que quepa todo el encabezado.
                // Multiplicamos la cantidad de letras por 8 píxeles (tamaño aprox de la letra)
                // y le sumamos 40 píxeles extra para dejar espacio al ícono de la flechita del filtro.
                const anchoCalculado = (headerName.length * 8) + 40;

                // Devolvemos el ancho calculado, pero asegurando que ninguna columna sea menor a 100px
                return Math.max(100, anchoCalculado);
            },

            // --- APLICAR RENDERIZADOR PERSONALIZADO CUIDANDO EL DROPDOWN Y LA FECHA ---
            cells: function (row, col) {
                const headerName = colHeaders[col];

                // Renderizador de color para CONTRATO
                if (headerName === 'CONTRATO') {
                    return { renderer: 'contratoRenderer' };
                }
                const camposExcluidosIcono = [
                    'CONTRATO', 'ASIGNADO', 'RESPONSABLE', 'FECHA ASIGNADO', 'SUPERVISOR',
                    'RECEPCIÓN', 'FECHA RECEPCIÓN',
                    'CÓDIGO AUTORIZACIÓN', 'FECHA RESPUESTA', 'FECHA LÍMITE', 'DÍAS FALTANTES'
                ];

                if (!camposExcluidosIcono.includes(colHeaders[col])) {
                    return { renderer: 'verMasRenderer' };
                }
                return {};
            },

            licenseKey: "non-commercial-and-evaluation",
            afterGetColHeader: function (col, TH) {
                // Configuraciones de fuente por defecto para todas las cabeceras
                TH.style.color = "white";
                TH.style.fontWeight = "bold";

                // Evaluamos el índice de la columna para asignar el color por sección
                if (col >= 0 && col <= 12) {
                    // Verde hasta OBSERVACIÓN SOLICITUD
                    TH.style.backgroundColor = "#5ab15d";
                } else if (col >= 13 && col <= 20) {
                    // Morado claro desde FECHA CIERRE ÚLTIMA hasta TIPO TRABAJO ASIGNACIÓN ÚLTIMA
                    TH.style.backgroundColor = "#BA68C8";
                } else if (col >= 21 && col <= 25) {
                    // Azul Rey (el que ya tenías) desde RESPONSABLE hasta FECHA ASIGNADO
                    TH.style.backgroundColor = "#4F81BD";
                }else if (col >= 26 && col <= 27) {
                    // Cafe Campos supervisores
                    TH.style.backgroundColor = "#595858";
                } else if (col >= 28 && col <= 33) {
                    // Rojo claro desde RECEPCIÓN hasta FECHA RESPUESTA
                    TH.style.backgroundColor = "#ed5e5b";
                } else if (col >= 34 && col <= 35) {
                    // Amarillo desde FECHA LÍMITE hasta DÍAS FALTANTES
                    TH.style.backgroundColor = "#ffa43b";
                    // Cambiamos el texto a negro en esta sección para que contraste mejor con el fondo amarillo
                    TH.style.color = "black";
                }
            },

            afterChange: function (changes, source) {
                if (source === 'loadData' || source === 'programmatic' || !changes) return;

                changes.forEach(([row, prop, oldValue, newValue]) => {
                    const colIndex = typeof prop === 'number' ? prop : hot.propToCol(prop);
                    const headerName = colHeaders[colIndex];

                    // Si cambia alguno de los campos permitidos, disparamos AJAX
                    const camposEditables = ['ASIGNADO', 'RESPONSABLE', 'RECEPCIÓN', 'OBSERVACIÓN GESTIÓN', 'CÓDIGO AUTORIZACIÓN',
                        'MOTIVO DE PQR','FECHA SOLICITUD CIERRE','INSTRUCCIONES CAMPO','OBSERVACION SUPERVISOR'];

                    // Si cambia ASIGNADO o RESPONSABLE, disparamos AJAX
                    if (camposEditables.includes(headerName) && oldValue !== newValue) {
                        const orden = hot.getDataAtCell(row, 0);
                        const contrato = hot.getDataAtCell(row, 1);
                        const url = document.getElementById('url_update_asignado').value;
                        const token = document.querySelector('input[name="_token"]').value;

                        // Mapeamos el nombre del frontend a los campos exactos de BD
                        let campoBD = headerName;
                        if (headerName === 'RECEPCIÓN') campoBD = 'RECEPCION';
                        if (headerName === 'OBSERVACIÓN GESTIÓN') campoBD = 'OBSERVACION_GESTION';
                        if (headerName === 'CÓDIGO AUTORIZACIÓN') campoBD = 'CODIGO_AUTORIZACION';
                        if (headerName === 'MOTIVO DE PQR') campoBD = 'MOTIVO_DE_PQR';
                        if (headerName === 'FECHA SOLICITUD CIERRE') campoBD = 'FECHA_SOLICITUD_CIERRE';
                        if(headerName === 'INSTRUCCIONES CAMPO') campoBD = 'INSTRUCCIONES_CAMPO';
                        if(headerName === 'OBSERVACION SUPERVISOR') campoBD = 'OBSERVACION_SUPERVISOR';


                        $.ajax({
                            url: url,
                            type: 'POST',
                            data: {
                                _token: token,
                                orden: orden,
                                contrato: contrato,
                                campo: campoBD,
                                valor: newValue
                            },
                            success: function (response) {
                                console.log(`${headerName} guardado con éxito.`);

                                let batchChanges = [];

                                if (headerName === 'ASIGNADO') {
                                    const fechaColIndex = colHeaders.indexOf('FECHA ASIGNADO');
                                    const supervisorColIndex = colHeaders.indexOf('SUPERVISOR');
                                    batchChanges.push([row, fechaColIndex, response.fecha_asignado || null]);
                                    batchChanges.push([row, supervisorColIndex, response.supervisor || null]);
                                }

                                // Si editó Recepción (GDW)
                                if (headerName === 'RECEPCIÓN') {
                                    const fechaRecepcionColIndex = colHeaders.indexOf('FECHA RECEPCIÓN');
                                    batchChanges.push([row, fechaRecepcionColIndex, response.fecha_extra || null]);
                                }

                                // Si editó Código de Autorización
                                if (headerName === 'CÓDIGO AUTORIZACIÓN') {
                                    const fechaRespuestaColIndex = colHeaders.indexOf('FECHA RESPUESTA');
                                    batchChanges.push([row, fechaRespuestaColIndex, response.fecha_extra || null]);
                                }

                                if (batchChanges.length > 0) {
                                    hot.setDataAtCell(batchChanges, 'programmatic');
                                }
                            },
                            error: function (xhr) {

                                let errorMsg = 'Error guardando datos.';
                                if (xhr.responseJSON) {
                                    errorMsg = xhr.responseJSON.error || errorMsg;


                                        hot.setDataAtCell(row, colIndex, oldValue, 'programmatic');

                                        if (headerName === 'ASIGNADO' && !oldValue) {
                                            hot.setDataAtCell([
                                                [row, colHeaders.indexOf('FECHA ASIGNADO'), null],
                                                [row, colHeaders.indexOf('SUPERVISOR'), null]
                                            ], 'programmatic');
                                        }

                                        if (headerName === 'RECEPCIÓN' && !oldValue) {
                                            hot.setDataAtCell(row, colHeaders.indexOf('FECHA RECEPCIÓN'), null, 'programmatic');
                                        }

                                        if (headerName === 'CÓDIGO AUTORIZACIÓN' && !oldValue) {
                                            hot.setDataAtCell(row, colHeaders.indexOf('FECHA RESPUESTA'), null, 'programmatic');
                                        }

                                }

                                console.error(errorMsg);

                                if (typeof Swal !== 'undefined') {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error de Validación',
                                        text: errorMsg,
                                        toast: true,
                                        position: 'top-end',
                                        showConfirmButton: false,
                                        timer: 3000
                                    });
                                }
                            }
                        });
                    }
                });
            }
        });
    } else {
        console.error('El contenedor para Handsontable no fue encontrado o la librería no está cargada.');
    }


}

function iniciarActualizacionAutomatica() {
    const url = document.getElementById('url_get_datos_actualizados').value;

    // Se ejecutará cada 60000 milisegundos (1 minuto)
    setInterval(() => {
        // TRUCO: Si la tabla no está lista o el usuario está editando una celda (dropdown abierto o escribiendo),
        // cancelamos la actualización en este ciclo para no interrumpirlo.
        if (!hot || (hot.getActiveEditor() && hot.getActiveEditor().isOpened())) {
            return;
        }

        fetch(url)
            .then(response => response.json())
            .then(result => {
                if(result.data) {
                    // Mapeamos los datos exactamente con el mismo orden que en Blade
                    const newData = result.data.map(row => [
                        row.NUMERO_ORDEN, row.CONTRATO, row.CEDULA, row.NOMBRE,
                        row.DESC_DEPART, row.DESC_LOCALIDAD, row.BARRIO, row.DIRECCION,
                        row.DESC_CATEGORIA, row.COD_UNIDAD_OPER, row.DESC_TIPO_TRABAJO,
                        row.FECHA_ASIGNACION, row.OBSERVACION_SOLICITUD, row.FECHA_CIERRE_ULTIMA,
                        row.OBSERVACIÓN_CIERRE_ULTIMA, row.TIPO_TRABAJO_CIERRE_ULTIMA,
                        row.DESC_CAUSAL_CIERRE_ULTIMA, row.FECHA_ASIGNACIÓN_ULTIMA,
                        row.OBSERVACIÓN_ASIGNACIÓN_ULTIMA, row.GESTIÓN_ASIGNACIÓN_ULTIMA,
                        row.TIPO_TRABAJO_ASIGNACIÓN_ULTIMA, row.MOTIVO_DE_PQR, row.RESPONSABLE,
                        row.ASIGNADO, row.SUPERVISOR, row.FECHA_ASIGNADO,row.INSTRUCCIONES_CAMPO,
                        row.OBSERVACION_SUPERVISOR,row.RECEPCION,
                        row.FECHA_RECEPCION, row.FECHA_SOLICITUD_CIERRE, row.OBSERVACION_GESTION,
                        row.CODIGO_AUTORIZACION, row.FECHA_RESPUESTA, row.FECHA_LIMITE,
                        row.DIAS_FALTANTES,
                    ]);

                    // 1. Obtenemos los plugins
                    const sortPlugin = hot.getPlugin('columnSorting');
                    const filtersPlugin = hot.getPlugin('filters');

                    // 2. Guardamos el estado ACTUAL (Orden y Filtros)
                    const currentSortConfig = sortPlugin.getSortConfig();
                    const currentFilters = filtersPlugin.conditionCollection.exportAllConditions();

                    hot.loadData(newData);

                    // 4. Restauramos los Filtros primero
                    if (currentFilters && currentFilters.length > 0) {
                        filtersPlugin.conditionCollection.importAllConditions(currentFilters);
                        filtersPlugin.filter(); // Le decimos a la tabla que aplique el filtro
                    }

                    // 5. Restauramos el Ordenamiento después
                    if (currentSortConfig && currentSortConfig.length > 0) {
                        sortPlugin.sort(currentSortConfig);
                    } else {
                        // Si no había orden del usuario, forzamos tu orden por defecto
                        const diasFaltantesCol = colHeaders.indexOf('DÍAS FALTANTES');
                        sortPlugin.sort({ column: diasFaltantesCol, sortOrder: 'asc' });
                    }


                }
            })
            .catch(error => console.error('Error actualizando la tabla en segundo plano:', error));
    }, 60000); // <-- Cambia a 30000 si quieres que sea cada medio minuto
}

function showValidationErrors(errors, addmodal, errorContainer) {
    errorContainer.innerHTML = ''; // Limpiar mensajes anteriores
    errorContainer.classList.add('alert-modern', 'alert-danger-modern');


    if (typeof errors === 'string') {
        // Si es una cadena, muestra directamente
        errorContainer.textContent = errors;
    } else {
        // Si es un objeto, muestra cada mensaje en una línea
        for (const field in errors) {
            const errorMessages = errors[field];
            for (const message of errorMessages) {
                const errorItem = document.createElement('li');
                errorItem.textContent = message;
                errorContainer.appendChild(errorItem);
            }
        }
    }

    // Agregar el contenedor de errores al modal
    const modalBody = addmodal.querySelector('.modal-body');
    modalBody.prepend(errorContainer);
}
