<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Repara los valores por omisión que estropeó la conversión de collation.
 *
 * La migración `2026_09_05_090000_unificar_collation_en_utf8mb4_unicode_ci`
 * rehacía cada columna con `MODIFY`, y para eso leía su valor por omisión de
 * `information_schema.COLUMNS`. Ahí está el fallo: MySQL y MariaDB no
 * devuelven lo mismo en `COLUMN_DEFAULT`.
 *
 *                      DEFAULT NULL      DEFAULT 'OK'
 *     MySQL 8          SQL NULL          OK
 *     MariaDB 11       'NULL'            '''OK'''
 *
 * MariaDB devuelve la *expresión SQL*, ya entrecomillada. La migración la
 * volvía a entrecomillar, así que en producción —MariaDB— cada
 * `DEFAULT NULL` acabó siendo `DEFAULT 'NULL'`, la cadena de cuatro letras, y
 * cada `DEFAULT 'OK'` acabó siendo `DEFAULT '''OK'''`, con las comillas
 * dentro del valor. En local —MySQL— no se reprodujo nada de esto, que es por
 * lo que pasó desapercibido.
 *
 * Las consecuencias eran de dos tamaños. La visible: las filas nuevas nacen
 * con el texto «NULL» y las pantallas lo pintan tal cual. La silenciosa, y
 * peor: `tbl_temp_contratos.ESTADO` pasó a nacer con `'OK'` —con apóstrofos—,
 * de modo que las comparaciones del guardado de bitácoras (`ESTADO === 'DV'`,
 * `CAUSAL === '--SELECCIONE CAUSAL--'`) dejaron de reconocer sus propios
 * valores.
 *
 * Se arreglan las dos cosas: la definición de la columna y las filas que ya
 * se escribieron con el valor equivocado.
 */
return new class extends Migration
{
    /** Lo que se corrigió, para el resumen final. */
    private array $resumen = [];

    public function up(): void
    {
        foreach ($this->columnasConDefectoDeComillas() as [$columna, $valor]) {
            $esNulo = $valor === 'NULL';

            $this->corregirDefinicion($columna, $esNulo ? null : $this->sinComillas($valor));
            $this->corregirFilas($columna, $valor, $esNulo);
        }

        $this->informar();
    }

    /**
     * Sin vuelta atrás: lo que deshace esto es volver a romper los defaults.
     */
    public function down(): void
    {
        // Intencionadamente vacío.
    }

    /**
     * Columnas cuyo valor por omisión quedó con una capa de comillas de más.
     *
     * Son dos formas del mismo error: el literal `NULL`, que debería ser el
     * NULL de SQL, y una cadena que se guarda con sus propios apóstrofos.
     *
     * @return list<array{0: object, 1: string}> la columna y su valor actual
     */
    private function columnasConDefectoDeComillas(): array
    {
        $columnas = DB::select(
            'SELECT TABLE_NAME, COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE,
                    COLUMN_DEFAULT, COLLATION_NAME, EXTRA
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND COLUMN_DEFAULT IS NOT NULL
                AND COLLATION_NAME IS NOT NULL
              ORDER BY TABLE_NAME, ORDINAL_POSITION',
            [DB::getDatabaseName()]
        );

        $rotas = [];

        foreach ($columnas as $columna) {
            /* Una expresión (`current_timestamp()`) no es un literal y no se
               toca; en MariaDB se reconoce porque llega sin comillas. */
            if (str_contains((string) $columna->EXTRA, 'DEFAULT_GENERATED')) {
                continue;
            }

            $valor = $this->valorPorOmision($columna);

            if ($valor === null) {
                continue;
            }

            if ($valor === 'NULL' || $this->vieneEntrecomillado($valor)) {
                $rotas[] = [$columna, $valor];
            }
        }

        return $rotas;
    }

    /**
     * El valor que de verdad se escribiría en una fila nueva.
     *
     * En MariaDB hay que quitarle la capa de expresión SQL; en MySQL el valor
     * ya viene crudo.
     */
    private function valorPorOmision(object $columna): ?string
    {
        $crudo = (string) $columna->COLUMN_DEFAULT;

        if (! $this->esMariaDB()) {
            return $crudo;
        }

        if (! $this->vieneEntrecomillado($crudo)) {
            /* Un número o una expresión: no es una cadena literal. */
            return null;
        }

        return $this->sinComillas($crudo);
    }

    private function vieneEntrecomillado(string $valor): bool
    {
        return strlen($valor) >= 2 && str_starts_with($valor, "'") && str_ends_with($valor, "'");
    }

    private function sinComillas(string $valor): string
    {
        return str_replace("''", "'", substr($valor, 1, -1));
    }

    /**
     * Rehace la columna con el valor por omisión correcto.
     *
     * `$correcto` a null significa «que vuelva a ser NULL de verdad».
     */
    private function corregirDefinicion(object $c, ?string $correcto): void
    {
        $nulable = $c->IS_NULLABLE === 'YES';

        $sql = sprintf(
            'ALTER TABLE `%s` MODIFY `%s` %s COLLATE %s %s',
            $c->TABLE_NAME, $c->COLUMN_NAME, $c->COLUMN_TYPE,
            $c->COLLATION_NAME, $nulable ? 'NULL' : 'NOT NULL'
        );

        if ($correcto !== null) {
            $sql .= ' DEFAULT '.DB::getPdo()->quote($correcto);
        } elseif ($nulable) {
            $sql .= ' DEFAULT NULL';
        }
        /* Una columna NOT NULL no puede tener DEFAULT NULL: se queda sin valor
           por omisión, que es lo que tenía antes de que se le colara la
           cadena. */

        DB::statement($sql);
    }

    /**
     * Corrige las filas que ya se escribieron con el valor estropeado.
     *
     * Se comparan por igualdad exacta y con `utf8mb4_bin` para no depender de
     * la collation: sólo caen las filas que valen literalmente lo que puso el
     * default roto.
     */
    private function corregirFilas(object $c, string $malo, bool $esNulo): void
    {
        if (! $esNulo && ! $this->vieneEntrecomillado($malo)) {
            return;
        }

        $bueno = $esNulo ? null : $this->sinComillas($malo);

        /* Una columna NOT NULL no admite el NULL de vuelta; sus filas se
           quedan como están y se avisa en el resumen. */
        if ($bueno === null && $c->IS_NULLABLE !== 'YES') {
            $this->resumen[] = sprintf(
                '  %s.%s: definición corregida; %d filas con el texto «NULL» se dejan '
                .'intactas porque la columna es NOT NULL',
                $c->TABLE_NAME, $c->COLUMN_NAME, $this->contarFilas($c, $malo)
            );

            return;
        }

        $afectadas = DB::update(sprintf(
            'UPDATE `%s` SET `%s` = ? WHERE `%s` COLLATE utf8mb4_bin = ?',
            $c->TABLE_NAME, $c->COLUMN_NAME, $c->COLUMN_NAME
        ), [$bueno, $malo]);

        $this->resumen[] = sprintf(
            '  %s.%s: default %s, %d filas corregidas',
            $c->TABLE_NAME, $c->COLUMN_NAME,
            $esNulo ? 'NULL' : "'".$bueno."'", $afectadas
        );
    }

    private function contarFilas(object $c, string $valor): int
    {
        return (int) DB::scalar(sprintf(
            'SELECT COUNT(*) FROM `%s` WHERE `%s` COLLATE utf8mb4_bin = ?',
            $c->TABLE_NAME, $c->COLUMN_NAME
        ), [$valor]);
    }

    private function esMariaDB(): bool
    {
        return str_contains(strtolower((string) DB::scalar('SELECT VERSION()')), 'mariadb');
    }

    private function informar(): void
    {
        if ($this->resumen === []) {
            echo "  Sin defaults que reparar.\n";

            return;
        }

        echo implode("\n", $this->resumen)."\n";
    }
};
