<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Contrato <?php echo($contrato)?> Gestionado</title>
    <style>
        body {
            font-family: sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #eee;
        }
        h1 {
            color: #333;
        }
        .button {
            display: inline-block;
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Contrato <?php echo($contrato) ?> Gestionado</h1>
       
        <p>
            El usuario <?php echo ($user) ?> ha gestionado una devolución en el contrato <?php echo ($contrato) ?>.
        </p>
        <p>
            Este correo corresponde al reporte: <?php echo $archivo->nombre_archivo ?>
        </p>
        <p>
            <a href="<?php echo(route('bitacoras.ver_reporte', ['id_bitacora' => $bitacora])) ?>" class="button">Ver detalles</a>
        </p>
        <p>
            La información se ha actualizado en Producción y en la bitácora.
        </p>
    </div>
</body>
</html>