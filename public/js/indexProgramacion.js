document.addEventListener('DOMContentLoaded', () => {

    const openModalBtn = document.getElementById('openModalBtn');
    const addProgramacionModal = document.getElementById('addProgramacionModal');
    const modal = new bootstrap.Modal(addProgramacionModal);

    const closeModalBtn = document.querySelector('.btn-close'); // Selecciona el botón de cierre
    
    closeModalBtn.addEventListener('click', function () {
      modal.hide(); // Cierra el modal
    });

    openModalBtn.addEventListener('click', function () {
        modal.show();
    });

    const programacionForm = document.getElementById('programacionForm');
    const errorContainer = document.createElement('div'); // Contenedor para mensajes de error

    programacionForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const url = document.getElementById('url_base').value; // Ruta Laravel para procesar el formulario
        const csrfToken = document.getElementById('token').value

        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                // Manejo de la respuesta exitosa (opcional)
                console.log(response);
                modal.hide();
            },
            error: function (xhr, status, error) {
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors); // Mostrar errores en el modal
                } else {
                    console.error('Error:', error);
                }
            }
        });
    });
    function showValidationErrors(errors) {
      errorContainer.innerHTML = ''; // Limpiar mensajes anteriores
      errorContainer.classList.add('alert', 'alert-danger'); // Clase para estilos

      for (const field in errors) {
          const errorMessages = errors[field];
          for (const message of errorMessages) {
              const errorItem = document.createElement('li');
              errorItem.textContent = message;
              errorContainer.appendChild(errorItem);
          }
      }

      // Agregar el contenedor de errores al modal (puedes personalizar la ubicación)
      const modalBody = addProgramacionModal.querySelector('.modal-body');
      modalBody.prepend(errorContainer); // Agregar al inicio del body del modal
  }

});