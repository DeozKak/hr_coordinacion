document.addEventListener('DOMContentLoaded', () => {

    $('#programacion').DataTable({
        scrollX: false,
        scrollY: true,
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
        },
         // Aquí agregamos la propiedad 'order'
        "order": [[ 3, "desc" ]] 
    });



    // codigo de modal masivo
    const openMasivo = document.getElementById('openMasivoBtn');
    const addMasivoModal = document.getElementById('addMasivoModal');
    const modalMasivo = new bootstrap.Modal(addMasivoModal);

    const closeModalMasivoBtn = document.querySelector('.masivoModal'); // Selecciona el botón de cierre
  
    closeModalMasivoBtn.addEventListener('click', function () {
        modalMasivo.hide();
    });

    openMasivo.addEventListener('click', function () {
        modalMasivo.show();
    });

    const masivoform = document.getElementById('masivoForm');
    const errorContainerMasivo = document.createElement('div'); // Contenedor para mensajes de error
    const loaderMasivo = document.getElementById('loaderMasivo');

    masivoform.addEventListener('submit', function (event) {

        event.preventDefault();
        loaderMasivo.style.display = 'block'; // Mostrar animación de carga
        // Limpiar mensajes de error anteriores antes de enviar el formulario
        document.getElementById('submit-masivo').disabled = true;
        
        const formData = new FormData(this);
        const url = document.getElementById('url_masivo').value; // Ruta Laravel para procesar el formulario


        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
            document.getElementById('submit-masivo').disabled = false;

                // Manejo de la respuesta exitosa (opcional)
                errorContainerMasivo.innerHTML = '';
                errorContainerMasivo.classList.remove('alert', 'alert-danger');
                modalMasivo.hide();
                location.reload(); 
            },
            error: function (xhr, status, error) {
                document.getElementById('submit-masivo').disabled = false;

                if (xhr.status === 422) {
                   
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors,addMasivoModal,errorContainerMasivo); // Mostrar errores en el modal
                } else {
                    console.error(xhr.responseText); // Mostrar errores en la consola

                }
            },
            complete: function () {
                loaderMasivo.style.display = 'none';
               
                masivoform.reset(); // Limpiar el formulario
            }
        });
    });

    // cofigo de modal base
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
       document.getElementById('submit-programacion').disabled = true;

        const formData = new FormData(this);
        const url = document.getElementById('url_base').value; // Ruta Laravel para procesar el formulario


        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                document.getElementById('submit-programacion').disabled = false;

                // Manejo de la respuesta exitosa (opcional)
                Swal.fire({
                    position: "top-end",
                    icon: "success",
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
                document.getElementById('submit-programacion').disabled = false;

                console.log(xhr.responseText);
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors,addProgramacionModal,errorContainer); // Mostrar errores en el modal
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
    
    const openGDOBtn = document.getElementById('openGDOBtn');
    const addGDOModal = document.getElementById('addGDO');
    const GDO = new bootstrap.Modal(addGDOModal);

    const closeModalGDO = document.querySelector('.GDO'); // Selecciona el botón de cierre

    closeModalGDO.addEventListener('click', function () {
        GDO.hide();
    });

    openGDOBtn.addEventListener('click', function () {
        GDO.show();
    });

    const GDOform = document.getElementById('GDOForm');
    const errorContainerGDO = document.createElement('div'); // Contenedor para mensajes de error
    const loaderGDO = document.getElementById('loaderGDO');

    GDOform.addEventListener('submit', function (event) {
        event.preventDefault();
        loaderGDO.style.display = 'block'; // Mostrar animación de carga
        // Limpiar mensajes de error anteriores antes de enviar el formulario
        document.getElementById('submit-GDO').disabled = true;

        const formData = new FormData(this);
        const url = document.getElementById('url_GDO').value; // Ruta Laravel para procesar el formulario


        $.ajax({
            type: 'POST',
            url: url,
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
            document.getElementById('submit-GDO').disabled = false;

                // Manejo de la respuesta exitosa (opcional)
                Swal.fire({
                    position: "top-end",
                    icon: "success",
                    title: response.message,
                    showConfirmButton: false,
                    toast: true,
                    timer: 4000
                });
                errorContainerGDO.innerHTML = '';
                errorContainerGDO.classList.remove('alert', 'alert-danger');
                GDO.hide();
            },
            error: function (xhr, status, error) {
                document.getElementById('submit-GDO').disabled = false;
                if (xhr.status === 422) {
                    const errors = xhr.responseJSON.errors;
                    showValidationErrors(errors,addGDOModal,errorContainerGDO); // Mostrar errores en el modal
                } else {
                    console.error(xhr.responseText); // Mostrar errores en la consola

                }
            },
            complete: function () {
                loaderGDO.style.display = 'none';
               
                GDOform.reset(); // Limpiar el formulario
            }
        });
    });




    function showValidationErrors(errors,addmodal,errorContainer) {
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
        const modalBody = addmodal.querySelector('.modal-body');
        modalBody.prepend(errorContainer);
    }


    $('#buscadorContrato').on('input', function() {
        let contrato = $(this).val();
        buscarBitacorasPorContrato(contrato);
    });

    function buscarBitacorasPorContrato(contrato) {
        if (contrato.trim() === '') {
            $('#resultadosBusqueda').empty(); // Limpiar la lista de resultados
            return; // Salir de la función si está vacío
        }
        const url = document.getElementById('url_buscar').value; // Ruta Laravel para buscar bitácoras
        $.ajax({
            url: url, // Ruta a tu nueva función en el controlador
            type: 'GET',
            data: {
                contrato: contrato
            },
            success: function(response) {
                
                let listaHtml = '<ul>';
                response.forEach(programadas => {
                    listaHtml += `<li data-id="${programadas.id}">${programadas.nombre} ${programadas.usuario} (ID: ${programadas.id})</li>`;
                });
                listaHtml += '</ul>';
                $('#resultadosBusqueda').html(listaHtml);

                // Evento de clic para cada elemento de la lista
                $('.lista-resultados li').click(function() {
                    const idProgramacion = $(this).data('id');
                    let elementoUrl = document.getElementById('url_ver'); 
                    let url = elementoUrl.value.replace(':id', idProgramacion); 
                    window.location.href = url;
                });
            },
            error: function(xhr, status, error) {
                console.log(xhr.responseText);
            }
        });
    }

    
});