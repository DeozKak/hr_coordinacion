<x-mail.marco tono="info"
              saludo="{{ $user }} gestionó una devolución"
              titulo="Contrato {{ $contrato }} gestionado"
              :enlace="route('bitacoras.ver_reporte', ['id_bitacora' => $bitacora])"
              enlaceTexto="Ver el reporte">

    <p style="margin:0 0 14px;">
        Se gestionó una devolución en el contrato <strong>{{ $contrato }}</strong>.
    </p>

    <x-mail.dato titulo="Reporte">
        {{ $archivo->nombre_archivo }}
    </x-mail.dato>
</x-mail.marco>
