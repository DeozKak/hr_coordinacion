<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Alta manual de un contrato en el detalle diario.
 *
 * La fila llega como arreglo posicional, tal y como la arma el Handsontable,
 * y el controlador la reparte por índice. Dos de esos índices se usan **antes**
 * del try/catch: `data[4]` pasa por `Carbon::createFromFormat('d-m-y', …)`, que
 * con una fecha malformada lanza y devuelve un 500 en vez de un mensaje, y
 * `data[2]` sirve para localizar la bitácora a la que se asocia.
 *
 * Las posiciones son las de `colHeaders` en
 * `produccion/partials/detalles-script.blade.php`.
 */
class InsertarContratoRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'data' => ['required', 'array', 'min:16'],

            'data.2' => ['required', 'string', 'exists:tbl_insp_cali,cedula'],  // CC OPERARIO
            'data.3' => ['required', 'string', 'max:255'],                      // MUNICIPIO
            'data.4' => ['required', 'date_format:d-m-y'],                      // FECHA
            'data.5' => ['required', 'string', 'max:255'],                      // N° ACTA
            'data.6' => ['required', 'string', 'max:255'],                      // TIPO TRABAJO
            'data.7' => ['required', 'string', 'max:255'],                      // CONTRATO
            'data.8' => ['nullable', 'string', 'max:255'],                      // ORDEN TRABAJO
            'data.9' => ['nullable', 'string', 'max:255'],                      // ORDEN EXT
            'data.10' => ['nullable', 'string', 'max:255'],                     // CATEGORIA
            'data.11' => ['nullable', 'string', 'max:255'],                     // RESULTADO CIERRE
            'data.12' => ['nullable', 'string', 'max:255'],                     // HORA INICIO
            'data.13' => ['nullable', 'string', 'max:255'],                     // HORA FINAL
            'data.14' => ['nullable', 'string', 'max:255'],                     // DURACION INSP
            'data.15' => ['nullable', 'string', 'max:255'],                     // 4 RECINTOS O MAS
        ];
    }

    public function messages(): array
    {
        return [
            'data.required' => 'No se recibió la fila que hay que insertar.',
            'data.min' => 'La fila no trae todas las columnas esperadas.',
            'data.2.required' => 'Falta la cédula del operario.',
            'data.2.exists' => 'No hay ningún inspector con esa cédula.',
            'data.4.required' => 'Falta la fecha.',
            'data.4.date_format' => 'La fecha debe venir como dd-mm-aa.',
            'data.7.required' => 'Falta el contrato.',
        ];
    }
}
