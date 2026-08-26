<?php

namespace App\Services\Home;

use App\Models\Programacion\tbl_programacion_base;

class PendientesBaseService
{
    /** Solo cuentan las órdenes que aún no han sido recepcionadas */
    private const ESTADO_PENDIENTE = '0';

    /** Etiquetas legibles para los códigos de tipo de trabajo */
    private const ETIQUETAS_TIPO = [
        '10444' => 'RP 10444',
        '12161' => 'RP 12161',
        '12162' => 'RN 12162',
        '12163' => 'SA 12163',
        '12164' => 'SA 12164',
        '12166' => 'RP 12166',
    ];

    /** Rangos fijos de meses de vencimiento, en el orden en que se muestran */
    private const RANGOS_MESES = ['-55', '56', '57', '58', '59', '60', '60 +'];

    /** Órdenes pendientes que no tienen el campo MESES diligenciado */
    private const RANGO_SIN_MESES = 'Sin meses';

    /**
     * Pendientes de tbl_programacion_base agrupados por las dos vistas del tablero.
     *
     * @return array tipos, meses y el total de cada agrupación.
     */
    public function generar(): array
    {
        $tipos = $this->porTipoDeTrabajo();
        $meses = $this->porMesesVencimiento();

        return [
            'tipos'      => $tipos,
            'meses'      => $meses,
            'totalTipos' => array_sum(array_column($tipos, 'cantidad')),
            'totalMeses' => array_sum(array_column($meses, 'cantidad')),
            // Todos los registros de la tabla, recepcionados incluidos, para
            // que se entienda por qué el total del tablero es menor
            'totalTabla' => tbl_programacion_base::count(),
        ];
    }

    /**
     * Cantidad de pendientes por cada tipo de trabajo existente en la tabla.
     *
     * @return array<int, array{codigo: string, etiqueta: string, cantidad: int}>
     */
    public function porTipoDeTrabajo(): array
    {
        return tbl_programacion_base::where('ESTADO_RECEPCION', self::ESTADO_PENDIENTE)
            ->selectRaw('ID_TIPO_TRABAJO, COUNT(*) as cantidad')
            ->groupBy('ID_TIPO_TRABAJO')
            ->orderByDesc('cantidad')
            ->get()
            ->map(function ($fila) {
                $codigo = (string) $fila->ID_TIPO_TRABAJO;

                return [
                    'codigo'   => $codigo,
                    'etiqueta' => self::ETIQUETAS_TIPO[$codigo] ?? ($codigo !== '' ? $codigo : 'SIN TIPO'),
                    'cantidad' => (int) $fila->cantidad,
                ];
            })
            ->all();
    }

    /**
     * Cantidad de pendientes por rango de meses de vencimiento.
     *
     * A diferencia de los tipos de trabajo, los rangos son fijos: siempre se
     * devuelven todos, incluso los que quedan en cero. Las órdenes sin MESES
     * se agrupan aparte para que el total coincida con el de tipo de trabajo.
     *
     * @return array<int, array{rango: string, cantidad: int}>
     */
    public function porMesesVencimiento(): array
    {
        $conteos = tbl_programacion_base::where('ESTADO_RECEPCION', self::ESTADO_PENDIENTE)
            ->selectRaw("
                CASE
                    WHEN MESES IS NULL THEN '" . self::RANGO_SIN_MESES . "'
                    WHEN MESES <= 55   THEN '-55'
                    WHEN MESES > 60    THEN '60 +'
                    ELSE CAST(MESES AS CHAR)
                END as rango,
                COUNT(*) as cantidad
            ")
            ->groupBy('rango')
            ->pluck('cantidad', 'rango');

        $rangos = array_merge(self::RANGOS_MESES, [self::RANGO_SIN_MESES]);

        return array_map(fn (string $rango) => [
            'rango'    => $rango,
            'cantidad' => (int) ($conteos[$rango] ?? 0),
        ], $rangos);
    }
}
