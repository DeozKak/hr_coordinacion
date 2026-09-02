<x-mail.marco tono="error"
              saludo="Hola, {{ $nombreUsuario }}"
              titulo="La macro no se pudo procesar">

    <p style="margin:0 0 14px;">
        Se encontró un error al procesar <strong>{{ $nombreArchivo }}</strong> y
        el proceso no llegó a completarse.
    </p>

    <x-mail.dato tono="error" titulo="Detalle del error">
        <strong>Mensaje:</strong> {{ $error['mensaje'] }}<br>
        <strong>Fila aproximada:</strong> {{ $error['fila'] }}
    </x-mail.dato>

    <p style="margin:0;">
        Revisa el archivo en esa fila, corrige el problema y vuelve a subirlo.
        Si el error persiste, contacta a soporte técnico.
    </p>
</x-mail.marco>
