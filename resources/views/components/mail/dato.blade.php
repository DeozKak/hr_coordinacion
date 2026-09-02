@props([
    'tono' => 'info',       // info | exito | error
    'titulo' => null,
])

@php
    $tonos = [
        'info'  => ['tinte' => '#eef4ff', 'borde' => '#bcd3ff', 'texto' => '#1a37b5'],
        'exito' => ['tinte' => '#dcfce7', 'borde' => '#bbf7d0', 'texto' => '#166534'],
        'error' => ['tinte' => '#fee2e2', 'borde' => '#fecaca', 'texto' => '#991b1b'],
    ];
    $t = $tonos[$tono] ?? $tonos['info'];
@endphp

{{-- Recuadro para los detalles de un aviso: el resumen de un proceso, el
     motivo de un fallo. Mismo tinte que las alertas de la aplicación. --}}
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="margin:18px 0; background-color:{{ $t['tinte'] }};
              border:1px solid {{ $t['borde'] }}; border-radius:12px;">
    <tr>
        <td style="padding:16px 18px; font-size:14px; line-height:1.7; color:{{ $t['texto'] }};">
            @if ($titulo)
                <p style="margin:0 0 8px; font-size:12px; font-weight:700;
                          text-transform:uppercase; letter-spacing:0.08em;">{{ $titulo }}</p>
            @endif
            {{ $slot }}
        </td>
    </tr>
</table>
