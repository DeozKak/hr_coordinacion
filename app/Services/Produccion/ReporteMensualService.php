<?php

namespace App\Services\Produccion;

use App\Models\Nomina\TblNominaFechas;
use App\Models\Produccion\TblInspeccionIndustrial;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Rmunate\Calendario\CalendarioColombia;

/**
 * El reporte diario de un mes: inspecciones por zona, día a día.
 */
class ReporteMensualService
{
    /** Las tres zonas por las que se reparte el conteo. */
    private const ZONAS = [1, 2, 3];

    public function __construct(
        private ParametrosPreciosService $precios
    ) {}

    public function generar($anio, int $mes): array
    {
        $mesFormateado = str_pad((string) $mes, 2, '0', STR_PAD_LEFT);
        $primero = Carbon::parse("$anio-$mesFormateado-01");
        $ultimo = $primero->copy()->endOfMonth();

        $conteos = [];
        $festivos = [];
        $sabados = [];

        for ($dia = $primero->copy(); $dia->lte($ultimo); $dia->addDay()) {
            $fecha = $dia->format('Y-m-d');

            $conteos[] = ['fecha' => $fecha, 'conteos' => $this->conteosDelDia($fecha)];

            if (CalendarioColombia::date($fecha)->isHoliday()) {
                $festivos[] = $fecha;
            }

            if ($dia->dayOfWeekIso === 6) {
                $sabados[] = $fecha;
            }
        }

        return [
            'conteos'             => $conteos,
            'diasFestivos'        => $festivos,
            'diasSabados'         => $sabados,
            'nomina'              => $this->proyeccionDeNomina($primero, $ultimo),
            'inspeccionIndustrial' => $this->inspeccionIndustrial($primero->format('Y-m')),
            'preciosParametros'   => $this->precios->vigentesEn(
                "$anio-$mesFormateado-01", "$anio-$mesFormateado-31"
            ),
        ];
    }

    /**
     * Inspecciones de un día, repartidas por categoría y zona.
     *
     * Va en SQL crudo y no en Eloquent porque son seis conteos condicionales
     * sobre la misma pasada; hacerlo con la colección obligaría a traerse todos
     * los contratos del mes a memoria.
     */
    private function conteosDelDia(string $fecha): array
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

        return DB::select(
            'SELECT MAX(m.id_zona) AS zona, c.CC_OPERARIO,
                    COUNT(DISTINCT c.CC_OPERARIO) AS total_inspectores, ' . implode(', ', $columnas) . '
               FROM tbl_bitacora_contratos c
               JOIN tbl_localidades_municipios AS m ON m.nombre = c.MUNICIPIO
              WHERE c.FECHA = ? AND c.state = 1 AND c.TIPO_TRABAJO NOT IN (?, ?)
              GROUP BY c.CC_OPERARIO',
            array_merge($enlaces, [$fecha], ContratosDelCorteService::MATRICES)
        );
    }

    private function proyeccionDeNomina(Carbon $desde, Carbon $hasta): array
    {
        return TblNominaFechas::whereBetween('fecha', [$desde, $hasta])
            ->get()
            ->map(fn ($fila) => [
                'fechaNomina' => $fila->fecha,
                'proyeccion'  => $fila->cantidad_proyectada,
            ])->all();
    }

    private function inspeccionIndustrial(string $anioMes): array
    {
        return TblInspeccionIndustrial::where('fecha', $anioMes)
            ->get()
            ->map(fn ($fila) => ['cantidad' => $fila->cantidad])
            ->all();
    }
}
