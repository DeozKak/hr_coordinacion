document.addEventListener('DOMContentLoaded', function () {

    const boton = document.getElementById('btnProcesar');


    boton.addEventListener('click', function (event) {

        enviarDatos();

    });

});

function enviarDatos() {
    // Obtener el archivo y otros campos
    const archivoInput = document.getElementById('archivo_diaria');
    const archivo = archivoInput.files[0]; // Obtén el archivo seleccionado (tipo File)

    // Crear un objeto FormData
    const formData = new FormData();
    formData.append('archivo', archivo); // Agrega el archivo al FormData
    formData.append('_token', document.getElementById('token').value); // Agrega el token CSRF

    $.ajax({
        url: document.getElementById('url_diaria').value,
        type: 'POST',
        data: formData,
        contentType: false, // Importante: Desactivar el tipo de contenido predeterminado
        processData: false, // Importante: Evita convertir el FormData en una cadena query
        success: function (response) {
            let message = $('#message');
            message.css('display', 'none');
            console.log(response);
        }, error: function (xhr, status) {
            console.log(xhr.responseText);
            let message = $('#message');
            message.css('display', 'block');
            message.addClass('alert alert-danger');
            message.html(xhr.responseJSON.error);
        }
    })
}
