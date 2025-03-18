document.addEventListener('DOMContentLoaded', function () {
    setTimeout(function () {
        let notificationId = 0;

        // Manejar clic en el ícono de eliminar
        $(document).on('click', '[id^="notificationTrash_"]', function (event) {
            event.preventDefault();
            event.stopPropagation();

            notificationId = this.id.split('_')[1];
            marcarComoLeida(notificationId);
        });

        // Manejar clic en "Ver más"
        $(document).on('click', 'a.text-muted.text-sm', function (event) {
            notificationId = $(this).closest('.dropdown-item').attr('id'); // Obtiene el ID de la notificación
            if (notificationId) {
                event.preventDefault(); // Evita la navegación inmediata
                marcarComoLeida(notificationId, () => {
                    window.location.href = $(this).attr('href'); // Redirige después de eliminar
                });
            }
        });

    }, 800);


    function marcarComoLeida(notificationId, callback = null) {
        $.ajax({
            url: Mark_notification,
            type: "GET",
            data: { notification_id: notificationId },
            success: function (response) {
                if (response.success) {
                    $('#' + notificationId).fadeOut(300, function () {
                        $(this).remove(); // Elimina la notificación del DOM
                    });

                    // Actualiza el contador de notificaciones
                    let badge = $('.navbar-badge');
                    let count = parseInt(badge.text()) || 0;
                    if (count > 1) {
                        badge.text(count - 1);
                    } else {
                        badge.text('');
                    }

                    // Ejecuta el callback si existe (redirigir al enlace)
                    if (typeof callback === "function") {
                        callback();
                    }
                } else {
                    console.error("Error:", response.message);
                }
            },
            error: function (xhr, status, error) {
                console.error(xhr.responseText);
            }
        });
    }

    const makeRead = document.getElementById('makeRead').value;
    setTimeout(() => {
        document.addEventListener('click', function (event) {
            let dropdownLink = null;
            if (event.target.matches('a.deleteNotification')) {
                dropdownLink = event.target;
            }

            if (dropdownLink) {
                const notificationId = dropdownLink.id;

                const requestUrl = `${makeRead}?notification_id=${notificationId}`;

                $.ajax({
                    url: requestUrl,
                    type: 'GET',
                    success: function (response) {

                    },
                    error: function (xhr, status, error) {

                    }
                });
            }
        });
    }, 10);

    $(document).ready(function () {
        $('body').on('shown.bs.dropdown', '.nav-item.dropdown', function () {
            $.ajax({
                url: Mark_all_notification,
                type: "GET",
                success: function (response) {
                    if (response.success) {
                        $('.navbar-badge').text(''); // Ocultar el número de notificaciones
                    } else {
                        console.error("Error:", response.message);
                    }
                },
                error: function (xhr, status, error) {
                    console.error(xhr.responseText);
                }
            });
        });
    });

    $(document).on('click', '.adminlte-dropdown-content', function (event) {
        event.stopPropagation();
    });
});
