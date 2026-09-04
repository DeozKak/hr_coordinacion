<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Metas mensuales de GyC y GDO en el consolidado.
 *
 * El mes es la clave con la que se busca o se crea la fila de
 * tbl_inspeccion_industrial, así que un formato distinto no falla: crea un
 * registro paralelo que después nadie encuentra.
 *
 * Las dos metas son opcionales porque la pantalla manda sólo la que se editó
 * (`col === 11 ? metagyc : metagdo`), y el controlador conserva la otra.
 */
class InsertarMetasRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'anioMes' => ['required', 'date_format:Y-m'],
            'metagyc' => ['nullable', 'numeric', 'min:0'],
            'metagdo' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'anioMes.required' => 'Falta el mes al que corresponde la meta.',
            'anioMes.date_format' => 'El mes debe venir como aaaa-mm.',
            'metagyc.numeric' => 'La meta de GyC debe ser un número.',
            'metagdo.numeric' => 'La meta de GDO debe ser un número.',
        ];
    }
}
