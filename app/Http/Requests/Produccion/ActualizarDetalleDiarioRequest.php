<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Edición de una celda del detalle diario.
 *
 * El controlador hace `$contrato->{$datos['prop']} = $datos['newValue']`, con
 * el nombre de la columna llegando tal cual desde el navegador. Sin lista
 * blanca eso permite escribir cualquier campo de `tbl_bitacora_contratos`, y
 * varios cambian dinero o esconden trabajo: `CC_OPERARIO` reasigna el contrato
 * a otro inspector —y la producción alimenta la nómina—, `state` lo saca del
 * conteo, `id_bitacora` lo muda a otra bitácora.
 *
 * Las columnas permitidas son las que la propia pantalla deja editar: el
 * Handsontable del detalle bloquea el resto en `editarFila()`
 * (`bloqueadas = [1, 2, 3, 4, 13, 14, 15, 17, 18]`). Si algún día se desbloquea
 * una columna allí, hay que añadirla también aquí.
 *
 * `newValue` se deja pasar aunque venga vacío: el controlador ya responde
 * «Campo vacío» por su cuenta, y convertirlo en un error de validación
 * cambiaría lo que ve la persona.
 */
class ActualizarDetalleDiarioRequest extends FormRequest
{
    /** Columnas que la pantalla permite editar. */
    private const COLUMNAS_EDITABLES = [
        'FECHA',
        'No_ACTA',
        'TIPO_TRABAJO',
        'CONTRATO',
        'ORDEN_TRABAJO',
        'ORDEN_EXT',
        'CATEGORIA',
        'RESULTADO_CIERRE',
        '4_RECINTOS',
    ];

    /* La autorización la resuelve CheckPermission:ver_residente en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'payload' => ['required', 'array'],
            'payload.prop' => ['required', 'string', Rule::in(self::COLUMNAS_EDITABLES)],
            'payload.newValue' => ['present'],

            /* Los manda la tabla pero el controlador no los usa. */
            'payload.row' => ['sometimes', 'integer'],
            'payload.oldValue' => ['sometimes'],
        ];
    }

    public function messages(): array
    {
        return [
            'payload.required' => 'No se recibió el cambio que hay que guardar.',
            'payload.prop.required' => 'No se pudo determinar qué columna se editó.',
            'payload.prop.in' => 'Esa columna no se puede editar desde el detalle diario.',
        ];
    }
}
