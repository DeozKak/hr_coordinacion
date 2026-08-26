<?php

namespace App\Services\Home;

use Carbon\Carbon;

class FechaEjecucionService
{
    /**
     * Texto que se muestra cuando el reporte no trae fecha de ejecución.
     */
    private const SIN_FECHA = '-';

    /**
     * Formatea la fecha de ejecución de un reporte para los modales de detalle.
     *
     * @param mixed $fecha Fecha tal como viene de reportes_diarios.
     * @return string Fecha en formato d/m/Y H:i, o un guion si no hay dato.
     */
    public function mostrar($fecha): string
    {
        if (empty($fecha)) {
            return self::SIN_FECHA;
        }

        try {
            return Carbon::parse($fecha)->format('d/m/Y H:i');
        } catch (\Exception $e) {
            // Una fecha con formato raro no debe tumbar el modal
            return self::SIN_FECHA;
        }
    }
}
