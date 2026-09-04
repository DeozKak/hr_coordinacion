<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Salda el desfase entre las migraciones versionadas y la base que ya existía.
 *
 * El repositorio arrastra un volcado del esquema (`create_*` y
 * `add_foreign_keys_*`) de una base creada a mano antes de versionar nada.
 * Están en git pero no en la tabla `migrations`, así que `php artisan migrate`
 * intenta crearlas, revienta con *Base table or view already exists* y puede
 * dejar la base a medias. Por eso hasta ahora toda migración se corría con
 * `--path`, apuntando sólo a la nueva.
 *
 * Este comando las registra como aplicadas para que `migrate` vuelva a ser
 * utilizable. No toca el esquema: sólo inserta filas en `migrations`, y sólo
 * de aquellas cuyo objeto ya existe de verdad en la base.
 *
 * Sin `--aplicar` no escribe nada: informa de qué haría.
 */
class MarcarMigracionesAplicadas extends Command
{
    protected $signature = 'migraciones:marcar-aplicadas {--aplicar : Escribe en la tabla migrations; sin esta opción sólo informa}';

    protected $description = 'Marca como aplicadas las migraciones cuyo objeto ya existe en la base, sin ejecutarlas';

    public function handle(): int
    {
        $aplicadas = DB::table('migrations')->pluck('migration')->all();

        $pendientes = collect(glob(database_path('migrations/*.php')))
            ->map(fn ($f) => basename($f, '.php'))
            ->reject(fn ($m) => in_array($m, $aplicadas, true))
            ->sort()
            ->values();

        if ($pendientes->isEmpty()) {
            $this->info('No hay migraciones pendientes. Nada que marcar.');

            return self::SUCCESS;
        }

        $marcar = [];
        $omitir = [];

        foreach ($pendientes as $migracion) {
            $motivo = $this->porQueNoSePuedeMarcar($migracion);
            $motivo === null ? $marcar[] = $migracion : $omitir[$migracion] = $motivo;
        }

        $this->line(sprintf(
            'Pendientes: %d  |  ya reflejadas en la base: %d  |  se dejan pendientes: %d',
            $pendientes->count(),
            count($marcar),
            count($omitir)
        ));

        if ($omitir !== []) {
            $this->newLine();
            $this->warn('Estas NO se marcan, tendrán que ejecutarse con `migrate`:');
            foreach ($omitir as $migracion => $motivo) {
                $this->line("  - {$migracion}");
                $this->line("      {$motivo}");
            }
        }

        if ($marcar === []) {
            return self::SUCCESS;
        }

        if (! $this->option('aplicar')) {
            $this->newLine();
            $this->comment('Simulación: no se ha escrito nada. Repite con --aplicar para registrarlas.');

            return self::SUCCESS;
        }

        $lote = ((int) DB::table('migrations')->max('batch')) + 1;

        DB::table('migrations')->insert(
            array_map(fn ($m) => ['migration' => $m, 'batch' => $lote], $marcar)
        );

        $this->newLine();
        $this->info(sprintf('%d migraciones marcadas como aplicadas en el lote %d.', count($marcar), $lote));
        $this->line('A partir de ahora `php artisan migrate` funciona sin --path.');

        return self::SUCCESS;
    }

    /**
     * Devuelve null si la migración ya está reflejada en la base y por tanto es
     * seguro marcarla; si no, el motivo por el que hay que dejarla pendiente.
     *
     * Sólo se reconocen las dos formas del volcado: crear una tabla y añadirle
     * claves ajenas. Cualquier otra cosa —una migración de datos, un cambio de
     * columnas— se deja correr, porque su efecto no se puede deducir mirando
     * el esquema.
     */
    private function porQueNoSePuedeMarcar(string $migracion): ?string
    {
        $fuente = file_get_contents(database_path("migrations/{$migracion}.php"));

        if (preg_match('/Schema::create\([\'"]([A-Za-z0-9_]+)[\'"]/', $fuente, $coincidencia)) {
            return Schema::hasTable($coincidencia[1])
                ? null
                : "la tabla {$coincidencia[1]} no existe todavía";
        }

        /* Los nombres se sacan del `down()`: la mitad de estas migraciones declara
           la clave sin nombrarla (`->foreign(['id_usuario'])`) y deja que Laravel
           lo genere, pero el `dropForeign` siempre escribe el nombre real. */
        preg_match_all('/dropForeign\([\'"]([A-Za-z0-9_]+)[\'"]\)/', $fuente, $coincidencias);
        $restricciones = array_unique($coincidencias[1] ?? []);

        if ($restricciones === []) {
            return 'no es ni un create_ ni un add_foreign_keys_; su efecto no se puede comprobar';
        }

        $ausentes = array_filter(
            $restricciones,
            fn ($nombre) => ! $this->existeClaveAjena($nombre)
        );

        return $ausentes === []
            ? null
            : 'faltan claves ajenas: '.implode(', ', $ausentes);
    }

    private function existeClaveAjena(string $nombre): bool
    {
        return DB::table('information_schema.TABLE_CONSTRAINTS')
            ->where('CONSTRAINT_SCHEMA', DB::getDatabaseName())
            ->where('CONSTRAINT_NAME', $nombre)
            ->where('CONSTRAINT_TYPE', 'FOREIGN KEY')
            ->exists();
    }
}
