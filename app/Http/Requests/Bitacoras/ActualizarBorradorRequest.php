<?php

namespace App\Http\Requests\Bitacoras;

use App\Services\Bitacoras\AutoguardadoService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Un cambio suelto del borrador de una bitácora.
 *
 * El nombre de la columna llegaba del navegador y se asignaba tal cual
 * (`$contrato->$campo = $valor`), así que alcanzaba cualquier campo de la
 * tabla. La lista blanca vive en el servicio, junto a la lógica que la usa.
 */
class ActualizarBorradorRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission:generar_bitacoras en la ruta;
       que la fila sea del borrador propio lo comprueba el servicio. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'campo' => ['required', 'string', Rule::in(AutoguardadoService::CAMPOS_EDITABLES)],
            'valor' => ['present', 'nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'campo.required' => 'No se indicó qué campo cambió.',
            'campo.in' => 'Ese campo no se puede editar desde la bitácora.',
            'valor.string' => 'El valor de la celda no es válido.',
        ];
    }
}
