<?php

namespace App\Support;

use OpenSpout\Common\Entity\Cell\FormulaCell;
use OpenSpout\Common\Entity\Row;

/**
 * Los valores de una fila leída con openspout, como los daba box/spout.
 *
 * `Row::toArray()` de openspout devuelve el texto de la fórmula en las celdas
 * calculadas («=E2*2»), mientras que box/spout devolvía su resultado. Los
 * archivos que se cargan hoy no traen fórmulas, pero basta con que alguien
 * añada una columna calculada en Excel para que se guarde la fórmula en lugar
 * del número, sin ningún error. Se toma el valor calculado, que Excel deja
 * guardado en el archivo.
 *
 * Todo lo que lee Excel con openspout debería pasar por aquí en vez de llamar
 * a `toArray()` directamente.
 */
final class ValoresDeFila
{
    /**
     * @return list<mixed>
     */
    public static function de(Row $fila): array
    {
        return array_map(
            fn ($celda) => $celda instanceof FormulaCell ? $celda->getComputedValue() : $celda->getValue(),
            $fila->getCells()
        );
    }
}
