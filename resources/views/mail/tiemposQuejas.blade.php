@php
    /* Prioridad de cada fila. GDW manda sobre los días: una queja recién
       recepcionada ahí se atiende primero aunque le queden días. Los tintes
       son los de las alertas de la aplicación. */
    $prioridad = function ($queja) {
        if ($queja->RECEPCION === 'GDW')   return ['#eef4ff', '#bcd3ff', '#1a37b5', 'Recepción GDW'];
        if ($queja->DIAS_FALTANTES <= 1)   return ['#fee2e2', '#fecaca', '#991b1b', 'Vence hoy o vencida'];
        if ($queja->DIAS_FALTANTES <= 3)   return ['#fef3c7', '#fde68a', '#92400e', 'Quedan 2 o 3 días'];
        return ['#ffffff', '#e2e8f0', '#334155', null];
    };

    $columnas = ['Contrato', 'Localidad', 'Barrio', 'Dirección', 'Días', 'Inspector', 'Supervisor', 'Recepción'];

    /* Leyenda: se arma con los mismos valores, así no puede desalinearse de
       los colores de la tabla. */
    $leyenda = [
        ['#eef4ff', '#bcd3ff', '#1a37b5', 'Recepción GDW'],
        ['#fee2e2', '#fecaca', '#991b1b', 'Vence hoy o vencida'],
        ['#fef3c7', '#fde68a', '#92400e', 'Quedan 2 o 3 días'],
    ];
@endphp

<x-mail.marco tono="info"
              saludo="Reporte automático"
              titulo="Quejas pendientes de gestión"
              :enlace="route('pqrs.coordinacion')"
              enlaceTexto="Abrir coordinación PQRS">

    <p style="margin:0 0 4px;">
        Quejas con más de tres días pendientes o recepcionadas recientemente en
        el sistema GDW. Son <strong>{{ count($quejas) }}</strong>.
    </p>

    {{-- Leyenda de colores --}}
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" style="margin:16px 0 6px;">
        <tr>
            @foreach ($leyenda as [$fondo, $borde, $texto, $rotulo])
                <td style="padding:0 14px 0 0; font-size:12px; color:#64748b; white-space:nowrap;">
                    <span style="display:inline-block; width:10px; height:10px; border-radius:3px;
                                 background-color:{{ $fondo }}; border:1px solid {{ $borde }};"></span>
                    {{ $rotulo }}
                </td>
            @endforeach
        </tr>
    </table>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
           style="border-collapse:collapse; margin:10px 0 4px; font-size:12px;">
        <thead>
            <tr>
                @foreach ($columnas as $columna)
                    <th align="left"
                        style="padding:9px 8px; background-color:#f8fafc; border-bottom:1px solid #e2e8f0;
                               font-size:10px; font-weight:700; text-transform:uppercase;
                               letter-spacing:0.06em; color:#64748b; white-space:nowrap;">{{ $columna }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach ($quejas as $queja)
                @php [$fondo, $borde, $texto] = $prioridad($queja); @endphp
                <tr style="background-color:{{ $fondo }};">
                    @foreach ([$queja->CONTRATO, $queja->DESC_LOCALIDAD, $queja->BARRIO, $queja->DIRECCION,
                               $queja->DIAS_FALTANTES, $queja->ASIGNADO, $queja->SUPERVISOR,
                               $queja->RECEPCION] as $valor)
                        <td style="padding:9px 8px; border-bottom:1px solid {{ $borde }};
                                   color:{{ $texto }}; vertical-align:top;">{{ $valor }}</td>
                    @endforeach
                </tr>
            @endforeach

            @if (! count($quejas))
                <tr>
                    <td colspan="{{ count($columnas) }}"
                        style="padding:26px 8px; text-align:center; color:#64748b;
                               border-bottom:1px solid #e2e8f0;">
                        No hay quejas pendientes.
                    </td>
                </tr>
            @endif
        </tbody>
    </table>
</x-mail.marco>
