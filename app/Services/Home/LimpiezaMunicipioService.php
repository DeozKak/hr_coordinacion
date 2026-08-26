<?php

namespace App\Services\Home;

class LimpiezaMunicipioService
{
    /**
     * Localidades que en la fuente llegan con un nombre distinto al de su municipio madre.
     */
    private const MAPEO_MUNICIPIOS = [
        'SANTIAGO DE CALI'      => 'CALI',
        'CORREGIMIENTO HOLGUIN' => 'LA VICTORIA',
        'CORREGIMIENTO PAVAS'   => 'LA CUMBRE',
    ];

    /**
     * Normaliza el nombre de una localidad hasta su municipio madre.
     *
     * @param mixed $nombre_original Nombre tal como viene de la base de datos.
     * @return string Municipio madre en mayúsculas.
     */
    public function limpiar($nombre_original): string
    {
        $nombre = strtoupper(trim((string) $nombre_original));

        // Cuando viene como "CORREGIMIENTO X (LA CUMBRE)" nos quedamos con el paréntesis
        if (preg_match('/\(([^)]+)\)/', $nombre, $matches)) {
            $nombre = strtoupper(trim($matches[1]));
        }

        if (array_key_exists($nombre, self::MAPEO_MUNICIPIOS)) {
            return self::MAPEO_MUNICIPIOS[$nombre];
        }

        if (str_contains($nombre, 'SANTIAGO DE CALI') || str_contains($nombre, 'SANTIAGO DE CALÍ')) {
            return 'CALI';
        }

        return $nombre;
    }
}
