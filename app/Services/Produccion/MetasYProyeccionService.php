<?php

namespace App\Services\Produccion;

use App\Models\Nomina\TblNominaFechas;
use App\Models\Produccion\TblInspeccionIndustrial;

/**
 * Las cifras que coordinación teclea sobre el reporte: proyección y metas.
 */
class MetasYProyeccionService
{
    /**
     * Inspecciones proyectadas para un día.
     *
     * La rejilla manda "NaN" cuando la celda queda vacía, que llega como texto
     * y hay que tratar como cero.
     */
    public function proyectar(string $fecha, $cantidad): bool
    {
        $nomina = TblNominaFechas::firstOrNew(['fecha' => $fecha]);
        $nomina->cantidad_proyectada = $this->numero($cantidad);

        return (bool) $nomina->save();
    }

    /** Inspecciones industriales de un mes, con su valor total. */
    public function inspeccionIndustrial(string $fecha, $cantidad, $total): bool
    {
        // Llega como Y-m-d y la tabla guarda solo el mes.
        [$anio, $mes] = explode('-', $fecha);

        $registro = TblInspeccionIndustrial::firstOrNew(['fecha' => $anio . '-' . $mes]);
        $registro->cantidad = $this->numero($cantidad);
        $registro->total = $total;
        $registro->metagyc ??= 0;
        $registro->metagdo ??= 0;

        return (bool) $registro->save();
    }

    /**
     * Metas del mes.
     *
     * Una meta que llega vacía deja la que ya estaba: el formulario manda las
     * dos juntas y solo se edita una cada vez.
     */
    public function guardarMetas(string $anioMes, $metaGyc, $metaGdo): bool
    {
        $registro = TblInspeccionIndustrial::firstOrNew(['fecha' => $anioMes]);
        $registro->cantidad ??= 0;
        $registro->total ??= 0;
        $registro->metagyc = $metaGyc ?? $registro->metagyc ?? 0;
        $registro->metagdo = $metaGdo ?? $registro->metagdo ?? 0;

        return (bool) $registro->save();
    }

    private function numero($valor): int
    {
        return ($valor === 'NaN' || $valor === null) ? 0 : (int) $valor;
    }
}
