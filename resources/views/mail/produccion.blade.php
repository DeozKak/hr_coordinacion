<x-mail.marco tono="info"
              saludo="{{ $user }} agregó un contrato"
              titulo="Contrato {{ $contrato }} en producción"
              :enlace="route('produccion.detalles')"
              enlaceTexto="Ver en producción">

    <p style="margin:0 0 14px;">
        Se agregó el contrato <strong>{{ $contrato }}</strong> a producción el
        <strong>{{ $fecha }}</strong>.
    </p>

    <x-mail.dato titulo="Inspector asignado">
        {{ $inspector->apellidos }} {{ $inspector->nombres }}
    </x-mail.dato>
</x-mail.marco>
