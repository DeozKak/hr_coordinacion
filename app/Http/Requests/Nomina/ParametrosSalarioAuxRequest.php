<?php

namespace App\Http\Requests\Nomina;

use App\Http\Requests\Concerns\TraduceFallosAEstado;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Periodo de salario mínimo, auxilio y aportes.
 *
 * Es la base de todo el cálculo de nómina. La comprobación que había no
 * sostenía nada: los dos importes pasaban por `intval()` antes de preguntar
 * `is_numeric()`, que a esas alturas siempre es cierto, y los ocho porcentajes
 * sólo se miraban contra la cadena vacía. Un «abc» en salud entraba tal cual a
 * la tabla y salía multiplicando en la liquidación.
 *
 * El tope de 100 en los porcentajes es el que la propia pantalla ya aplica
 * antes de enviar; aquí se repite porque esa comprobación vive en el navegador.
 */
class ParametrosSalarioAuxRequest extends FormRequest
{
    use TraduceFallosAEstado;

    /** Los ocho aportes, todos expresados como porcentaje. */
    private const PORCENTAJES = [
        'salud', 'pension', 'arl', 'caja',
        'prima', 'cesantias', 'intCesantias', 'vacaciones',
    ];

    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $reglas = [
            /* `<input type="month">`: viaja y se guarda como aaaa-mm. */
            'fechaSalAuxInicio' => ['required', 'date_format:Y-m'],
            'fechaSalAuxFin' => ['required', 'date_format:Y-m'],

            'salMin' => ['required', 'numeric', 'min:0'],
            'auxTrans' => ['required', 'numeric', 'min:0'],
        ];

        foreach (self::PORCENTAJES as $campo) {
            $reglas[$campo] = ['required', 'numeric', 'min:0', 'max:100'];
        }

        return $reglas;
    }

    protected function camposDeFecha(): array
    {
        return ['fechaSalAuxInicio', 'fechaSalAuxFin'];
    }
}
