let data;
document.addEventListener('DOMContentLoaded', () => {

    if(warning !== ''){
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

            }
        });
    }

    function consultas(){
        $.ajax({
            url: 'zonas/datosAsignador',
            type: 'GET',
            success:function(response){
               data = response;
                generarSelectores();
            },error(xhr, status){
                console.log(xhr.responseText);
            }
        })

    }

});
