<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Toda la base va en utf8mb4_unicode_ci.
 *
 * No es una manía de estilo: cruzar dos columnas de collation distinta en un
 * JOIN revienta con *Illegal mix of collations*, y el fallo no aparece al
 * crear la columna sino meses después, cuando alguien escribe la consulta que
 * las une. Ya ocurrió entre `tbl_dv_insp.CC_OPERARIO` y `tbl_insp_cali.cedula`.
 *
 * Esta prueba es la red que evita que vuelva a entrar una tabla o una columna
 * fuera de sitio.
 */
class CollationTest extends TestCase
{
    private const COLLATION = 'utf8mb4_unicode_ci';

    /**
     * Los JSON se comparan byte a byte a propósito, así que quedan fuera.
     *
     * @var list<string>
     */
    private const BINARIAS_PERMITIDAS = [
        'activity_log.properties',
        'tbl_produccion_historicos.data',
        'tbl_produccion_historicos.no_dobles',
        'tbl_produccion_historicos.no_dobles_festivos',
        'tbl_produccion_historicos.dobles_sabados',
    ];

    public function test_ninguna_tabla_usa_otra_collation(): void
    {
        $fuera = DB::select(
            'SELECT TABLE_NAME, TABLE_COLLATION
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? AND TABLE_COLLATION <> ?',
            [DB::getDatabaseName(), 'BASE TABLE', self::COLLATION]
        );

        $this->assertSame(
            [],
            array_map(fn ($t) => "{$t->TABLE_NAME} ({$t->TABLE_COLLATION})", $fuera),
            'estas tablas no están en '.self::COLLATION.'; un JOIN contra ellas fallará'
        );
    }

    public function test_ninguna_columna_de_texto_usa_otra_collation(): void
    {
        $fuera = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME, COLLATION_NAME
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND COLLATION_NAME IS NOT NULL AND COLLATION_NAME <> ?',
            [DB::getDatabaseName(), self::COLLATION]
        );

        $sobrantes = [];
        foreach ($fuera as $c) {
            $nombre = "{$c->TABLE_NAME}.{$c->COLUMN_NAME}";

            if (in_array($nombre, self::BINARIAS_PERMITIDAS, true)) {
                continue;
            }

            $sobrantes[] = "{$nombre} ({$c->COLLATION_NAME})";
        }

        $this->assertSame(
            [],
            $sobrantes,
            'estas columnas no están en '.self::COLLATION.'. Si alguna es un JSON que '
            .'debe compararse byte a byte, añádela a BINARIAS_PERMITIDAS; si no, conviértela.'
        );
    }

    public function test_las_columnas_binarias_declaradas_siguen_existiendo(): void
    {
        /* Si una desaparece o se convierte, la lista de excepciones se queda
           mintiendo y la prueba de arriba deja de cubrirla. */
        foreach (self::BINARIAS_PERMITIDAS as $nombre) {
            [$tabla, $columna] = explode('.', $nombre);

            $collation = DB::scalar(
                'SELECT COLLATION_NAME FROM information_schema.COLUMNS
                  WHERE TABLE_SCHEMA = ? AND TABLE_NAME = ? AND COLUMN_NAME = ?',
                [DB::getDatabaseName(), $tabla, $columna]
            );

            $this->assertSame(
                'utf8mb4_bin',
                $collation,
                "{$nombre} figura como excepción binaria pero ya no lo es"
            );
        }
    }
}
