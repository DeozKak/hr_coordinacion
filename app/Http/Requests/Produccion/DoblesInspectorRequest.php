<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Base de los botones de días dobles del detalle de producción.
 *
 * Cinco acciones comparten exactamente la misma entrada —marcar y desmarcar
 * un día como doble, en día corriente, festivo o sábado— y todas terminan
 * escribiendo el JSON de `tbl_produccion_historicos`, que es lo que después
 * lee nómina. Hasta ahora ninguna comprobaba nada: una cédula inexistente
 * creaba un registro fantasma dentro del JSON que ya nadie relacionaba con
 * un inspector.
 *
 * `fecha` se valida con `date` y no con un formato fijo: el valor se compara
 * tal cual contra las fechas ya guardadas en el JSON, así que pinchar un
 * formato aquí cambiaría comparaciones que hoy funcionan. Lo que se busca es
 * rechazar basura, no reformatear.
 */
class DoblesInspectorRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ccInspector' => ['required', 'string', 'exists:tbl_insp_cali,cedula'],
            'fecha' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'ccInspector.required' => 'Falta la cédula del inspector.',
            'ccInspector.exists' => 'No hay ningún inspector con esa cédula.',
            'fecha.required' => 'Falta la fecha del día que se quiere marcar.',
            'fecha.date' => 'La fecha no es válida.',
        ];
    }
}
