<?php

namespace App\Http\Requests\Produccion;

/**
 * Contar inspecciones dobles de un sábado.
 *
 * Igual que el resto de días dobles, pero aquí la persona escribe además
 * cuántas inspecciones cuentan, y ese número entra directo en el JSON como
 * `total_contratos`. La pantalla ya impide poner cero o más de las que hay,
 * pero esa comprobación vive en el navegador: el tope real depende de datos
 * que hay que recalcular en el servidor, así que aquí se exige al menos que
 * sea un entero positivo.
 */
class ContarDoblesSabadoRequest extends DoblesInspectorRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'diasContados' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return parent::messages() + [
            'diasContados.required' => 'Falta la cantidad de inspecciones a contar.',
            'diasContados.integer' => 'La cantidad debe ser un número entero.',
            'diasContados.min' => 'Hay que contar al menos una inspección.',
        ];
    }
}
