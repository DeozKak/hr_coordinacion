<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?php echo($user)?> Generó tabla <?php echo($archivo->nombre)?></title>
</head>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; margin: 0; padding: 0; text-align: center;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #ffffff; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); margin: 40px auto; padding: 20px; border: 1px solid #e1e1e1;">

                <!-- Logo -->
                <tr>
                    <td align="center" style="padding-bottom: 20px;">
                        <img src="<?php echo(asset('vendor/adminlte/dist/img/logo_EYC_2.png'))?>" alt="Logo Empresa" width="250" style="display: block; border-bottom: 2px solid #007bff; padding-bottom: 10px;">
                    </td>
                </tr>

                <!-- Línea divisoria -->
                <tr>
                    <td style="border-top: 1px solid #e1e1e1; padding-top: 20px;"></td>
                </tr>

                <!-- Encabezado -->
                <tr>
                    <td style="background: linear-gradient(135deg, #007bff, #0056b3); color: white; padding: 17px; border-radius: 8px 8px 0 0; font-size: 19px; font-weight: bold;">
                        <?php echo($user) ?> generó la tabla <?php echo($archivo->nombre) ?>
                    </td>
                </tr>

                <!-- Cuerpo del correo -->
                <tr>
                    <td style="padding: 25px; text-align: left; color: #333; font-size: 16px; line-height: 1.6;">
                        <p style="margin-bottom: 15px;">
                            Se ha actualizado la tabla de agendamiento.
                        </p>

                        <p style="text-align: left; margin: 25px 0;">
                            <a href="<?php echo (route('programacion.show',['id'=>$archivo->id]).'?action=view') ?>" style="display: inline-block; background-color: #007bff; color: white; padding: 13px 23px; text-decoration: none; border-radius: 6px; font-weight: bold; font-size: 16px;">
                                Más detalles
                            </a>
                        </p>
                    </td>
                </tr>

                <!-- Pie de página -->
                <tr>
                    <td style="background-color: #f8f9fa; padding: 15px; font-size: 13px; color: #666; text-align: center; border-radius: 0 0 8px 8px;">
                        <p style="margin: 5px 0;">&copy; <?php echo date('Y'); ?> E&C Ingeniería | Todos los derechos reservados.</p>
                        <p style="margin: 0;">Este es un correo generado automáticamente, por favor no responder.</p>
                    </td>
                </tr>

            </table>
        </td>
    </tr>
</table>
</body>
</html>
