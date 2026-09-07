<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Barre los valores que dejó escritos la conversión de collation rota.
 *
 * La migración `2026_09_05_090000_unificar_collation_en_utf8mb4_unicode_ci`
 * estropeó los valores por omisión al leerlos de `information_schema` sin
 * tener en cuenta que MariaDB los devuelve ya entrecomillados (el porqué está
 * en el cabecero de esa migración). Durante los días que estuvo así, las filas
 * nuevas nacieron con dos clases de basura:
 *
 *   - el texto «NULL», donde debía haber un NULL de verdad;
 *   - cadenas con sus propios apóstrofos: «'OK'» en vez de «OK».
 *
 * La migración `2026_09_07_120000_reparar_defaults_rotos_...` arregla la
 * definición de las columnas y las filas de aquellas cuyo *default* se rompió.
 * Eso no basta: esos valores se copiaron a tablas que nunca tuvieron el
 * default roto —`tbl_temp_contratos` alimenta `tbl_bitacora_contratos` y
 * `tbl_dv_insp` al cerrar una bitácora—, y ahí siguen. Este comando los busca
 * en toda la base, columna por columna.
 *
 * Sin `--aplicar` no escribe nada: informa de qué encontró y enseña ejemplos,
 * porque un valor entrecomillado puede ser legítimo —una observación que de
 * verdad empieza y acaba con apóstrofo— y eso lo decide quien mira, no esto.
 */
class RepararValoresEntrecomillados extends Command
{
    protected $signature = 'bd:reparar-entrecomillados
                            {--aplicar : Corrige las filas; sin esta opción sólo informa}
                            {--solo-nulls : Limita el barrido al texto «NULL», sin tocar las cadenas entrecomilladas}';

    protected $description = 'Busca y corrige los valores que dejó la conversión de collation: el texto «NULL» y las cadenas con apóstrofos de más';

    public function handle(): int
    {
        $hallazgos = [];

        foreach ($this->columnasDeTexto() as $columna) {
            $filas = $this->filasSospechosas($columna);

            if ($filas !== []) {
                $hallazgos[] = [$columna, $filas];
            }
        }

        if ($hallazgos === []) {
            $this->info('Nada que corregir: no queda ningún valor entrecomillado en la base.');

            return self::SUCCESS;
        }

        $this->mostrar($hallazgos);

        if (! $this->option('aplicar')) {
            $this->newLine();
            $this->warn('Simulación. Repite con --aplicar para corregirlo.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($hallazgos as [$columna, $filas]) {
            foreach ($filas as $malo => $datos) {
                $total += $this->corregir($columna, (string) $malo, $datos['bueno']);
            }
        }

        $this->newLine();
        $this->info("Corregidas {$total} filas.");

        return self::SUCCESS;
    }

    /**
     * Todas las columnas de texto de la base.
     */
    private function columnasDeTexto(): array
    {
        return DB::select(
            "SELECT TABLE_NAME, COLUMN_NAME, IS_NULLABLE
               FROM information_schema.COLUMNS
              WHERE TABLE_SCHEMA = ?
                AND DATA_TYPE IN ('varchar','char','text','tinytext','mediumtext','longtext')
              ORDER BY TABLE_NAME, ORDINAL_POSITION",
            [DB::getDatabaseName()]
        );
    }

    /**
     * Los valores rotos que hay en una columna, con cuántas filas ocupa cada uno.
     *
     * Se agrupa por valor en lugar de contar filas sueltas para que el informe
     * quepa en pantalla y se vea de un vistazo qué se va a escribir encima.
     *
     * La comparación va en `utf8mb4_bin`: interesa el byte exacto, no lo que
     * la collation considere equivalente.
     *
     * @return array<string, array{filas: int, bueno: string|null}>
     */
    private function filasSospechosas(object $c): array
    {
        $col = "`{$c->COLUMN_NAME}`";
        $soloNulls = (bool) $this->option('solo-nulls');

        /* El patrón viaja como parámetro en vez de incrustado: la cadena que
           hay que buscar está hecha de apóstrofos, y escaparla dentro del SQL
           es justo la clase de cosa que provocó todo esto. */
        $condicion = $soloNulls
            ? "{$col} COLLATE utf8mb4_bin = ?"
            : "({$col} COLLATE utf8mb4_bin = ? OR ({$col} LIKE ? AND CHAR_LENGTH({$col}) >= 2))";

        $valores = DB::select(sprintf(
            'SELECT %s AS valor, COUNT(*) AS filas FROM `%s` WHERE %s GROUP BY %s',
            $col, $c->TABLE_NAME, $condicion, $col
        ), $soloNulls ? ['NULL'] : ['NULL', "'%'"]);

        $encontrados = [];

        foreach ($valores as $v) {
            $malo = (string) $v->valor;

            $encontrados[$malo] = [
                'filas' => (int) $v->filas,
                'bueno' => $malo === 'NULL'
                    ? ($c->IS_NULLABLE === 'YES' ? null : 'NULL')
                    : substr($malo, 1, -1),
            ];
        }

        return $encontrados;
    }

    private function corregir(object $c, string $malo, ?string $bueno): int
    {
        /* Una columna NOT NULL no admite el NULL de vuelta: se deja como está
           en lugar de inventarle un valor. */
        if ($bueno === 'NULL' && $malo === 'NULL') {
            return 0;
        }

        return DB::update(sprintf(
            'UPDATE `%s` SET `%s` = ? WHERE `%s` COLLATE utf8mb4_bin = ?',
            $c->TABLE_NAME, $c->COLUMN_NAME, $c->COLUMN_NAME
        ), [$bueno, $malo]);
    }

    private function mostrar(array $hallazgos): void
    {
        $tabla = [];

        foreach ($hallazgos as [$columna, $filas]) {
            foreach ($filas as $malo => $datos) {
                $destino = $datos['bueno'] === null
                    ? 'NULL (de verdad)'
                    : ($datos['bueno'] === 'NULL' && $malo === 'NULL'
                        ? 'se deja: la columna es NOT NULL'
                        : $datos['bueno']);

                $tabla[] = [
                    $columna->TABLE_NAME.'.'.$columna->COLUMN_NAME,
                    $this->recortar((string) $malo),
                    $this->recortar((string) $destino),
                    $datos['filas'],
                ];
            }
        }

        $this->table(['Columna', 'Valor actual', 'Quedaría', 'Filas'], $tabla);
    }

    private function recortar(string $texto): string
    {
        return mb_strlen($texto) > 40 ? mb_substr($texto, 0, 37).'...' : $texto;
    }
}
