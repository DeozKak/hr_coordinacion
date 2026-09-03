<?php

namespace App\Support;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

/**
 * Deja pasar sólo un rango de filas al leer una hoja de cálculo.
 *
 * PhpSpreadsheet construye en memoria todas las celdas que el filtro acepte.
 * Sin filtro, un .xls mediano se convierte en cientos de megas; con él, la
 * memoria queda acotada al tamaño del bloque, cueste lo que cueste el archivo.
 */
class FiltroDeFilas implements IReadFilter
{
    public function __construct(
        private int $desde,
        private int $hasta
    ) {}

    public function readCell($columnAddress, $row, $worksheetName = ''): bool
    {
        return $row >= $this->desde && $row <= $this->hasta;
    }
}
