<?php

namespace App\Filters;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private int $startRow = 0;
    private int $endRow = 0;

    /**
     * Establece las filas que se leerán en este fragmento.
     */
    public function setRows(int $startRow, int $chunkSize): void
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }
    public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
    {
        // Carga solo las filas dentro del rango definido
        if ($row >= $this->startRow && $row < $this->endRow) {
            return true;
        }
        return false;
    }
}
