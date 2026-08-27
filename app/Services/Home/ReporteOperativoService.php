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
     * Además del día, se calcula el acumulado histórico: el mismo cruce aplicado
     * a todo lo ejecutado antes de la fecha seleccionada, para que se vea el
     * rezago que se viene arrastrando.
     *
     * @return array localidadesDisponibles, metricas, detalles, mesesData y acumuladoDesde.
     */
    public function generar(string $fechaReporte, string $localidadSeleccionada): array
    {
        $diarias = $this->metricasDiarias->generar($fechaReporte, $localidadSeleccionada);

        $legalizacion = $this->pendientesLegalizar->calcular($diarias['ejecutadas']);

        $ejecutadasPrevias = $this->metricasDiarias->ejecutadasAnteriores($fechaReporte, $localidadSeleccionada);
        $acumulado = $this->pendientesLegalizar->calcular($ejecutadasPrevias);

        return [
            'localidadesDisponibles' => $diarias['localidadesDisponibles'],
            'metricas'               => array_merge($diarias['metricas'], $legalizacion['metricas'], [
                'pendientes_legalizar_acumulado' => $acumulado['metricas']['pendientes_legalizar'],
                'prioridades_acumulado'          => $acumulado['metricas']['prioridades'],
            ]),
            'detalles'               => array_merge($diarias['detalles'], $legalizacion['detalles'], [
                'pendientes_legalizar_acumulado' => $acumulado['detalles']['pendientes_legalizar'],
                'prioridades_acumulado'          => $acumulado['detalles']['prioridades'],
            ]),
            'mesesData'              => $diarias['mesesData'],
            'acumuladoDesde'         => $this->metricasDiarias->fechaMasAntigua(),
        ];
    }
}
