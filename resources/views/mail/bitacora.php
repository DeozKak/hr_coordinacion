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
            background-color: #f4f4f4; /* Fondo similar a Bootstrap */
        }

        .card { /* Estilos de tarjeta */
            background-color: #fff;
            border: 1px solid rgba(0,0,0,.125);
            border-radius: .25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0,0,0,.075);
            max-width: 600px;
            margin: 20px auto;
            padding: 20px;
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

        .card-header { /* Estilos del encabezado */
            background-color: #f0f0f0;
            padding: 10px;
            border-bottom: 1px solid rgba(0,0,0,.125);
        }
    </style>
</head>
<body>
    <div class="card">
        <div class="card-header">
            <h1>Reporte <?php echo ($archivo->nombre_archivo) ?>| Generado</h1>
        </div>

        <div class="card-body">
          
            <p>
                <strong><?php echo ($user) ?></strong> ha generado el reporte <strong><?php echo $archivo->nombre_archivo ?></strong>.
            </p>

            <p>
                <a href="<?php echo (route('bitacoras.ver_reporte', ['id_bitacora' => $bitacora])) ?>" class="button">Ver Reporte</a>
            </p>
            <p>
                La información se ha actualizado en <strong>Producción</strong>
            </p>
        </div>
    </div>
</body>
</html>