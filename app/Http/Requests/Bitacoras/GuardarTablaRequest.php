<?php

namespace App\Http\Requests\Bitacoras;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Cierre de la bitácora en curso.
 *
 * Ya no lleva cuerpo. Antes viajaba aquí la tabla entera del navegador
 * —encabezado, filas y desplegables— y con eso se armaba tanto el Excel como
 * los registros definitivos; ahora el servidor toma ambas cosas del borrador
 * de autoguardado, que es la única versión que se ha ido guardando sola.
 *
 * La clase se conserva porque la ruta está exenta de CSRF en
 * `bootstrap/app.php` y conviene que siga habiendo un punto donde declarar qué
 * se acepta si algún día vuelve a recibir algo.
 */
class GuardarTablaRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission:generar_bitacoras en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [];
    }
}
