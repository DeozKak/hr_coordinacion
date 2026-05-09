
const url_plantilla = document.getElementById('url_plantilla').value;
const columns = ['id', 'CONTRATO', 'TIPO_TRABAJO', 'FECHA', 'CELULAR', 'NOMBRE_USUARIO', 'ORDEN_TRABAJO', 'DIRECCION', 'BARRIO', 'CIUDAD', 'ACTIVA',
    'SUSPENDIDO', 'CATEGORIA', 'FECHA_AGENDAMIENTO', 'OBSERVACIONES', 'PORQUE_PROGRAMO', 'TECNICO', 'JORNADA'
];
// Esperamos a que el documento esté completamente cargado
document.addEventListener('DOMContentLoaded', function () {
    // Obtenemos el botón "Agregar"
    const agregarButton = document.getElementById('agregar');
    const NOMBRE_USUARIO = document.getElementById('NOMBRE_USUARIO');
    const CONTRATOInput = document.getElementById('CONTRATO');
    const CELULARInput = document.getElementById('CELULAR');
    const ORDEN_TRABAJO = document.getElementById('ORDEN_TRABAJO');
    const fechaInput = document.getElementById('FECHA');
    const PORQUE_PROGRAMO = document.getElementById('PORQUE_PROGRAMO');
    const fechaAgendamientoInput = document.getElementById('FECHA_AGENDAMIENTO');
    PORQUE_PROGRAMO.value = user.name;



    // Obtener la fecha actual en formato YYYY-MM-DD
    const hoy = new Date();
    const year = hoy.getFullYear();
    const month = (hoy.getMonth() + 1).toString().padStart(2, '0');
    const day = hoy.getDate().toString().padStart(2, '0');
    const fechaFormateada = `${year}-${month}-${day}`; // Formato correcto para input type="date"

    // Asignar la fecha actual al input
    fechaInput.value = fechaFormateada;
    hoy.setDate(hoy.getDate() + 1);
    const year2 = hoy.getFullYear();
    const month2 = (hoy.getMonth() + 1).toString().padStart(2, '0');
    const day2 = hoy.getDate().toString().padStart(2, '0');
    const fechaFormateada2 = `${year2}-${month2}-${day2}`;
    // Asignar la fecha de mañana como valor mínimo del input
    fechaAgendamientoInput.min = fechaFormateada2;

    NOMBRE_USUARIO.addEventListener('input', function () {
        this.value = this.value.replace(/[^a-zA-Z ]/g, '').slice(0, 50);
    });
    CONTRATOInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 15);
    });

    CELULARInput.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9 ]/g, '').slice(0, 21);
    });

    ORDEN_TRABAJO.addEventListener('input', function () {
        this.value = this.value.replace(/[^0-9]/g, '').slice(0, 18);
    });

    // Adjuntamos un event listener al clic del botón
    agregarButton.addEventListener('click', function () {
        // Creamos un objeto para almacenar los datos del formulario
        const formData = {};

        // Capturamos los valores de los campos de entrada de texto

        formData.CONTRATO = document.getElementById('CONTRATO').value;
        formData.CELULAR = document.getElementById('CELULAR').value;
        formData.NOMBRE_USUARIO = document.getElementById('NOMBRE_USUARIO').value;

        if (document.getElementById('ORDEN_TRABAJO').value == '') {
            document.getElementById('ORDEN_TRABAJO').value = 'N/A';
            formData.ORDEN_TRABAJO = 'N/A';
        } else {
            formData.ORDEN_TRABAJO = document.getElementById('ORDEN_TRABAJO').value;
        }

        formData.DIRECCION = document.getElementById('DIRECCION').value;
        formData.BARRIO = document.getElementById('BARRIO').value;
        formData.OBSERVACIONES = document.getElementById('OBSERVACIONES').value;
        formData.PORQUE_PROGRAMO = document.getElementById('PORQUE_PROGRAMO').value;

        // Capturamos el valor del select para el tipo de trabajo
        formData.TIPO_TRABAJO = document.getElementById('TIPO_TRABAJO').value;

        // Capturamos el valor de la fecha
        formData.FECHA = document.getElementById('FECHA').value;

        // Capturamos el valor del select para el municipio
        formData.CIUDAD = document.getElementById('CIUDAD').value;

        // Capturamos el estado (activo o suspendido)
        if (document.querySelector('input[name="estado"]:checked').value === 'activo') {
            formData.ACTIVA = 'Si';
            formData.SUSPENDIDO = 'No';
        } else {
            formData.ACTIVA = 'No';
            formData.SUSPENDIDO = 'Si';
        }

        // Capturamos el valor del select para la categoría
        formData.CATEGORIA = document.getElementById('CATEGORIA').value;

        // Capturamos el valor de la fecha de agendamiento
        formData.FECHA_AGENDAMIENTO = document.getElementById('FECHA_AGENDAMIENTO').value;

        // Capturamos el valor del select para el inspector
        formData.TECNICO = document.getElementById('TECNICO').value;

        // Capturamos las horas de inicio y fin
        formData.JORNADA = document.getElementById('JORNADA').value;


        // Validación de campos obligatorios
        const camposObligatorios = ['CONTRATO', 'CELULAR', 'NOMBRE_USUARIO', 'DIRECCION', 'BARRIO', 'TIPO_TRABAJO', 'CIUDAD', 'FECHA_AGENDAMIENTO', 'CATEGORIA', 'OBSERVACIONES', 'TECNICO', 'JORNADA'];

        let hayCamposVacios = false; // Variable para controlar si hay campos vacíos

        for (const campo of camposObligatorios) {
            const elementoCampo = document.getElementById(campo); // Obtenemos el elemento del campo

            if (!formData[campo]) {

                elementoCampo.classList.add('campo-invalido'); // Añadimos la clase 'error' para resaltar en rojo
                hayCamposVacios = true;
            } else {
                elementoCampo.classList.remove('campo-invalido'); // Removemos la clase si el campo se llena posteriormente
            }
        }

        if (hayCamposVacios) {
            Swal.fire({
                position: "top-end",
                icon: "warning",
                title: "Por favor complete todos los campos",
                showConfirmButton: false,
                toast: true,
                timer: 4000
            });
            return; // Detenemos el envío del formulario
        }

        // Si todos los campos están completos, puedes continuar con el envío del formulario
        enviarServidor(formData);
    });


    function enviarServidor(data) {

        const token = document.getElementById('token').value;
        $.ajax({
            url: url_plantilla,
            type: 'POST',
            data: {
                _token: token,
                data: data,
                tabla: tabla_id
            },
            success: function (response) {
                $('#addPlantilla').modal('hide');
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: "Registro exitoso",
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });
                data.id = response.id;
                let lastRowWithData = H_tabla.countRows() - 1;

                // Iterar desde la última fila hacia arriba hasta encontrar una fila con datos
                while (lastRowWithData >= 0) {
                    let rowData = H_tabla.getDataAtRow(lastRowWithData);

                    // Verificar si la fila tiene datos (puedes ajustar esta condición según tus necesidades)
                    if (rowData.some(cell => cell !== null && cell !== '')) {
                        break; // Salir del bucle cuando se encuentra una fila con datos
                    }

                    lastRowWithData--;
                }

                // Obtener el número total de filas existentes
                let rowCount = H_tabla.countRows();

                // Insertar una nueva fila al final
                H_tabla.alter('insert_row_above', rowCount);

                // Obtener las propiedades (columnas) de tu Handsontable
                const columnProperties = H_tabla.getColHeader();

                // Crear un array de cambios para setDataAtRowProp
                const changes = columnProperties.map((prop, index) => {
                    return [lastRowWithData + 1, columns[index], data[columns[index]]];
                });

                // Establecer los datos en la nueva fila
                H_tabla.setDataAtRowProp(changes);
                limpiarCampos();

            }, error: function (xhr, status, error) {
                Swal.fire({
                    position: "top-end",
                    icon: "error",
                    title: "Error al registrar",
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });
                console.error("Error en la petición AJAX:");
                console.log("Código de estado:", xhr.status);
                console.log("Descripción:", xhr.statusText);
                console.log("Respuesta del servidor:", xhr.responseText);
            }
        });

    }

    function limpiarCampos() {
        const columnsB = ['CONTRATO', 'CELULAR', 'NOMBRE_USUARIO', 'ORDEN_TRABAJO', 'DIRECCION', 'BARRIO'
            , 'OBSERVACIONES', 'HORA_INICIO', 'HORA_FINAL', 'FECHA_AGENDAMIENTO'
        ];
        columnsB.forEach(id => document.getElementById(id).value = '');
        const columnsS = ['TIPO_TRABAJO', 'CIUDAD', 'CATEGORIA', 'TECNICO']
        columnsS.forEach(id => document.getElementById(id).selectedIndex = 0);
        $('#CIUDAD').val('').trigger('change');
    }
});
