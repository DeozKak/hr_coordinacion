document.addEventListener('DOMContentLoaded', () => {

    $('#programacion').DataTable({
        "language": {
            "lengthMenu": "Mostrar _MENU_ registros por página",
            "zeroRecords": "Nada encontrado - lo siento",
            "info": "Mostrando la página _PAGE_ de _PAGES_",
            "infoEmpty": "No hay registros disponibles",
            "infoFiltered": "(Filtrado de _MAX_ registros totales)",
            "search": "Buscar:",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            }
        }
    });

    const openModalBtn = document.getElementById('openModalBtn');
    const addProgramacionModal = document.getElementById('addProgramacionModal');
    const modal = new bootstrap.Modal(addProgramacionModal);

    const closeModalBtn = document.querySelector('.btn-close'); // Selecciona el botón de cierre

    closeModalBtn.addEventListener('click', function () {
        modal.hide();
    });

    openModalBtn.addEventListener('click', function () {
        modal.show();
    });

    const programacionForm = document.getElementById('programacionForm');
    const errorContainer = document.createElement('div'); // Contenedor para mensajes de error
    const loader = document.getElementById('loader');
    programacionForm.addEventListener('submit', function (event) {
       
      
        event.preventDefault();
        loader.style.display = 'block'; // Mostrar animación de carga
        // Limpiar mensajes de error anteriores antes de enviar el formulario
       

        const formData = new FormData(this);
        const url = document.getElementById('url_base').value; // Ruta Laravel para procesar el formulario


        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Manejo de la respuesta exitosa (opcional)
                Swal.fire({
                    position: "top-end",
                    type: "success",
                    title: response.message,
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });
                errorContainer.innerHTML = '';
                errorContainer.classList.remove('alert', 'alert-danger');
                modal.hide();
            },
            error: function (xhr, status, error) {
                if (xhr.status === 422) {
                   
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors); // Mostrar errores en el modal
                } else {
                    console.error(xhr.responseText); // Mostrar errores en la consola

                }
            },
            complete: function () {
                loader.style.display = 'none';
               
                programacionForm.reset(); // Limpiar el formulario
            }
        });
    });
    function showValidationErrors(errors) {
        errorContainer.innerHTML = ''; // Limpiar mensajes anteriores
        errorContainer.classList.add('alert', 'alert-danger');

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
        const modalBody = addProgramacionModal.querySelector('.modal-body');
        modalBody.prepend(errorContainer);
    }
});