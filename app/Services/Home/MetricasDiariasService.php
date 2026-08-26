<?php

namespace App\Services\Home;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MetricasDiariasService
{
    /**
     * Cierres que cuentan como inspección efectivamente ejecutada.
     */
    private const CIERRES_EFECTIVOS = [
        '.CERTIFICADA',
        'CERTIFICADA CON NOVEDADES',
        '.INSPECCIONADA CON DEFECTO NO CRITICO VALLE',
        '.INSPECCIONADA CON DEFECTO CRITICO VALLE',
    ];

    public function __construct(
        private LimpiezaMunicipioService $municipios
    ) {}

    /**
     * Métricas del reporte operativo de un día: inspectores, ejecutadas, fallidas y programadas.
     *
     * @param string $fechaReporte Fecha del reporte en formato Y-m-d.
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array localidadesDisponibles, metricas, detalles, mesesData y ejecutadas (reportes crudos).
     */
    public function generar(string $fechaReporte, string $localidadSeleccionada): array
    {
        $todosReportesDia = $this->reportesDelDia($fechaReporte);

        // El selector se arma con el día completo, sin importar el filtro activo
        $localidadesDisponibles = $this->localidadesDisponibles($todosReportesDia);

        $reportesFiltrados = $this->filtrarPorLocalidad($todosReportesDia, $localidadSeleccionada);

        $metricas = [
            'inspectores'          => 0,
            'ejecutadas'           => 0,
            'fallidas'             => 0,
            'programadas'          => 0,
            'pendientes_legalizar' => 0,
            'prioridades'          => 0,
        ];

        $detalles = [
            'inspectores'          => [],
            'ejecutadas'           => [],
            'fallidas'             => [],
            'programadas'          => [],
            'pendientes_legalizar' => [],
            'prioridades'          => [],
        ];

        $mesesData = [];
        $ejecutadasList = [];
        $inspectoresMap = [];

        foreach ($reportesFiltrados as $rep) {
            if (!isset($inspectoresMap[$rep->NroOperario])) {
                $inspectoresMap[$rep->NroOperario] = true;
                $detalles['inspectores'][] = [
                    'contrato'  => '-',
                    'operario'  => $rep->NroOperario . ' - ' . $rep->NombreOperario,
                    'tarea'     => '-',
                    'cierre'    => '-',
                    'localidad' => $this->municipios->limpiar($rep->Localidad),
                ];
                $metricas['inspectores']++;
            }

            $cierre = strtoupper(trim($rep->Cierre3));
            $infoModal = [
                'contrato'  => ltrim($rep->NroSitio, ':'),
                'operario'  => $rep->NombreOperario,
                'tarea'     => $rep->TipoTarea,
                'cierre'    => $rep->Cierre3,
                'localidad' => $this->municipios->limpiar($rep->Localidad),
            ];

            if (in_array($cierre, self::CIERRES_EFECTIVOS)) {
                $metricas['ejecutadas']++;
                $detalles['ejecutadas'][] = $infoModal;
                $ejecutadasList[] = $rep;

                $mes = !empty($rep->Meses) ? $rep->Meses : 'Sin mes';
                $mesesData[$mes] = ($mesesData[$mes] ?? 0) + 1;

            } elseif ($cierre === 'PROGRAMADA' || $cierre === 'PROGRAMADA.') {
                $metricas['programadas']++;
                $detalles['programadas'][] = $infoModal;
            } elseif ($cierre !== 'CERTIFICADA POR EYC.') {
                $metricas['fallidas']++;
                $detalles['fallidas'][] = $infoModal;
            }
        }

        return [
            'localidadesDisponibles' => $localidadesDisponibles,
            'metricas'               => $metricas,
            'detalles'               => $detalles,
            'mesesData'              => $mesesData,
            'ejecutadas'             => $ejecutadasList,
        ];
    }

    /**
     * Reportes cuya ejecución real terminó dentro del día indicado.
     */
    private function reportesDelDia(string $fechaReporte): Collection
    {
        return DB::table('reportes_diarios')
            ->whereBetween('FechaRealFin', [$fechaReporte . ' 00:00:00', $fechaReporte . ' 23:59:59'])
            ->get();
    }

    /**
     * Municipios madre presentes en el día, ya normalizados, para alimentar el selector.
     */
    private function localidadesDisponibles(Collection $reportes): Collection
    {
        return $reportes->pluck('Localidad')
            ->map(function ($loc) {
                return $this->municipios->limpiar($loc);
            })
            ->filter()
            ->unique()
            ->sort()
            ->values();
    }

    /**
     * Filtra los reportes comparando contra el municipio madre, no contra la localidad cruda.
     */
    private function filtrarPorLocalidad(Collection $reportes, string $localidadSeleccionada): Collection
    {
        if ($localidadSeleccionada === 'TODAS') {
            return $reportes;
        }

        return $reportes->filter(function ($rep) use ($localidadSeleccionada) {
            return $this->municipios->limpiar($rep->Localidad) === $localidadSeleccionada;
        });
    }
}
