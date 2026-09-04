<?php

namespace App\Http\Requests\Bitacoras;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Volcado de las tablas de bitácora a Excel.
 *
 * Este endpoint está exento de CSRF en `bootstrap/app.php`, así que conviene
 * que sea especialmente estricto con lo que acepta. El permiso
 * `generar_bitacoras` ya limita quién entra; lo que no había era comprobación
 * de forma: el método recorre `datos` con `foreach` e indexa `indicadores` y
 * `valoresSeleccionados` por clave, de modo que cualquier tipo distinto de los
 * esperados terminaba en un 500 en vez de en un mensaje.
 *
 * Las reglas describen el contrato que arma `construirPayload()` en
 * `bitacoras/partials/tabla-script.blade.php`: una hoja por tabla, una fila por
 * contrato y un diccionario `select_{tabla}_{n}` con los desplegables.
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
        return [
            'encabezado' => ['required', 'array'],
            'encabezado.*' => ['string'],

            /* Una entrada por tabla, y cada una es la lista de sus filas. */
            'datos' => ['required', 'array'],
            'datos.*' => ['array'],
            'datos.*.*' => ['array'],

            /* El controlador ya contempla que no vengan: `if ($indicadores !== null)`. */
            'indicadores' => ['nullable', 'array'],
            'indicadores.*' => ['array'],

            /* Se indexa por clave `select_{tabla}_{n}`; los valores llegan
               siempre como texto, incluida la cadena "false". */
            'valoresSeleccionados' => ['required', 'array'],
            'valoresSeleccionados.*' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'encabezado.required' => 'Falta el encabezado de la tabla.',
            'datos.required' => 'No se recibió ninguna tabla que guardar.',
            'datos.array' => 'El formato de las tablas no es válido.',
            'valoresSeleccionados.required' => 'Faltan los valores de los desplegables.',
        ];
    }
}
