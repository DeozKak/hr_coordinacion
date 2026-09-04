<?php

namespace App\Http\Requests\Nomina;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Multa de rodamiento de un inspector en un mes.
 *
 * El mes y la cédula son la clave con la que se busca o se crea la fila de
 * tbl_nomina_multas, así que un formato distinto no falla: crea un registro
 * paralelo que la liquidación después no encuentra, y la multa se pierde.
 *
 * La multa se admite vacía porque el controlador lo contempla: si no viene,
 * conserva la que hubiera o arranca en cero.
 */
class GuardarMultaRodamientoRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fecha' => ['required', 'date_format:Y-m'],
            'ccOperario' => ['required', 'string', 'exists:tbl_insp_cali,cedula'],
            'multa' => ['nullable', 'numeric', 'min:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'fecha.required' => 'Falta el mes de la multa.',
            'fecha.date_format' => 'El mes debe venir como aaaa-mm.',
            'ccOperario.required' => 'Falta la cédula del operario.',
            'ccOperario.exists' => 'No hay ningún inspector con esa cédula.',
            'multa.numeric' => 'La multa debe ser un número.',
            'multa.min' => 'La multa no puede ser negativa.',
        ];
    }
}
