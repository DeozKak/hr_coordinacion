<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deja toda la base en utf8mb4_unicode_ci.
 *
 * El esquema arrastraba dos collations mezcladas —218 columnas en
 * `utf8mb4_unicode_ci` y 165 en `utf8mb4_general_ci`— y eso no es cosmético:
 * cruzar dos columnas de collation distinta en un JOIN revienta con
 * *Illegal mix of collations*. Ya pasó al unir `tbl_dv_insp.CC_OPERARIO` con
 * `tbl_insp_cali.cedula`, y hoy se sortea con un COLLATE explícito en la
 * consulta que este cambio vuelve innecesario.
 *
 * Se convierte columna a columna y no con `CONVERT TO CHARACTER SET`, que
 * arrastraría también las cinco columnas `utf8mb4_bin`: son los JSON de
 * `activity_log.properties` y de `tbl_produccion_historicos` (`data`,
 * `no_dobles`, …), que deben seguir comparándose byte a byte.
 *
 * Los datos no se tocan. utf8mb4 → utf8mb4 no reescribe nada: sólo cambian las
 * reglas de comparación y ordenamiento. Comprobado sobre una copia real de
 * `tbl_insp_cali`: 0 filas difieren byte a byte, eñes incluidas.
 *
 * Tampoco cambia el resultado de ninguna consulta. Las dos collations sólo
 * discrepan en ß, æ, œ y ligaduras tipo ﬁ, y no hay una sola fila en toda la
 * base que los contenga; para español (ñ, tildes) se comportan igual.
 */
return new class extends Migration
{
    private const COLLATION = 'utf8mb4_unicode_ci';

    private const JUEGO = 'utf8mb4';

    public function up(): void
    {
        /* El MODIFY reconstruye la tabla y revalida las claves ajenas. Aquí
           todas son numéricas, así que ninguna se ve afectada por el cambio,
           pero desactivarlas evita que el orden de conversión importe y acorta
           el bloqueo de escritura. */
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        try {
            foreach ($this->columnasPorConvertir() as $columna) {
                DB::statement($this->sentenciaDeColumna($columna));
            }

            /* Y el juego por omisión de la tabla, para que una columna nueva
               nazca ya con la collation buena. */
            foreach ($this->tablasPorConvertir() as $tabla) {
                DB::statement(sprintf(
                    'ALTER TABLE `%s` DEFAULT CHARACTER SET %s COLLATE %s',
                    $tabla, self::JUEGO, self::COLLATION
                ));
            }
        } finally {
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        }
    }

    /**
     * Sin vuelta atrás.
     *
     * Devolver columnas a `general_ci` reproduciría el problema que esto
     * arregla, y no hay nada que recuperar: los datos son los mismos antes y
     * después.
     */
    public function down(): void
    {
        // Intencionadamente vacío.
    }

    /**
     * Columnas de texto que aún no están en la collation buena.
     *
     * Se filtra por `general_ci` en lugar de por «distinta de unicode_ci» a
     * propósito: así las `utf8mb4_bin` quedan fuera sin tener que enumerarlas.
     */
    private function columnasPorConvertir(): array
    {
        return DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ? AND COLLATION_NAME = ?
              ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [DB::getDatabaseName(), 'utf8mb4_general_ci']
        );
    }

    private function tablasPorConvertir(): array
    {
        $tablas = DB::select(
            'SELECT TABLE_NAME
               FROM information_schema.TABLES
              WHERE TABLE_SCHEMA = ? AND TABLE_TYPE = ? AND TABLE_COLLATION <> ?',
            [DB::getDatabaseName(), 'BASE TABLE', self::COLLATION]
        );

        return array_map(fn ($t) => $t->TABLE_NAME, $tablas);
    }

    /**
     * Rehace la definición de la columna cambiando sólo su collation.
     *
     * `MODIFY` exige repetir la definición entera, así que hay que devolver
     * tipo, nulabilidad y valor por omisión tal y como estaban: perder un
     * `DEFAULT 'OK'` por el camino cambiaría el comportamiento de la
     * aplicación sin que nada avise.
     */
    private function sentenciaDeColumna(object $c): string
    {
        $sql = sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s CHARACTER SET %s COLLATE %s',
            $c->TABLE_NAME, $c->COLUMN_NAME, $c->COLUMN_TYPE, self::JUEGO, self::COLLATION
        );

        $sql .= $c->IS_NULLABLE === 'YES' ? ' NULL' : ' NOT NULL';

        if ($c->COLUMN_DEFAULT !== null) {
            /* Ninguna de estas columnas tiene una expresión por defecto
               (`EXTRA` sin DEFAULT_GENERATED), así que el valor es literal. */
            $sql .= str_contains((string) $c->EXTRA, 'DEFAULT_GENERATED')
                ? ' DEFAULT '.$c->COLUMN_DEFAULT
                : ' DEFAULT '.DB::getPdo()->quote($c->COLUMN_DEFAULT);
        }

        return $sql;
    }
};
