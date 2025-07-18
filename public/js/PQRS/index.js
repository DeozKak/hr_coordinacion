
let hot;
document.addEventListener('DOMContentLoaded', function() {

    const table = document.getElementById('table');


    hot = new Handsontable(table, {
        data: [],
        rowHeaders: true,
        columns: [
            { data: 'id', readOnly: true },
            { data: 'fecha' },
            { data: 'hora' },
            { data: 'observaciones' },
            { data: 'tecnico' },
            { data: 'celular' },
        ],
        licenseKey: 'non-commercial-and-evaluation',
    });


    $('#formulario-archivo').on('submit', function(e) {
        e.preventDefault(); // Evita el envío por defecto

        let formData = new FormData(this);

        $.ajax({
            url: '/ruta/a/tu/controlador', // Cambia esto por tu ruta real, por ejemplo: '/pqrs-import'
            type: 'POST',
            data: formData,
            contentType: false, // Requerido para FormData
            processData: false, // Requerido para FormData
            headers: {
                'X-CSRF-TOKEN': $('input[name="_token"]').val() // Solo si tu CSRF es input hidden
            },
            success: function(response) {
                $('#mensaje-programaciones').html(
                    '<div class="alert alert-success">Archivo subido exitosamente.</div>'
                );
            },
            error: function(xhr) {
                $('#mensaje-programaciones').html(
                    '<div class="alert alert-danger">Ocurrió un error al subir el archivo.</div>'
                );
            }
        });
    });

});
