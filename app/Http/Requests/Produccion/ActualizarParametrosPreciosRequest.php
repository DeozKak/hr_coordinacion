<?php

namespace App\Http\Requests\Produccion;

/**
 * Edición de un periodo de precios ya existente.
 *
 * Mismas reglas y mismo contrato de respuesta que el alta; sólo añade el
 * periodo que se edita, que el controlador usa además para excluirse a sí
 * mismo al buscar solapamientos.
 */
class ActualizarParametrosPreciosRequest extends ParametrosPreciosRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'id' => ['required', 'integer', 'exists:tbl_parametro_precios,id'],
        ];
    }
}
