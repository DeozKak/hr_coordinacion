<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Resultados Procesados</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; text-align: center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin: 40px auto; padding: 20px; border: 1px solid #e1e1e1;">

                <tr>
                    <td align="center" style="padding-bottom: 20px;">
                        <img src="{{ asset('vendor/adminlte/dist/img/logo_EYC_2.png') }}" alt="Logo Empresa" width="250" style="display: block; border-bottom: 2px solid #007bff; padding-bottom: 10px;">
                    </td>
                </tr>

                <tr>
                    <td style="color: #333; padding: 17px; border-radius: 8px 8px 0 0; font-size: 19px; font-weight: bold;">
                        ¡Hola, {{ $userName }}!
                    </td>
                </tr>

                <tr>
                    <td style="padding: 25px; text-align: left; color: #333; font-size: 16px; line-height: 1.6;">
                        <p style="margin-bottom: 15px;">
                            El archivo de <strong>Programación GDO</strong> que subiste ha sido procesado exitosamente por nuestro sistema en segundo plano.
                        </p>
                        <p style="margin-bottom: 15px;">
                            Se ha analizado la información y extraído las fechas de agendamiento. Adjunto a este correo encontrarás el archivo Excel resultante con el detalle de las operaciones programadas, los errores encontrados y las justificaciones.
                        </p>
                    </td>
                </tr>

                <tr>
                    <td style="background-color: #f8f9fa; padding: 15px; font-size: 13px; color: #666; text-align: center; border-radius: 0 0 8px 8px;">
                        <p style="margin: 5px 0;">&copy; {{ date('Y') }} E&C Ingeniería | Todos los derechos reservados.</p>
                        <p style="margin: 0;">Este es un correo generado automáticamente, por favor no responder.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
