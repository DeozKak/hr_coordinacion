<x-mail.marco tono="info"
              saludo="{{ $user }} generó una tabla"
              titulo="Se actualizó la tabla {{ $archivo->nombre }}"
              :enlace="route('programacion.show', ['id' => $archivo->id]) . '?action=view'"
              enlaceTexto="Ver la programación">

    <p style="margin:0;">
        La tabla de agendamiento ya tiene los datos nuevos.
    </p>
</x-mail.marco>
