<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alternar el estado de una sede, una zona o un causal.
 *
 * Las tres acciones reciben lo mismo. Sólo se exige que el identificador venga
 * y sea un entero: comprobar aquí que exista devolvería un 422, y los métodos
 * ya responden un 404 con su propio mensaje cuando no encuentran la fila.
 */
class CambiarEstadoRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'id' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'id.required' => 'Falta indicar el registro.',
            'id.integer' => 'El identificador no es válido.',
        ];
    }
}
