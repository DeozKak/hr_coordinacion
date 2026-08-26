<?php

namespace App\Services\Home;

use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class PendientesLegalizarService
{
    /**
     * Meses desde la última certificación a partir de los cuales el contrato se marca como prioridad.
     */
    private const MESES_PARA_PRIORIDAD = 60;

    public function __construct(
        private LimpiezaMunicipioService $municipios
    ) {}

    /**
     * Cruza las inspecciones ejecutadas contra asignaciones y cerradas para saber qué falta legalizar.
     *
     * Una ejecutada queda pendiente de legalizar cuando existe en tbl_asignaciones
     * pero todavía no aparece en tbl_cerradas para el mismo tipo de trabajo.
     *
     * @param iterable $ejecutadas Reportes con cierre efectivo del día.
     * @param string $fechaReporte Fecha del reporte en formato Y-m-d.
     * @return array metricas y detalles de pendientes_legalizar y prioridades.
     */
    public function calcular(iterable $ejecutadas, string $fechaReporte): array
    {
        $metricas = ['pendientes_legalizar' => 0, 'prioridades' => 0];
        $detalles = ['pendientes_legalizar' => [], 'prioridades' => []];

        $ejecutadasList = collect($ejecutadas);

        if ($ejecutadasList->isEmpty()) {
            return ['metricas' => $metricas, 'detalles' => $detalles];
        }

        $contratosEfectivos = $ejecutadasList->map(function ($r) {
            return ltrim($r->NroSitio, ':');
        })->unique()->toArray();

        $asignaciones = DB::table('tbl_asignaciones')
            ->whereIn('CONTRATO', $contratosEfectivos)
            ->get(['CONTRATO', 'ID_TIPO_TRABAJO', 'FECHA_ULTCERTI'])
            ->groupBy('CONTRATO');

        $cerradas = DB::table('tbl_cerradas')
            ->whereIn('CONTRATO', $contratosEfectivos)
            ->get(['CONTRATO', 'ID_TIPO_TRABAJO'])
            ->groupBy('CONTRATO');

        $fechaParseadaReporte = Carbon::parse($fechaReporte);

        foreach ($ejecutadasList as $rep) {
            $contrato = ltrim($rep->NroSitio, ':');
            $tarea = trim(substr($rep->TipoTarea, 2));

            $itemAsignacion = $this->buscarCoincidencia($asignaciones, $contrato, $tarea);

            // Sin asignación abierta no hay nada que legalizar
            if (!$itemAsignacion) {
                continue;
            }

            if ($this->buscarCoincidencia($cerradas, $contrato, $tarea)) {
                continue;
            }

            $metricas['pendientes_legalizar']++;
            $detalles['pendientes_legalizar'][] = $this->infoModal($rep, $contrato);

            if ($this->esPrioridad($itemAsignacion, $fechaParseadaReporte)) {
                $metricas['prioridades']++;
                $detalles['prioridades'][] = $this->infoModal($rep, $contrato);
            }
        }

        return ['metricas' => $metricas, 'detalles' => $detalles];
    }

    /**
     * Busca el registro del contrato que corresponde al tipo de trabajo buscado.
     *
     * El tipo 10444 y el 12161 se consideran equivalentes.
     *
     * @param Collection $lista Registros agrupados por CONTRATO.
     * @return object|null
     */
    private function buscarCoincidencia(Collection $lista, string $contrato, string $tareaBuscada)
    {
        if (!isset($lista[$contrato])) {
            return null;
        }

        foreach ($lista[$contrato] as $item) {
            if ($item->ID_TIPO_TRABAJO == $tareaBuscada || ($tareaBuscada == '10444' && $item->ID_TIPO_TRABAJO == '12161')) {
                return $item;
            }
        }

        return null;
    }

    /**
     * Es prioridad cuando la última certificación supera los meses definidos frente a la fecha del reporte.
     */
    private function esPrioridad(object $itemAsignacion, Carbon $fechaParseadaReporte): bool
    {
        if (empty($itemAsignacion->FECHA_ULTCERTI)) {
            return false;
        }

        try {
            $strFecha = str_replace('/', '-', trim($itemAsignacion->FECHA_ULTCERTI));
            $fechaUlt = Carbon::parse($strFecha);

            return $fechaUlt->diffInMonths($fechaParseadaReporte) >= self::MESES_PARA_PRIORIDAD;
        } catch (\Exception $e) {
            // Fechas con formato inesperado no descalifican el pendiente, sólo no marcan prioridad
            return false;
        }
    }

    /**
     * Fila que consume el modal de detalles en la vista.
     */
    private function infoModal(object $rep, string $contrato): array
    {
        return [
            'contrato'  => $contrato,
            'operario'  => $rep->NombreOperario,
            'tarea'     => $rep->TipoTarea,
            'cierre'    => $rep->Cierre3,
            'localidad' => $this->municipios->limpiar($rep->Localidad),
        ];
    }
}
