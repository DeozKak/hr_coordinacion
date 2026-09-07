<?php

namespace App\Services\Home;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Shared\Date as FechaExcel;

/**
 * Meses de vencimiento de una orden.
 *
 * Es la fórmula con la que coordinación lleva el control en su hoja:
 *
 *     60 - ((AÑO(PLAZO) - AÑO(HOY())) * 12 + MES(PLAZO) - MES(HOY()))
 *
 * El plazo máximo es la fecha límite —la certificación más el ciclo de 60
 * meses—, así que restarle los meses que faltan devuelve los que ya han pasado.
 * El día no entra en la cuenta, igual que en Excel.
 *
 * Vive aparte porque la usan dos sitios que tienen que dar el mismo número: el
 * tablero de pendientes del inicio y el volcado diario de movilidad.
 */
class MesesDeVencimientoService
{
    /** Ciclo de revisión: el plazo máximo se fija a 60 meses de la certificación. */
    private const CICLO_EN_MESES = 60;

    /** De dónde sale el plazo de cada contrato. */
    private const TABLA_ORDENES = 'tbl_asignaciones';

    /**
     * @param mixed $plazoMaximo Número de serie de Excel, que es como lo deja
     *                           el cargue, o una fecha escrita si llega en CSV.
     * @return int|null null cuando la orden no trae plazo.
     */
    public function desdePlazo($plazoMaximo): ?int
    {
        $plazo = $this->comoFecha($plazoMaximo);

        if ($plazo === null) {
            return null;
        }

        $hoy = Carbon::today();
        $faltan = ($plazo->year - $hoy->year) * 12 + ($plazo->month - $hoy->month);

        return self::CICLO_EN_MESES - $faltan;
    }

    /**
     * Meses de cada contrato, en una sola consulta.
     *
     * Un contrato puede tener varias órdenes abiertas; se queda con el plazo
     * más próximo, que es el que primero vence.
     *
     * @param array<int, string> $contratos Contratos sin los dos puntos delante.
     * @return array<string, int> contrato => meses, sin las que no tienen plazo.
     */
    public function porContrato(array $contratos): array
    {
        if ($contratos === []) {
            return [];
        }

        $plazos = DB::table(self::TABLA_ORDENES)
            ->whereIn('CONTRATO', $contratos)
            ->whereNotNull('PLAZO_MAXIMO')
            ->select('CONTRATO', 'PLAZO_MAXIMO')
            ->get();

        $meses = [];

        foreach ($plazos as $fila) {
            $valor = $this->desdePlazo($fila->PLAZO_MAXIMO);

            if ($valor === null) {
                continue;
            }

            // El plazo más próximo es el mayor número de meses transcurridos.
            $meses[$fila->CONTRATO] = max($meses[$fila->CONTRATO] ?? $valor, $valor);
        }

        return $meses;
    }

    /**
     * La fecha del plazo, venga como número de serie o como texto.
     */
    private function comoFecha($valor): ?Carbon
    {
        $valor = is_string($valor) ? trim($valor) : $valor;

        if ($valor === null || $valor === '' || $valor === 0 || $valor === '0') {
            return null;
        }

        try {
            /* El cargue guarda la celda tal cual y en .xls una fecha es un
               número: 46417 es el 30/01/2027. */
            if (is_numeric($valor)) {
                return Carbon::instance(FechaExcel::excelToDateTimeObject((float) $valor));
            }

            return Carbon::parse(explode(' ', $valor)[0]);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
