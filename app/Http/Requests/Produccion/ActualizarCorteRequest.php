<?php

namespace App\Http\Requests\Produccion;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Edición de un corte de producción.
 *
 * Las reglas son las que storeCorte ya aplicaba con Validator::make para la
 * misma entidad; al editar no se comprobaba nada, de modo que el mismo
 * formulario aceptaba o rechazaba según se estuviera creando o modificando.
 *
 * Los tres choques de fechas (iguales, invertidas, solapadas) se dejan donde
 * están: el controlador los responde con 200 y un `status`, y la pantalla los
 * distingue de los errores de validación a propósito.
 */
class ActualizarCorteRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255'],
            'fecha_inicio' => ['required', 'date'],
            'fecha_fin' => ['required', 'date', 'after_or_equal:fecha_inicio'],
            'meta' => ['required', 'integer', 'max:250'],
            'dobles' => ['required', 'integer', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'Llene por favor el campo nombre',
            'nombre.max' => 'El nombre no debe superar los 255 caracteres',
            'fecha_inicio.required' => 'Debe seleccionar una fecha de inicio',
            'fecha_inicio.date' => 'La fecha de inicio debe ser una fecha válida',
            'fecha_fin.required' => 'Debe seleccionar una fecha de finalización',
            'fecha_fin.date' => 'La fecha de finalización debe ser una fecha válida',
            'fecha_fin.after_or_equal' => 'La fecha de finalización debe ser igual o posterior a la fecha de inicio',
            'meta.required' => 'Debe ingresar la meta',
            'meta.integer' => 'La meta debe ser un número entero',
            'meta.max' => 'La meta no puede superar 250',
            'dobles.required' => 'Debe ingresar la cantidad de dobles',
            'dobles.integer' => 'La cantidad de dobles debe ser un número entero',
            'dobles.max' => 'La cantidad de dobles no puede superar 50',
        ];
    }

    /**
     * Mismo formato que storeCorte: un 422 con `error` y el primer mensaje.
     * La pantalla lo lee con `datos?.error ?? datos?.message`.
     */
    protected function failedValidation(Validator $validador): void
    {
        throw new HttpResponseException(
            response()->json(['error' => $validador->errors()->first()], 422)
        );
    }
}
