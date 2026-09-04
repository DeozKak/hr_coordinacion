<?php

namespace App\Http\Requests\PQRS;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Edición de una celda del tablero de coordinación de PQRS.
 *
 * La lista de columnas editables se queda en el controlador a propósito: allí
 * depende del permiso `coordinacion_pqrs` —un supervisor sólo toca su
 * observación— y su rechazo ya viaja como un 404 con mensaje propio, que la
 * pantalla revierte celda incluida.
 *
 * Lo que faltaba era la forma de lo que entra. `valor` importa más de lo que
 * parece: cuando la columna es ASIGNADO o RESPONSABLE el controlador hace
 * `explode('.', $request->valor)` para sacar el identificador del inspector, y
 * un arreglo ahí es un error de tipo, no un mensaje.
 */
class ActualizarAsignadoRequest extends FormRequest
{
    /* La autorización la resuelve CheckPermission en la ruta, y el permiso fino
       de qué columnas se pueden tocar lo resuelve el propio controlador. */
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'orden' => ['required'],
            'contrato' => ['required'],
            'campo' => ['required', 'string'],
            'valor' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'orden.required' => 'Falta el número de orden de la queja.',
            'contrato.required' => 'Falta el contrato de la queja.',
            'campo.required' => 'No se pudo determinar qué columna se editó.',
            'valor.string' => 'El valor de la celda no es válido.',
        ];
    }
}
