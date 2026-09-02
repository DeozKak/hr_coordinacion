<x-mail.marco tono="exito"
              saludo="Hola, {{ $nombreUsuario }}"
              titulo="La macro se procesó correctamente">

    <p style="margin:0 0 14px;">
        La macro <strong>{{ $nombreArchivo }}</strong> que subiste terminó de
        procesarse sin incidencias.
    </p>

    <x-mail.dato tono="exito" titulo="Resumen del proceso">
        <strong>Registros procesados:</strong> {{ $stats['totalProcesados'] }}<br>
        <strong>Duración:</strong> {{ $stats['duracion'] }} segundos
    </x-mail.dato>
</x-mail.marco>
