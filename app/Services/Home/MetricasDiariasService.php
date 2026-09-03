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
        private LimpiezaMunicipioService $municipios,
        private FechaEjecucionService $fechas
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
                    'contrato'    => '-',
                    'operario'    => $rep->NroOperario . ' - ' . $rep->NombreOperario,
                    'tarea'       => '-',
                    'meses'       => null,
                    'cierre'      => '-',
                    'localidad'   => $this->municipios->limpiar($rep->Localidad),
                    'fecha'       => $this->fechas->mostrar($rep->FechaRealFin),
                    'fecha_orden' => $rep->FechaRealFin ?? '',
                ];
                $metricas['inspectores']++;
            }

            $cierre = strtoupper(trim($rep->Cierre3));
            $infoModal = [
                'contrato'    => ltrim($rep->NroSitio, ':'),
                'operario'    => $rep->NombreOperario,
                'tarea'       => $rep->TipoTarea,
                'meses'       => $rep->Meses,
                'cierre'      => $rep->Cierre3,
                'localidad'   => $this->municipios->limpiar($rep->Localidad),
                'fecha'       => $this->fechas->mostrar($rep->FechaRealFin),
                'fecha_orden' => $rep->FechaRealFin ?? '',
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
     * Inspecciones efectivas de todos los días anteriores al indicado.
     *
     * Sirve para calcular el acumulado histórico: arranca en el registro más
     * antiguo de reportes_diarios y llega hasta el día previo al seleccionado.
     * Una misma orden pudo ejecutarse varias veces en ese lapso, así que se
     * conserva solo la ejecución más reciente de cada contrato y tipo de tarea.
     *
     * @param string $fechaReporte Fecha del reporte en formato Y-m-d.
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array Reportes crudos, listos para el cruce de legalización.
     */
    public function ejecutadasAnteriores(string $fechaReporte, string $localidadSeleccionada): array
    {
        $reportes = DB::table('reportes_diarios')
            ->where('FechaRealFin', '<', $fechaReporte . ' 00:00:00')
            ->orderBy('FechaRealFin')
            ->get();

        return $this->filtrarPorLocalidad($reportes, $localidadSeleccionada)
            ->filter(fn ($rep) => in_array(strtoupper(trim($rep->Cierre3)), self::CIERRES_EFECTIVOS))
            // Al venir ordenadas por fecha, keyBy deja la ejecución más nueva de cada orden
            ->keyBy(fn ($rep) => ltrim($rep->NroSitio, ':') . '|' . trim($rep->TipoTarea))
            ->values()
            ->all();
    }

    /**
     * Fecha de la inspección más antigua registrada, para rotular el acumulado.
     *
     * @return string|null Fecha en formato de la base, o null si no hay datos.
     */
    public function fechaMasAntigua(): ?string
    {
        return DB::table('reportes_diarios')->min('FechaRealFin');
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
            // Sólo municipios madre: por un corregimiento no se filtra.
            ->filter(fn (string $loc) => $this->municipios->esMadre($loc))
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
