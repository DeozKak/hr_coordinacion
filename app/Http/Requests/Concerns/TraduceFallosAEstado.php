<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;

/**
 * Traduce los fallos de validación a los códigos que ya usan las pantallas de
 * periodos (precios de producción y salarios de nómina).
 *
 * Ambas declaran en su propio comentario que «el endpoint contesta siempre 200
 * con un `status` numérico» y ramifican sobre 1..7 para elegir el mensaje. Un
 * 422 caería en su rama por defecto, «Respuesta no reconocida del servidor»,
 * así que validar sin esto habría empeorado lo que ve la persona.
 *
 * El orden entre las dos fechas se deja a los controladores, que ya devuelven
 * el código 2 para ese caso.
 */
trait TraduceFallosAEstado
{
    /** Código 1 de las pantallas: «Las fechas son obligatorias». */
    private const ESTADO_FECHAS_OBLIGATORIAS = 1;

    /** Código 3: «Los datos ingresados no son válidos». */
    private const ESTADO_DATOS_INVALIDOS = 3;

    /**
     * Campos cuyo fallo debe contarse como problema de fechas.
     *
     * @return list<string>
     */
    abstract protected function camposDeFecha(): array;

    protected function failedValidation(Validator $validador): void
    {
        $fallanFechas = array_intersect(
            array_keys($validador->errors()->toArray()),
            $this->camposDeFecha()
        );

        throw new HttpResponseException(response()->json([
            'status' => $fallanFechas !== []
                ? self::ESTADO_FECHAS_OBLIGATORIAS
                : self::ESTADO_DATOS_INVALIDOS,
        ]));
    }
}
