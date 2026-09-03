<?php

namespace App\Services\Home;

class TiposTrabajoService
{
    /**
     * Iniciales de cada código de tipo de trabajo.
     *
     * RP: revisión periódica · RN: revisión nueva · SA: solicitud de atención.
     *
     * Vivía suelto dentro de PendientesBaseService y ahora lo comparten esa
     * tarjeta y la de programaciones, que enseñaban el mismo código con dos
     * aspectos distintos: aquí "RP 10444" y allí un escueto "10444".
     */
    private const INICIALES = [
        '10444' => 'RP',
        '12161' => 'RP',
        '12162' => 'RN',
        '12163' => 'SA',
        '12164' => 'SA',
        '12166' => 'RP',
    ];

    /**
     * Etiqueta legible de un código: iniciales y código, o el código a secas
     * cuando no está en la tabla.
     *
     * En programaciones aparecen códigos que no son tipos de trabajo —números
     * de orden colados en la columna, un "Ext. 64"— y ahí no se inventa nada:
     * se devuelve tal cual.
     */
    public function etiqueta($codigo): string
    {
        $codigo = trim((string) $codigo);

        if ($codigo === '') {
            return 'SIN TIPO';
        }

        $iniciales = self::INICIALES[$codigo] ?? null;

        return $iniciales ? "{$iniciales} {$codigo}" : $codigo;
    }

    /**
     * Sólo las iniciales, para cuando el código ya se ve al lado.
     */
    public function iniciales($codigo): ?string
    {
        return self::INICIALES[trim((string) $codigo)] ?? null;
    }
}
