<x-mail.marco tono="error"
              saludo="{{ $user }} devolvió un contrato"
              titulo="Contrato {{ $contrato }} en devolución">

    <p style="margin:0 0 14px;">
        El contrato <strong>{{ $contrato }}</strong> pasó a estado de devolución.
    </p>

    <x-mail.dato tono="error" titulo="Motivo">
        {{ $causal }}
    </x-mail.dato>

    <p style="margin:0;">
        Reporte asociado: <strong>{{ $archivo->nombre_archivo }}</strong>.
    </p>
</x-mail.marco>
