<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de Tiempos de Quejas</title>
</head>
<body style="font-family: sans-serif; line-height: 1.6; margin: 0; padding: 0; background-color: #f4f4f4;">
<table width="100%" cellpadding="0" cellspacing="0" border="0">
    <tr>
        <td align="center">
            <table width="600" cellpadding="0" cellspacing="0" border="0"
                   style="background-color: #fff; border: 1px solid rgba(0,0,0,.125); border-radius: .25rem; box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075); margin: 20px auto; padding: 20px;">
                <tr>
                    <td style="background-color: #f0f0f0; padding: 10px; border-bottom: 1px solid rgba(0,0,0,.125);">
                        <h2 style="color: #333;">Reporte de Tiempos de Quejas</h2>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p>Se ha generado el siguiente reporte automático con las quejas que presentan más de 3 días pendientes o que han sido recepcionadas recientemente en el sistema GDW:</p>
                        <table width="100%" cellpadding="5" cellspacing="0" border="1"
                               style="border-collapse: collapse; margin-top: 15px;">
                            <thead style="background-color: #eee;">
                            <tr>
                                <th>CONTRATO</th>
                                <th>LOCALIDAD</th>
                                <th>BARRIO</th>
                                <th>DIRECCIÓN</th>
                                <th>DIAS FALTANTES</th>
                                <th>INSPECTOR</th>
                                <th>SUPERVISOR</th>
                                <th>RECEPCIÓN</th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($quejas as $queja): ?>
                                <?php
                                // Elige el color de fondo según los días
                                $styleFila = '';
                                if ($queja->RECEPCION == 'GDW') {
                                    $styleFila = 'background-color: #dbeafe;'; // Azul (Prioridad Alta)

// 2. Si NO es GDW, entonces miramos los días
                                } elseif ($queja->DIAS_FALTANTES <= 1) {
                                    $styleFila = 'background-color: #ff8080;'; // Rojo (Prioridad Media)

                                } elseif ($queja->DIAS_FALTANTES == 2 || $queja->DIAS_FALTANTES == 3) {
                                    $styleFila = 'background-color: #ffe066;'; // Amarillo (Prioridad Baja)
                                }
                                ?>
                                <tr style="<?php echo $styleFila; ?>">
                                    <td><?php echo $queja->CONTRATO; ?></td>
                                    <td><?php echo $queja->DESC_LOCALIDAD; ?></td>
                                    <td><?php echo $queja->BARRIO; ?></td>
                                    <td><?php echo $queja->DIRECCION; ?></td>
                                    <td><?php echo $queja->DIAS_FALTANTES; ?></td>
                                    <td><?php echo $queja->ASIGNADO; ?></td>
                                    <td><?php echo $queja->SUPERVISOR; ?></td>
                                    <td><?php echo $queja->RECEPCION; ?></td>
                                </tr>

                            <?php endforeach; ?>
                            </tbody>
                        </table>
                        <p style="margin-top: 20px;">Por favor, revise y gestione según corresponda.</p>

                        <p style="text-align: left; margin: 25px 0;">
                            <a href="<?php echo (route('pqrs.index')) ?>"
                               class="button"
                               style="display: inline-block; background-color: #007bff;
                                          color: white; padding: 13px 23px; text-decoration: none;
                                          border-radius: 6px; font-weight: bold; font-size: 16px;
                                          transition: background 0.3s;">
                                Más detalles
                            </a>
                        </p>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
</body>
</html>
