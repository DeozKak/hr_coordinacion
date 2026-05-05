let data;
document.addEventListener('DOMContentLoaded', () => {
    if (warning !== '') {
        Swal.fire({
            title: "Advertencia",
            text: warning + ' desea asignar?',
            icon: "warning",
            showCancelButton: true,
            confirmButtonColor: "#3085d6",
            cancelButtonColor: "#d33",
            confirmButtonText: "Si",
            cancelButtonText: "No"
        }).then((result) => {
            if (result.isConfirmed) {
                // Evento para abrir el modal y generar los selectores
                consultas();
                setTimeout(() => {
                    generarSelectores();
                }, 1000)


            }
        });
    }
});

async function consultas() {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: 'zonas/datosAsignador',
            type: 'GET',
            success: function (response) {
                data = response;
                resolve(response);
            }, error(xhr, status) {
                //console.log(xhr.responseText);
                reject(xhr.responseJSON.error);
            }
        });
    });
}

function alertaMunicipio(){
    Swal.fire({
        title: "Aviso",
        text: 'Desea asignar Grupo al municipios creado?, tenga en cuenta que si no se asigna ' +
            'no se podrá asignar ordenes de trabajo a esta zona.',
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Si",
        cancelButtonText: "No"
    }).then((result) => {
        if (result.isConfirmed) {
            consultas();
            setTimeout(() => {
                generarSelectores();
            }, 1000);
        }

    });
}
