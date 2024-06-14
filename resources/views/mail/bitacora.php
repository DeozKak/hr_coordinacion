<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <title>Reporte <?php echo ($archivo->nombre_archivo) ?>| Generado</title>
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

        .shadow-container { /* Estilos para la sombra */
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.2); 
            margin: 20px auto;
            max-width: 1250px;
            border-radius: 10px;
            background-color: #f8f9fac4;
        }
    </style>
</head>

<body>
    <div class="container shadow-container"> <h1>Reporte <?php echo ($archivo->nombre_archivo) ?>| Generado</h1>
        <p>Estimado/a,</p>
        <p>
            El usuario <strong><?php echo ($user) ?></strong> ha generado el reporte <strong><?php echo $archivo->nombre_archivo ?></strong>.
        </p>

        <p>
            <a href="<?php echo (route('bitacoras.ver_reporte', ['id_bitacora' => $bitacora])) ?>" class="button">Ver Reporte</a>
        </p>
        <p>
            La información se ha actualizado en <strong>Producción</strong>
        </p>
    </div>
</body>

</html>
