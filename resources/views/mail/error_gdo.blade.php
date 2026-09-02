<x-mail.marco tono="error"
              saludo="Hola, {{ $userName }}"
              titulo="La Programación GDO no se pudo procesar">

    <p style="margin:0 0 14px;">
        El proceso falló inesperadamente y el archivo no llegó a cargarse.
    </p>

    <x-mail.dato tono="error" titulo="Detalle del error">
        <strong>Registro / fila del Excel:</strong> {{ $rowNumber }}<br>
        {{ $errorMessage }}
    </x-mail.dato>

    <p style="margin:0;">
        Revisa el archivo en la fila indicada e inténtalo de nuevo. Si el error
        persiste, contacta al administrador del sistema.
    </p>
</x-mail.marco>
