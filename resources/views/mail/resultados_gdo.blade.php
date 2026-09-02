<x-mail.marco tono="exito"
              saludo="Hola, {{ $userName }}"
              titulo="Tu Programación GDO ya está procesada">

    <p style="margin:0 0 14px;">
        El archivo que subiste se procesó en segundo plano. Se analizó la
        información y se extrajeron las fechas de agendamiento.
    </p>

    <p style="margin:0;">
        Adjunto encontrarás el Excel con el detalle de las operaciones
        programadas, los errores encontrados y sus justificaciones.
    </p>
</x-mail.marco>
