<?php

namespace App\Http\Requests\Nomina;

/**
 * Edición de un periodo de salario ya existente.
 *
 * Mismas reglas y mismo contrato de respuesta que el alta; sólo añade el
 * periodo que se edita, que el controlador usa además para excluirse a sí
 * mismo al buscar solapamientos.
 */
class ActualizarParametrosSalarioAuxRequest extends ParametrosSalarioAuxRequest
{
    public function rules(): array
    {
        return parent::rules() + [
            'id' => ['required', 'integer', 'exists:tbl_parametro_sal_aux,id'],
        ];
    }
}
