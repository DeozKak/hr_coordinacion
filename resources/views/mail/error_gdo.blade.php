<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Error en Procesamiento</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; text-align: center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 10px; margin: 40px auto; padding: 20px; border: 1px solid #ffcccc;">
                <tr>
                    <td align="center" style="padding-bottom: 20px;">
                        <img src="{{ asset('vendor/adminlte/dist/img/logo_EYC_2.png') }}" alt="Logo Empresa" width="250" style="display: block; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">
                    </td>
                </tr>
                <tr>
                    <td style="color: #dc3545; padding: 17px; font-size: 19px; font-weight: bold;">
                        ¡Hola, {{ $userName }}!
                    </td>
                </tr>
                <tr>
                    <td style="padding: 25px; text-align: left; color: #333; font-size: 16px; line-height: 1.6;">
                        <p>Lamentamos informarte que el proceso de <strong>Programación GDO</strong> ha fallado inesperadamente.</p>

                        <p style="background-color: #fff3f3; border-left: 5px solid #dc3545; padding: 15px; color: #a94442;">
                            <strong>📍 Registro / Fila de Excel:</strong> {{ $rowNumber }}<br><br>
                            <strong>❌ Detalle del error:</strong><br>
                            {{ $errorMessage }}
                        </p>

                        <p>Por favor, revisa el archivo de Excel en la fila indicada e intenta subirlo nuevamente. Si el error persiste, contacta al administrador del sistema.</p>
                    </td>
                </tr>
                <tr>
                    <td style="background-color: #f8f9fa; padding: 15px; font-size: 13px; color: #666; text-align: center;">
                        <p>&copy; {{ date('Y') }} E&C Ingeniería | Notificación de Sistema</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
