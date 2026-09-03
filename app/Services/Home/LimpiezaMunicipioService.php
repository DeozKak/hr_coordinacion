<?php

namespace App\Services\Home;

use Illuminate\Support\Facades\DB;

class LimpiezaMunicipioService
{
    /**
     * Localidades que en la fuente llegan con un nombre distinto al de su municipio madre.
     *
     * Sólo quedan aquí las que no se pueden deducir de los datos: el resto sale
     * del catálogo, que ya declara la madre entre paréntesis.
     */
    private const MAPEO_MUNICIPIOS = [
        'SANTIAGO DE CALI'      => 'CALI',
        'CORREGIMIENTO HOLGUIN' => 'LA VICTORIA',
        'CORREGIMIENTO PAVAS'   => 'LA CUMBRE',
    ];

    /** Prefijo con el que la fuente marca lo que no es un municipio. */
    private const PREFIJO_CORREGIMIENTO = 'CORREGIMIENTO ';

    /**
     * Corregimiento => municipio madre, aprendido del catálogo.
     *
     * Estático a propósito: se arma una vez por petición y lo comparten todos
     * los servicios que reciben esta clase, que son varios.
     */
    private static ?array $hijos = null;

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

        /* Corregimientos que llegan sin el paréntesis: "CORREGIMIENTO EL PLACER"
           es el mismo sitio que "EL PLACER (EL CERRITO)", pero escrito sin decir
           de quién es. La madre se deduce del catálogo. */
        return $this->hijos()[$nombre] ?? $nombre;
    }

    /**
     * ¿Es un municipio madre, o una localidad que cuelga de otro?
     *
     * Sirve para armar los selectores: ahí sólo deben salir las madres, porque
     * son las únicas por las que tiene sentido filtrar.
     */
    public function esMadre($nombre_original): bool
    {
        $nombre = strtoupper(trim((string) $nombre_original));

        if ($nombre === '') {
            return false;
        }

        // Lo que se anuncia como corregimiento no es una madre, se sepa o no de quién cuelga.
        if (str_starts_with($nombre, self::PREFIJO_CORREGIMIENTO)) {
            return false;
        }

        return ! isset($this->hijos()[$nombre]);
    }

    /**
     * Diccionario de corregimiento => madre, leído del catálogo de municipios.
     *
     * tbl_localidades_municipios guarda 229 nombres y no todos son municipios:
     * 183 traen la madre entre paréntesis ("EL PLACER (EL CERRITO)") y 11 son
     * corregimientos sueltos. De los primeros se aprende la relación, y con ella
     * se resuelven también los segundos cuando el mismo sitio aparece de las dos
     * formas. Por eso se deduce en vez de escribirse a mano: la lista crece sola
     * cuando el catálogo crece.
     *
     * @return array<string, string>
     */
    private function hijos(): array
    {
        if (self::$hijos !== null) {
            return self::$hijos;
        }

        self::$hijos = [];

        foreach (DB::table('tbl_localidades_municipios')->pluck('nombre') as $nombre) {
            $nombre = strtoupper(trim((string) $nombre));

            if (! preg_match('/^(.*?)\s*\(\s*([^)]+?)\s*\)\s*$/', $nombre, $partes)) {
                continue;
            }

            $hijo = trim($partes[1]);
            $madre = trim($partes[2]);

            // La madre puede necesitar su propia normalización: "( SANTIAGO DE CALI )".
            $madre = self::MAPEO_MUNICIPIOS[$madre]
                ?? (str_contains($madre, 'SANTIAGO DE CAL') ? 'CALI' : $madre);

            if ($hijo === '' || $madre === '' || $hijo === $madre) {
                continue;
            }

            self::$hijos[$hijo] = $madre;
            // Y la misma relación para cuando llega con el prefijo delante.
            self::$hijos[self::PREFIJO_CORREGIMIENTO . $hijo] = $madre;
        }

        return self::$hijos;
    }

    /**
     * Olvida el diccionario. Sólo lo necesitan las pruebas que cambian el catálogo.
     */
    public static function olvidarCatalogo(): void
    {
        self::$hijos = null;
    }
}
