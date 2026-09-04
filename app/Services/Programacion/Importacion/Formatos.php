<?php

namespace App\Services\Programacion\Importacion;

/**
 * Los formatos de Excel que el módulo sabe importar.
 *
 * Declarados en un solo sitio: cuando el proveedor cambie una cabecera, se
 * cambia aquí y no en tres validaciones repartidas por el controlador.
 */
class Formatos
{
    /** Base de GDO: la que se sube con la casilla marcada y la del call center. */
    public static function gdo(): FormatoExcel
    {
        return new FormatoExcel('base GDO', [
            'A' => 'NUMERO_ORDEN',
            'B' => 'CONTRATO',
            'C' => 'PRODUCTO',
            'D' => 'NUMERO_SOLICITUD',
            'E' => 'TIPO_SOLICITUD',
            'F' => 'CEDULA',
            'G' => 'NOMBRE',
            'H' => 'DESC_DEPART',
            'I' => 'DESC_LOCALIDAD',
            'J' => 'BARRIO',
            'K' => 'DIRECCION',
            'L' => 'CONSECUTIVO_RUTA',
            'M' => 'TELEFONO',
            'R' => 'FECHA_ASIGNACION',
            'S' => 'OBSERVACION_SOLICITUD',
        ]);
    }

    /**
     * Base que procesan las macros en segundo plano.
     *
     * Sólo se miran las cinco primeras columnas: es lo que basta para
     * distinguirlo del resto y el archivo llega con muchas más.
     */
    public static function macros(): FormatoExcel
    {
        return new FormatoExcel('base para macros', [
            'A' => 'Orden',
            'B' => 'Contrato',
            'C' => 'Producto',
            'D' => 'Numero solicitud',
            'E' => 'Tipo solicitud',
        ]);
    }

    /** Programación masiva de técnicos. */
    public static function masivos(): FormatoExcel
    {
        return new FormatoExcel('programación masiva', [
            'B' => 'Inspector',
            'C' => 'Categoria',
            'D' => 'Tipo de trabajo',
            'E' => 'Fecha de ejecucion',
            'F' => 'Codigo de Instalacion',
            'G' => 'Sector Operativo',
            'H' => 'Direccion',
            'I' => 'Municipio',
            'J' => 'Nombre Usuario',
            'K' => 'Telefono de Contacto1',
            'N' => 'Fecha de Agendamiento',
            'P' => 'Observacion de Agendamiento',
            'S' => 'ORDE MASIVA',
            'T' => 'Orden externa',
        ]);
    }
}
