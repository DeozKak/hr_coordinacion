document.addEventListener("DOMContentLoaded", function () {

    const boton = document.getElementById('btn-masivo');


    boton.addEventListener('click', function (event) {

        $('#MasivaModal').modal('show');

        document.getElementById('btn-procesar').addEventListener('click', function () {
            document.getElementById('btn-procesar').disabled = true;
            document.getElementById('cargando_masiva').style.display = 'block';
            enviarDatos()
        })

    });

});


function enviarDatos() {

    const input = document.getElementById('archivo_masivo');
    const archivo = input.files[0];

    const formData = new FormData();
    formData.append('archivo', archivo);
    formData.append('_token', document.getElementById('token').value);

    $.ajax({
        url:  document.getElementById('url_masivo').value,
        type: 'POST',
        data: formData,
        processData: false,
        contentType: false,
        success: function (response) {

            if(response.success){
                Swal.fire({
                    title: '¡Listo!',
                    text: response.success,
                    icon: 'success',
                    timer: 1500,
                    showConfirmButton: false
                });
                document.getElementById('btn-procesar').disabled = false;
                document.getElementById('cargando_masiva').style.display = 'none';
                $('#MasivaModal').modal('hide');
                input.value = '';
            }
            let message = $('#messageMasivas');
            message.css('display', 'none');
        }, error: function (xhr) {
            console.log(xhr.responseText);
            document.getElementById('btn-procesar').disabled = false;
            document.getElementById('cargando_masiva').style.display = 'none';
            let message = $('#messageMasivas');
            message.css('display', 'block');
            message.addClass('alert alert-danger');
            message.html(xhr.responseJSON.error);
        }
    });
}
