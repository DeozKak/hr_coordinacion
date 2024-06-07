<!DOCTYPE html>
<html>
<head>
    <title>Restablecimiento de Contraseña</title>
</head>
<body>
    <p>Hola,</p>

    <p>Has solicitado restablecer tu contraseña. Haz clic en el siguiente enlace para continuar:</p>

    <a href="{{ url('password/reset', $token) }}">Restablecer Contraseña</a>

    <p>Este enlace caducará en 60 minutos.</p>

    <p>Si no solicitaste restablecer tu contraseña, puedes ignorar este correo electrónico.</p>

    <p>Saludos,<br>
       Tu Equipo</p>
</body>
</html>