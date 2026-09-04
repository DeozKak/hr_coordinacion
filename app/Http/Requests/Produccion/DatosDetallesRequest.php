<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Carga del detalle de un corte de producción.
 *
 * El identificador puede venir de la sesión o de la petición, y el controlador
 * resuelve `TblProduccionCorte::find($idCorte)` para leer `$corte->id` acto
 * seguido: con un id que no existe eso es un 500. Por eso el campo es opcional
 * —la sesión puede traerlo— pero, si viene, tiene que apuntar a un corte real.
 */
class DatosDetallesRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'idCorteDetalles' => ['nullable', 'integer', 'exists:tbl_produccion_cortes,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'idCorteDetalles.integer' => 'El identificador del corte no es válido.',
            'idCorteDetalles.exists' => 'El corte indicado no existe.',
        ];
    }
}
