<?php

namespace App\Services\Produccion;

use App\Models\Produccion\TblInspeccionIndustrial;
use Illuminate\Support\Facades\DB;

/**
 * El consolidado del año y el resumen por zonas de un mes.
 */
class ReporteConsolidadoService
{
    public const MESES = ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio',
        'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'];

    /** Los tipos de trabajo que cuentan como revisión periódica. */
    private const REVISIONES = ['RP 10444', 'RP 12161', 'SA 12163', 'SA 12164'];

    /** Las tres zonas por las que se reparte el conteo. */
    private const ZONAS = [1, 2, 3];

    public function __construct(
        private ParametrosPreciosService $precios
    ) {}

    /**
     * Un renglón por mes del año, con ceros en los meses sin datos.
     */
    public function delAno($anio): array
    {
        $porMes = collect($this->conteosDelAno($anio))->keyBy('mes_numero');
        $industriales = TblInspeccionIndustrial::whereBetween('fecha', [$anio . '-01', $anio . '-12'])
            ->get()->keyBy('fecha');

        $filas = [];

        foreach (self::MESES as $indice => $nombre) {
            $numero = $indice + 1;
            $clave = $anio . '-' . str_pad((string) $numero, 2, '0', STR_PAD_LEFT);

            $conteo = $porMes->get($numero);
            $industrial = $industriales->get($clave);

            $filas[] = [
                'nombre_mes'         => $nombre,
                'total_residencial'  => $conteo->total_residencial ?? 0,
                'total_comercial'    => $conteo->total_comercial ?? 0,
                'total_inspecciones' => $industrial->cantidad ?? 0,
                'total_inspectores'  => $conteo->total_operarios ?? 0,
                'total'              => $industrial->total ?? 0,
                'metaGyc'            => $industrial->metagyc ?? 0,
                'metaGdo'            => $industrial->metagdo ?? 0,
                'total_rp'           => $conteo->total_rp ?? 0,
                'total_previas'      => $conteo->total_previas ?? 0,
            ];
        }

        return $filas;
    }

    private function conteosDelAno($anio): array
    {
        return DB::select(
            'SELECT MONTH(FECHA) AS mes_numero,
                    COUNT(CASE WHEN TIPO_TRABAJO IN (?, ?, ?, ?) THEN id END) AS total_rp,
                    COUNT(CASE WHEN TIPO_TRABAJO = ? THEN id END) AS total_previas,
                    COUNT(CASE WHEN CATEGORIA = ? THEN id END) AS total_residencial,
                    COUNT(CASE WHEN CATEGORIA = ? THEN id END) AS total_comercial,
                    COUNT(DISTINCT CC_OPERARIO) AS total_operarios
               FROM tbl_bitacora_contratos
              WHERE FECHA BETWEEN ? AND ? AND state = 1 AND TIPO_TRABAJO != ?
              GROUP BY mes_numero',
            array_merge(self::REVISIONES, [
                'RN 12162', 'RESIDENCIAL', 'COMERCIAL',
                $anio . '-01-01', $anio . '-12-31',
                ContratosDelCorteService::MATRICES[0],
            ])
        );
    }

    /**
     * Residenciales y comerciales de un mes, repartidos por zona.
     *
     * @param string $fecha El mes en formato Y-m.
     */
    public function porZonas(string $fecha): array
    {
        $columnas = [];
        $enlaces = [];

        foreach (['RESIDENCIAL' => 'residencial', 'COMERCIAL' => 'comercial'] as $categoria => $alias) {
            foreach (self::ZONAS as $zona) {
                $columnas[] = "COUNT(CASE WHEN c.CATEGORIA = ? AND m.id_zona = ?
                                     THEN c.id END) AS count_{$alias}_zona_{$zona}";
                $enlaces[] = $categoria;
                $enlaces[] = $zona;
            }
        }

        $conteos = DB::select(
            'SELECT ' . implode(', ', $columnas) . '
               FROM tbl_bitacora_contratos c
               JOIN tbl_localidades_municipios AS m ON m.nombre = c.MUNICIPIO
              WHERE c.FECHA LIKE ? AND c.state = 1 AND c.TIPO_TRABAJO != ?',
            array_merge($enlaces, ['%' . $fecha . '%', ContratosDelCorteService::MATRICES[0]])
        );

        $fila = $conteos[0] ?? null;

        return [
            'residencial' => $this->porZona($fila, 'residencial'),
            'comercial'   => $this->porZona($fila, 'comercial'),
            'fechas'      => $this->precios->vigentesEnElAno(substr($fecha, 0, 4)),
        ];
    }

    /** @return array<string, int> */
    private function porZona(?object $fila, string $categoria): array
    {
        $valores = [];

        foreach (self::ZONAS as $zona) {
            $valores['zona_' . $zona] = (int) ($fila->{"count_{$categoria}_zona_{$zona}"} ?? 0);
        }

        return $valores;
    }
}
