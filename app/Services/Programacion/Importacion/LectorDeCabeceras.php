<?php

namespace App\Services\Programacion\Importacion;

use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Saca la primera fila de un Excel como "letra de columna => valor".
 *
 * Hay dos formas de leerla según por dónde entre el archivo: PhpSpreadsheet,
 * que da la hoja entera, y Spout, que da la fila como un array plano. Las dos
 * acaban en la misma forma para que el formato se compruebe igual.
 */
class LectorDeCabeceras
{
    /** Desde una hoja de PhpSpreadsheet. */
    public function deLaHoja(Worksheet $hoja, FormatoExcel $formato): array
    {
        $fila = [];

        foreach (array_keys($formato->cabeceras) as $columna) {
            $fila[$columna] = $hoja->getCell($columna . '1')->getValue();
        }

        return $fila;
    }

    /**
     * Desde una fila plana, como la entrega Spout.
     *
     * El índice 0 es la columna A, el 1 la B, y así.
     */
    public function deLaFila(array $valores): array
    {
        $fila = [];

        foreach (array_values($valores) as $i => $valor) {
            $fila[$this->letra($i)] = $valor;
        }

        return $fila;
    }

    /** 0 => A, 25 => Z, 26 => AA. */
    private function letra(int $indice): string
    {
        $letra = '';

        for ($n = $indice; $n >= 0; $n = intdiv($n, 26) - 1) {
            $letra = chr($n % 26 + 65) . $letra;
        }

        return $letra;
    }
}
