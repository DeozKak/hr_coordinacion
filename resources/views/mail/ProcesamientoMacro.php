<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Proceso Finalizado</title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 20px;">
<table width="100%" border="0" cellspacing="0" cellpadding="0">
    <tr>
        <td align="center">
            <table width="600" border="0" cellspacing="0" cellpadding="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 4px 10px rgba(0,0,0,0.1);">
                <tr>
                    <td align="center" style="padding: 20px;">
                        <img src="<?php asset('vendor/adminlte/dist/img/logo_EYC_2.png') ?>" alt="Logo E&C Ingeniería" width="200">
                    </td>
                </tr>
                <tr>
                    <td align="center" style="background-color: #28a745; color: #ffffff; padding: 20px; font-size: 24px; font-weight: bold;">
                        Proceso Completado Exitosamente
                    </td>
                </tr>

                <tr>
                    <td style="padding: 30px 25px; color: #333; font-size: 16px; line-height: 1.6;">
                        <p>Hola <strong><?php echo( $nombreUsuario )?></strong>,</p>
                        <p>Te informamos que la macro <strong><?php echo( $nombreArchivo)?></strong> que subiste ha sido procesado correctamente.</p>

                        <table width="100%" border="0" cellspacing="0" cellpadding="0" style="background-color: #f8f9fa; border-radius: 5px; margin-top: 20px; padding: 15px; border: 1px solid #dee2e6;">
                            <tr>
                                <td style="font-size: 16px; color: #495057;">
                                    <strong>Resumen del Proceso:</strong>
                                    <ul style="list-style-type: none; padding-left: 0; margin-top: 10px;">
                                        <li style="margin-bottom: 8px;"><strong>✓ Registros Procesados:</strong> <?php echo( $stats['totalProcesados']) ?></li>
                                        <li style="margin-bottom: 8px;"><strong>⏱️ Duración:</strong> <?php echo( $stats['duracion']) ?> segundos</li>
                                    </ul>
                                </td>
                            </tr>
                        </table>

                    </td>
                </tr>

                <tr>
                    <td style="background-color: #e9ecef; padding: 20px; text-align: center; font-size: 12px; color: #6c757d;">
                        <p style="margin: 0;">&copy; <?php echo( date('Y')) ?> E&C Ingeniería. Todos los derechos reservados.</p>
                        <p style="margin: 5px 0 0;">Este es un correo generado automáticamente, por favor no responder.</p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
