document.addEventListener('DOMContentLoaded', function() {

    //boton exportar
    const exportar = document.getElementById('exportar')

    exportar.addEventListener('click', function(){
        exportarGDW();
    })


})

function exportarGDW(){
    const url = $('#url_exportar').val();
    $.ajax({
        url: url,
        type: 'POST',
        data: {
            _token: $('#token').val(),
            fecha_inicio: $('#fecha_inicio').val(),
            fecha_fin: $('#fecha_fin').val(),
        },
        success: function(response){
            const mensajeServidor = document.getElementById('mensaje_servidor');
            mensajeServidor.style.display = 'none';
            if(response.url){
                window.location.href = response.url;
            }

        },error: function(xhr, status){
            console.log(xhr.responseText);
            mostrarMensajeError(xhr.responseJSON.error);

        }
    })
}

function mostrarMensajeError(mensaje) {
    const mensajeServidor = document.getElementById('mensaje_servidor');
    mensajeServidor.textContent = mensaje;  // Insertar el mensaje
    mensajeServidor.style.display = 'block';  // Mostrar alerta
}

