<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Proyección diaria de producción del reporte.
 *
 * Escribe `cantidad_proyectada` en tbl_nomina_fechas, que es de donde sale la
 * proyección de nómina, y hasta ahora no comprobaba nada. La fecha se guarda
 * tal cual como clave de la fila, así que un valor con otro formato creaba una
 * fila nueva en vez de actualizar la que tocaba.
 *
 * La cantidad admite la cadena «NaN» a propósito: la tabla la manda cuando la
 * celda queda vacía y el controlador la traduce a cero. Una regla `numeric`
 * seca rompería esa vía.
 */
class GuardarProduccionRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fechaFila' => ['required', 'date_format:Y-m-d'],
            'nuevaCant' => ['required', $this->numeroONaN()],
        ];
    }

    /**
     * Número, o la cadena «NaN» que manda el Handsontable al vaciar la celda.
     */
    protected function numeroONaN(): \Closure
    {
        return function (string $atributo, mixed $valor, \Closure $fallar): void {
            if ($valor !== 'NaN' && ! is_numeric($valor)) {
                $fallar('La cantidad debe ser un número.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'fechaFila.required' => 'Falta la fecha de la fila.',
            'fechaFila.date_format' => 'La fecha debe venir como aaaa-mm-dd.',
            'nuevaCant.required' => 'Falta la cantidad proyectada.',
        ];
    }
}
