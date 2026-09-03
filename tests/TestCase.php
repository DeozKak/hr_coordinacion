<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use RuntimeException;

abstract class TestCase extends BaseTestCase
{
    /**
     * Bases de datos que nunca deben usarse para pruebas destructivas.
     *
     * Se comparan sin distinguir mayúsculas.
     */
    private const BASES_PROTEGIDAS = ['segoperacion'];

    /**
     * Freno de mano contra el borrado de la base real.
     *
     * phpunit.xml no define una conexión propia (el bloque de SQLite está
     * comentado) y no hay .env.testing, así que las pruebas corren contra la
     * misma base que la aplicación. Mientras solo se lea o se envuelva todo en
     * una transacción no pasa nada, pero un RefreshDatabase haría un
     * `migrate:fresh` sobre los datos de producción y no habría vuelta atrás.
     *
     * Este control aborta antes de tocar nada. Para poder usar RefreshDatabase
     * hay que apuntar las pruebas a otra base: descomentar el bloque de
     * phpunit.xml o crear un .env.testing con su propio DB_DATABASE.
     */
    protected function setUp(): void
    {
        /* La comprobación va ANTES de parent::setUp(), y esto es el detalle que
           importa: RefreshDatabase no borra la base cuando se ejecuta la prueba,
           sino dentro de setUpTraits(), al que llama parent::setUp(). Un control
           colocado después llega tarde: la base ya está vacía.

           Se levanta la aplicación a mano para poder leer la conexión; después
           parent::setUp() la reutiliza en vez de volver a crearla. */
        if (! $this->app) {
            $this->refreshApplication();
        }

        $this->abortarSiBorraLaBaseReal();

        parent::setUp();
    }

    private function abortarSiBorraLaBaseReal(): void
    {
        if (! $this->usaRefrescoDeBase()) {
            return;
        }

        $base = (string) DB::connection()->getDatabaseName();

        foreach (self::BASES_PROTEGIDAS as $protegida) {
            if (strcasecmp(basename($base), $protegida) === 0) {
                throw new RuntimeException(
                    "La prueba " . static::class . " usa RefreshDatabase y las pruebas están "
                    . "apuntando a «{$base}», que es la base de la aplicación: se borraría entera. "
                    . "Configura una base de pruebas aparte (phpunit.xml o .env.testing) antes de continuar."
                );
            }
        }
    }

    /**
     * ¿La prueba trae algún rasgo que recrea el esquema?
     */
    private function usaRefrescoDeBase(): bool
    {
        $rasgos = class_uses_recursive(static::class);

        return isset($rasgos[RefreshDatabase::class])
            || isset($rasgos['Illuminate\Foundation\Testing\DatabaseMigrations']);
    }
}
