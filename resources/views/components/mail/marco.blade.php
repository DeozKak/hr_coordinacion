@props([
    'tono' => 'info',           // info | exito | error
    'titulo',
    'saludo' => null,
    'enlace' => null,
    'enlaceTexto' => 'Ver detalles',
])

@php
    /* Paleta de la aplicación, escrita en hexadecimal porque en el correo no
       hay hoja de estilos ni variables: todo va en línea. Los valores son los
       mismos tokens de resources/css/app.css. */
    $tonos = [
        'info'  => ['acento' => '#1f47e0', 'tinte' => '#eef4ff', 'borde' => '#bcd3ff', 'texto' => '#1a37b5'],
        'exito' => ['acento' => '#15803d', 'tinte' => '#dcfce7', 'borde' => '#bbf7d0', 'texto' => '#166534'],
        'error' => ['acento' => '#b91c1c', 'tinte' => '#fee2e2', 'borde' => '#fecaca', 'texto' => '#991b1b'],
    ];
    $t = $tonos[$tono] ?? $tonos['info'];

    $lienzo  = '#f6f7fb';   // canvas
    $tinta   = '#0f172a';   // slate-900
    $cuerpo  = '#334155';   // slate-700
    $apagado = '#64748b';   // slate-500
    $linea   = '#e2e8f0';   // slate-200
    $pie     = '#f8fafc';   // slate-50

    $fuente = "'Plus Jakarta Sans', 'Segoe UI', Roboto, Helvetica, Arial, sans-serif";
@endphp

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo }}</title>
</head>

{{-- Maquetado con tablas y estilos en línea: es lo único que interpretan igual
     todos los clientes de correo. Sin flex, sin grid y sin hoja aparte. --}}
<body style="margin:0; padding:0; background-color:{{ $lienzo }};
             font-family:{{ $fuente }}; -webkit-font-smoothing:antialiased;">

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0"
       style="background-color:{{ $lienzo }};">
    <tr>
        <td align="center" style="padding:32px 16px;">

            <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0"
                   style="width:600px; max-width:100%; background-color:#ffffff;
                          border:1px solid {{ $linea }}; border-radius:16px; overflow:hidden;">

                {{-- Filo de color: dice de un vistazo si el correo trae buenas
                     o malas noticias, sin gritar con una banda entera. --}}
                <tr><td style="height:4px; line-height:4px; font-size:0;
                               background-color:{{ $t['acento'] }};">&nbsp;</td></tr>

                {{-- Marca --}}
                <tr>
                    <td align="center" style="padding:28px 32px 20px;">
                        <img src="{{ asset('img/logo-ec.png') }}" alt="E&amp;C Ingeniería SAS"
                             width="180" style="display:block; width:180px; max-width:60%; height:auto; border:0;">
                    </td>
                </tr>

                <tr><td style="padding:0 32px;">
                    <div style="height:1px; line-height:1px; font-size:0;
                                background-color:{{ $linea }};">&nbsp;</div>
                </td></tr>

                {{-- Título --}}
                <tr>
                    <td style="padding:26px 32px 0;">
                        @if ($saludo)
                            <p style="margin:0 0 6px; font-size:14px; color:{{ $apagado }};">{{ $saludo }}</p>
                        @endif
                        <h1 style="margin:0; font-size:21px; line-height:1.3; font-weight:700;
                                   letter-spacing:-0.01em; color:{{ $tinta }};">{{ $titulo }}</h1>
                    </td>
                </tr>

                {{-- Cuerpo --}}
                <tr>
                    <td style="padding:16px 32px 4px; font-size:15px; line-height:1.65; color:{{ $cuerpo }};">
                        {{ $slot }}
                    </td>
                </tr>

                @if ($enlace)
                    <tr>
                        <td style="padding:22px 32px 4px;">
                            {{-- El botón es una tabla y no un <a> con relleno:
                                 Outlook ignora el padding de los enlaces. --}}
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0">
                                <tr>
                                    <td align="center"
                                        style="background-color:{{ $t['acento'] }}; border-radius:12px;">
                                        <a href="{{ $enlace }}"
                                           style="display:inline-block; padding:13px 26px; font-size:15px;
                                                  font-weight:700; color:#ffffff; text-decoration:none;">
                                            {{ $enlaceTexto }}
                                        </a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                @endif

                <tr><td style="height:28px; line-height:28px; font-size:0;">&nbsp;</td></tr>

                {{-- Pie --}}
                <tr>
                    <td style="background-color:{{ $pie }}; border-top:1px solid {{ $linea }};
                               padding:18px 32px; text-align:center; font-size:12px;
                               line-height:1.6; color:{{ $apagado }};">
                        &copy; {{ date('Y') }} E&amp;C Ingeniería SAS · Seguimiento Operación<br>
                        Correo generado automáticamente, por favor no responder.
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
