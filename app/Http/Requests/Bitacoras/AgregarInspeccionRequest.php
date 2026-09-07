<?php

namespace App\Http\Requests\Bitacoras;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Inspección hecha en papel que se añade al borrador a mano.
 *
 * No pasa por el Excel de origen, así que es la única entrada donde los datos
 * los teclea una persona y nadie los había comprobado: el controlador leía
 * `$request->datos[...]` a ciegas y una clave ausente era un error de PHP.
 */
class AgregarInspeccionRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission:generar_bitacoras en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'datos' => ['required', 'array'],
            'datos.nombre' => ['required', 'string', 'max:255'],
            'datos.cedula' => ['required', 'string', 'exists:tbl_insp_cali,cedula'],
            'datos.municipio' => ['required', 'string', 'max:255'],
            'datos.fecha' => ['required', 'date'],
            'datos.acta' => ['required', 'string', 'max:255'],
            'datos.tipoTrabajo' => ['required', 'string', 'max:255'],
            'datos.contrato' => ['required', 'string', 'max:255'],
            'datos.categoria' => ['nullable', 'string', 'max:255'],
            'datos.cantidadRecintos' => ['nullable', 'string', 'max:255'],
            'datos.resultadoCierre' => ['required', 'string', 'max:255'],
            'datos.rechazo' => ['nullable', 'string', 'max:255'],
            'datos.id_bitacora' => ['required', 'integer', 'exists:tbl_bitacora_archivos,id'],
            'datos.id_super' => ['required', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'datos.required' => 'No se recibió la inspección.',
            'datos.cedula.exists' => 'No hay ningún inspector con esa cédula.',
            'datos.id_bitacora.exists' => 'La bitácora indicada no existe.',
        ];
    }
}
