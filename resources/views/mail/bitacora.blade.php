<x-mail.marco tono="info"
              saludo="{{ $user }} generó un reporte"
              titulo="Reporte {{ $archivo->nombre_archivo }} generado"
              :enlace="route('bitacoras.ver_reporte', ['id_bitacora' => $bitacora])"
              enlaceTexto="Ver el reporte">

    <p style="margin:0;">
        El reporte ya está disponible y la información se actualizó en
        <strong>Producción</strong>.
    </p>
</x-mail.marco>
