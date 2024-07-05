<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato <?php echo($contrato)?> Devuelto</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" border="0">
        <tr>
            <td align="center">
                <table class="card" width="600" cellpadding="0" cellspacing="0" border="0" style="background-color: #fff; border: 1px solid rgba(0,0,0,.125); border-radius: .25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); margin: 20px auto; padding: 20px;">
                    <tr>
                        <td class="card-header" style="background-color: #f0f0f0; padding: 10px; border-bottom: 1px solid rgba(0,0,0,.125);">
                            <h1 style="color: #333;">Contrato <?php echo($contrato)?> Devuelto</h1>
                        </td>
                    </tr>
                    <tr>
                        <td class="card-body">
                            <p style="line-height: 1.6;">Estimado/a,</p>
                            <p style="line-height: 1.6;">
                                El usuario <strong><?php echo ($user) ?></strong> ha pasado el contrato <strong><?php echo ($contrato) ?></strong> a estado de devolución.
                            </p>
                            <p style="line-height: 1.6;">
                                Se ha devuelto el contrato asociado al reporte: <strong><?php echo $archivo->nombre_archivo ?></strong>
                            </p>
                            <p style="line-height: 1.6;">
                                <strong>Observaciones:</strong> <?php echo $causal ?>
                            </p>
                           
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
