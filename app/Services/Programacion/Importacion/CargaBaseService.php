<?php

namespace App\Services\Programacion\Importacion;

use App\Models\Programacion\TblProgramacionBase;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Volcado de la base de GDO a tbl_programacion_base.
 *
 * Va con insertOrIgnore porque el archivo se sube varias veces al día y trae
 * siempre el acumulado: lo que ya está se deja como está en vez de fallar por
 * clave repetida.
 */
class CargaBaseService
{
    /** Columna del Excel => columna de la tabla. */
    private const MAPEO = [
        'A' => 'NUMERO_ORDEN',
        'B' => 'CONTRATO',
        'T' => 'DESC_ESTADO_PROD',
        'G' => 'NOMBRE',
        'I' => 'DESC_LOCALIDAD',
        'J' => 'BARRIO',
        'K' => 'DIRECCION',
        'O' => 'NOM_CATE',
        'Q' => 'ID_TIPO_TRABAJO',
    ];

    /** Filas por lote. Insertar de una en una era lo que hacía lento el volcado. */
    private const TAMANO_LOTE = 500;

    /**
     * Inserta la hoja entera.
     *
     * @return bool false si algo falló; el detalle queda en el log, como antes.
     */
    public function cargar(Worksheet $hoja): bool
    {
        try {
            $lote = [];

            foreach ($hoja->getRowIterator(2) as $fila) {
                $indice = $fila->getRowIndex();
                $registro = [];

                foreach (self::MAPEO as $columna => $campo) {
                    $registro[$campo] = $hoja->getCell($columna . $indice)->getValue();
                }

                $lote[] = $registro;

                if (count($lote) >= self::TAMANO_LOTE) {
                    TblProgramacionBase::insertOrIgnore($lote);
                    $lote = [];
                }
            }

            if ($lote !== []) {
                TblProgramacionBase::insertOrIgnore($lote);
            }

            return true;
        } catch (\Exception $e) {
            Log::error('Error al cargar la base de programación: ' . $e->getMessage());

            return false;
        }
    }
}
