<?php

namespace App\Services\Home;

use Illuminate\Support\Facades\DB;

class PendientesBaseService
{
    /** Tabla de la que salen las órdenes abiertas. */
    private const TABLA = 'tbl_asignaciones';

    /** Rangos fijos de meses de vencimiento, en el orden en que se muestran */
    private const RANGOS_MESES = ['-55', '56', '57', '58', '59', '60', '60 +'];

    /** Órdenes sin fecha de última certificación con la que calcular los meses */
    private const RANGO_SIN_MESES = 'Sin meses';

    public function __construct(
        private TiposTrabajoService $tipos,
        private MesesDeVencimientoService $meses
    ) {}

    /**
     * Pendientes agrupados por las dos vistas del tablero.
     *
     * La fuente es tbl_asignaciones, la foto de las órdenes abiertas: aquí no
     * hay estado que filtrar, toda fila cuenta como pendiente. Antes se leía
     * tbl_programacion_base descartando las ya recepcionadas.
     *
     * @return array tipos, meses y el total de cada agrupación.
     */
    public function generar(): array
    {
        $filas = DB::table(self::TABLA)
            ->select('ID_TIPO_TRABAJO', 'PLAZO_MAXIMO')
            ->get();

        $tipos = $this->porTipoDeTrabajo($filas);
        $meses = $this->porMesesVencimiento($filas);

        return [
            'tipos'      => $tipos,
            'meses'      => $meses,
            'totalTipos' => array_sum(array_column($tipos, 'cantidad')),
            'totalMeses' => array_sum(array_column($meses, 'cantidad')),
            'totalTabla' => $filas->count(),
        ];
    }

    /**
     * Cantidad de órdenes por cada tipo de trabajo.
     *
     * @return array<int, array{codigo: string, etiqueta: string, cantidad: int}>
     */
    private function porTipoDeTrabajo($filas): array
    {
        return $filas
            ->groupBy(fn ($fila) => trim((string) $fila->ID_TIPO_TRABAJO))
            ->map(fn ($grupo, $codigo) => [
                'codigo'   => $codigo,
                'etiqueta' => $this->tipos->etiqueta($codigo),
                'cantidad' => $grupo->count(),
            ])
            ->sortByDesc('cantidad')
            ->values()
            ->all();
    }

    /**
     * Cantidad de órdenes por rango de meses de vencimiento.
     *
     * Los rangos son fijos: siempre se devuelven todos, incluso los que quedan
     * en cero. Las órdenes sin plazo se agrupan aparte para que el total
     * coincida con el de tipo de trabajo.
     *
     * @return array<int, array{rango: string, cantidad: int}>
     */
    private function porMesesVencimiento($filas): array
    {
        $conteos = [];

        foreach ($filas as $fila) {
            $meses = $this->mesesSegunPlazo($fila->PLAZO_MAXIMO);
            $rango = $meses === null ? self::RANGO_SIN_MESES : $this->rangoDe($meses);
            $conteos[$rango] = ($conteos[$rango] ?? 0) + 1;
        }

        $rangos = array_merge(self::RANGOS_MESES, [self::RANGO_SIN_MESES]);

        return array_map(fn (string $rango) => [
            'rango'    => $rango,
            'cantidad' => $conteos[$rango] ?? 0,
        ], $rangos);
    }

    /**
     * Meses de vencimiento de una orden, a partir de su plazo máximo.
     *
     * La cuenta vive en MesesDeVencimientoService porque el volcado diario de
     * movilidad tiene que dar el mismo número que este tablero.
     */
    public function mesesSegunPlazo($plazoMaximo): ?int
    {
        return $this->meses->desdePlazo($plazoMaximo);
    }

    /**
     * Rango al que pertenece un número de meses.
     */
    private function rangoDe(int $meses): string
    {
        if ($meses <= 55) {
            return '-55';
        }

        return $meses > 60 ? '60 +' : (string) $meses;
    }
}
