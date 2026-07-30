let FechaInicial;
let boton;
let checkboxRango; // Creamos una variable para capturar el checkbox

document.addEventListener('DOMContentLoaded', function () {
    FechaInicial = document.getElementById('fechaInicio');
    boton = document.getElementById('btnAsignar');
    checkboxRango = document.getElementById('rangoFechas'); // Capturamos el checkbox del DOM

    boton.addEventListener('click', function (){

        // 1. Validamos si el usuario seleccionó la opción de rango de fechas
        if (checkboxRango && checkboxRango.checked) {
            alert('La reasignación solo está disponible para una fecha específica. Por favor, desmarca la opción "Seleccionar un rango de fechas".');
            return; // Esta instrucción detiene la ejecución, evitando que se llame a EnviarFechas
        }

        // 2. Validamos que el usuario efectivamente haya seleccionado una fecha inicial
        if (!FechaInicial.value) {
            alert('Por favor, selecciona una Fecha Inicial antes de asignar.');
            return; // Detenemos la ejecución si está vacío
        }

        // Si pasa las validaciones anteriores, ejecutamos la función normalmente
        EnviarFechas(FechaInicial.value);
    });
});


function EnviarFechas(fecha) {
    let url = document.getElementById('urlAsignar').value;
    let urlFinal = url.replace(':fecha', fecha);

    // Usamos la API fetch en lugar de $.ajax para manejar archivos binarios
    fetch(urlFinal, {
        method: 'GET'
    })
        .then(response => {
            // 1. Verificamos si la respuesta del servidor NO fue exitosa (ej. 404 o 500)
            if (!response.ok) {
                // Convertimos la respuesta a JSON para poder leer tu mensaje de error
                return response.json().then(errorData => {
                    throw errorData;
                });
            }
            // 2. Si todo está bien (200 OK), convertimos la respuesta en un archivo Blob
            return response.blob();
        })
        .then(blob => {
            // 3. Magia para descargar el archivo: creamos una URL temporal en memoria
            const urlBlob = window.URL.createObjectURL(blob);

            // Creamos un elemento <a> (enlace) invisible
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = urlBlob;

            // Asignamos el nombre con el que se descargará el archivo
            a.download = 'Reasignacion_' + fecha + '.xlsx';

            // Lo agregamos al documento, hacemos clic y lo eliminamos
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);

            // Limpiamos la URL de la memoria para no consumir recursos
            window.URL.revokeObjectURL(urlBlob);
        })
        .catch(error => {
            // 4. Aquí atrapamos el error 404 que envías desde el controlador
            console.error("Error en la descarga:", error);

            // Mostramos el mensaje que mandaste desde Laravel ('No hay programaciones para esta fecha.')
            if (error.mensaje) {
                alert(error.mensaje);
            } else {
                alert("Ocurrió un error inesperado al intentar descargar el archivo.");
            }
        });
}
