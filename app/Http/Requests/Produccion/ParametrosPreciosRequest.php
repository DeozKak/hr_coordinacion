<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Periodo de precios para el cálculo de nómina.
 *
 * Estos valores multiplican la producción de cada inspector, así que colar un
 * texto donde va un precio sale caro. El controlador creía cubrirlo, pero su
 * comprobación es inútil: aplica `intval()` a cada campo y después pregunta
 * `is_numeric()` sobre el resultado, que a esas alturas siempre es un entero.
 * Un precio de «abc» pasaba como 0.
 *
 * La respuesta NO es un 422. La pantalla declara —y así lo dice su comentario—
 * que «el endpoint contesta siempre 200 con un `status` numérico», y ramifica
 * sobre 1..7 para elegir el mensaje. Un 422 caería en su rama por defecto
 * («Respuesta no reconocida del servidor»), así que los fallos se traducen a
 * los códigos que ya existen: 1 para las fechas y 3 para los importes.
 */
class ParametrosPreciosRequest extends FormRequest
{
    /** Códigos que la pantalla ya sabe interpretar. */
    private const ESTADO_FECHAS_OBLIGATORIAS = 1;

    private const ESTADO_DATOS_INVALIDOS = 3;

    /** Los importes del periodo, todos con las mismas reglas. */
    private const IMPORTES = [
        'metroRes', 'norteRes', 'caucaRes',
        'metroCom', 'norteCom', 'caucaCom',
        'inspeccionInd',
    ];

    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            /* `<input type="month">`: el valor viaja y se guarda como aaaa-mm. */
            'fechaPrecioInicio' => ['required', 'date_format:Y-m'],
            'fechaPrecioFin' => ['required', 'date_format:Y-m'],
        ];

        foreach (self::IMPORTES as $campo) {
            $reglas[$campo] = ['required', 'numeric', 'min:0'];
        }

        return $reglas;
    }

    /**
     * El orden de la comparación con la fecha de fin se deja al controlador,
     * que ya devuelve el código 2 para ese caso.
     */
    protected function failedValidation(Validator $validador): void
    {
        $fallanFechas = array_intersect(
            array_keys($validador->errors()->toArray()),
            ['fechaPrecioInicio', 'fechaPrecioFin']
        );

        throw new HttpResponseException(response()->json([
            'status' => $fallanFechas !== []
                ? self::ESTADO_FECHAS_OBLIGATORIAS
                : self::ESTADO_DATOS_INVALIDOS,
        ]));
    }
}
