<?php

namespace App\Services\Home;

class ReporteOperativoService
{
    public function __construct(
        private MetricasDiariasService $metricasDiarias,
        private PendientesLegalizarService $pendientesLegalizar,
        private CorteGdoService $corteGdo
    ) {}

    /**
     * Reporte operativo diario completo: métricas del día más el cruce de legalización.
     *
     * Además del día se calcula lo que se arrastra sin legalizar. Ese acumulado
     * va acotado al corte de GDO vigente: es el periodo sobre el que se reporta,
     * y de paso evita recorrer todo el histórico en cada carga. Mientras no haya
     * ningún corte definido se recorre todo, como se hacía antes.
     *
     * @param string $fechaReporte Fecha del reporte en formato Y-m-d.
     * @param string $localidadSeleccionada Municipio madre a filtrar, o 'TODAS'.
     * @return array localidadesDisponibles, metricas, detalles, mesesData, corte y acumuladoDesde.
     */
    public function generar(string $fechaReporte, string $localidadSeleccionada): array
    {
        $diarias = $this->metricasDiarias->generar($fechaReporte, $localidadSeleccionada);

        $legalizacion = $this->pendientesLegalizar->calcular($diarias['ejecutadas']);

        $corte = $this->corteGdo->generar($localidadSeleccionada);
        $inicioCorte = $corte['corte']['inicio'] ?? null;

        $ejecutadasPrevias = $this->metricasDiarias->ejecutadasAnteriores(
            $fechaReporte,
            $localidadSeleccionada,
            $inicioCorte
        );
        $acumulado = $this->pendientesLegalizar->calcular($ejecutadasPrevias);

        return [
            'localidadesDisponibles' => $diarias['localidadesDisponibles'],
            'metricas'               => array_merge($diarias['metricas'], $legalizacion['metricas'], $corte['metricas'], [
                'pendientes_legalizar_acumulado' => $acumulado['metricas']['pendientes_legalizar'],
                'prioridades_acumulado'          => $acumulado['metricas']['prioridades'],
            ]),
            'detalles'               => array_merge($diarias['detalles'], $legalizacion['detalles'], $corte['detalles'], [
                'pendientes_legalizar_acumulado' => $acumulado['detalles']['pendientes_legalizar'],
                'prioridades_acumulado'          => $acumulado['detalles']['prioridades'],
            ]),
            'mesesData'              => $diarias['mesesData'],
            'corte'                  => $corte['corte'],
            // Desde cuándo se está acumulando: el corte si lo hay, y si no el
            // registro más antiguo que exista.
            'acumuladoDesde'         => $inicioCorte ?? $this->metricasDiarias->fechaMasAntigua(),
        ];
    }
}
