<?php

namespace App\Http\Requests\Produccion;

/**
 * Cantidad y total de la inspección industrial del mes.
 *
 * Comparte con la proyección diaria la tolerancia a «NaN» y el formato de
 * fecha, pero aquí el controlador hace `explode("-", $fecha)` y se queda con
 * las dos primeras partes para formar el aaaa-mm con el que busca la fila. Con
 * una fecha sin guiones ese índice no existe y la petición terminaba en error.
 */
class InspeccionIndustrialRequest extends GuardarProduccionRequest
{
    public function rules(): array
    {
        return [
            'fechaFila' => ['required', 'date_format:Y-m-d'],
            'valor' => ['nullable', $this->numeroONaN()],
            'totalFinal' => ['required', 'numeric'],
        ];
    }

    public function messages(): array
    {
        return [
            'fechaFila.required' => 'Falta la fecha de la fila.',
            'fechaFila.date_format' => 'La fecha debe venir como aaaa-mm-dd.',
            'totalFinal.required' => 'Falta el total facturado.',
            'totalFinal.numeric' => 'El total debe ser un número.',
        ];
    }
}
