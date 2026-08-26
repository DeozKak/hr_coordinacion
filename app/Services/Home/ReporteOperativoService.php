<?php

namespace App\Services\Home;

class ReporteOperativoService
{
    public function __construct(
        private MetricasDiariasService $metricasDiarias,
        private PendientesLegalizarService $pendientesLegalizar
    ) {}

    /**
     * Reporte operativo diario completo: métricas del día más el cruce de legalización.
     *
     * @param string $fechaReporte Fecha del reporte en formato Y-m-d.
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array localidadesDisponibles, metricas, detalles y mesesData.
     */
    public function generar(string $fechaReporte, string $localidadSeleccionada): array
    {
        $diarias = $this->metricasDiarias->generar($fechaReporte, $localidadSeleccionada);

        $legalizacion = $this->pendientesLegalizar->calcular($diarias['ejecutadas'], $fechaReporte);

        return [
            'localidadesDisponibles' => $diarias['localidadesDisponibles'],
            'metricas'               => array_merge($diarias['metricas'], $legalizacion['metricas']),
            'detalles'               => array_merge($diarias['detalles'], $legalizacion['detalles']),
            'mesesData'              => $diarias['mesesData'],
        ];
    }
}
