<?php

namespace App\Services\Home;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class PendientesBaseService
{
    /** Tabla de la que salen las órdenes abiertas. */
    private const TABLA = 'tbl_asignaciones';

    /** Rangos fijos de meses de vencimiento, en el orden en que se muestran */
    private const RANGOS_MESES = ['-55', '56', '57', '58', '59', '60', '60 +'];

    /** Órdenes sin fecha de última certificación con la que calcular los meses */
    private const RANGO_SIN_MESES = 'Sin meses';

    /** Marca de "sin fecha" que arrastra la fuente. */
    private const FECHA_VACIA = '1970-01-01';

    public function __construct(
        private TiposTrabajoService $tipos
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
            ->select('ID_TIPO_TRABAJO', 'FECHA_ULTCERTI')
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
     * en cero. Las órdenes sin fecha de certificación se agrupan aparte para
     * que el total coincida con el de tipo de trabajo.
     *
     * @return array<int, array{rango: string, cantidad: int}>
     */
    private function porMesesVencimiento($filas): array
    {
        $conteos = [];

        foreach ($filas as $fila) {
            $meses = $this->mesesDesdeCertificacion($fila->FECHA_ULTCERTI);
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
     * Meses transcurridos desde la última certificación.
     *
     * Se cuenta la diferencia de meses de calendario y nada más, que es lo que
     * hace la hoja con la que coordinación contrasta el tablero:
     *
     *     (AÑO(HOY()) - AÑO(ULTCERTI)) * 12 + MES(HOY()) - MES(ULTCERTI)
     *
     * El día no entra. Antes se sumaba un mes cuando sobraban días, y eso
     * inflaba en uno el 16,7% de las órdenes —3.939 de 23.632—, siempre hacia
     * arriba y nunca hacia abajo, así que varias se veían vencidas antes de
     * tiempo. Todo agosto de 2021 son 61 meses, caiga el día que caiga.
     *
     * Una fecha futura da negativo, como en la hoja; cae en el rango de "-55".
     *
     * @return int|null null cuando no hay fecha con la que calcular.
     */
    public function mesesDesdeCertificacion($fechaUltimaCertificacion): ?int
    {
        $valor = trim((string) $fechaUltimaCertificacion);

        if ($valor === '' || str_starts_with($valor, self::FECHA_VACIA)) {
            return null;
        }

        try {
            // La columna es varchar y llega como "2021-11-18 00:00:00".
            $certificacion = Carbon::parse(explode(' ', $valor)[0]);
        } catch (\Exception $e) {
            return null;
        }

        $hoy = Carbon::today();

        return ($hoy->year - $certificacion->year) * 12 + ($hoy->month - $certificacion->month);
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
