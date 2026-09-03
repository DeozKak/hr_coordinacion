<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Causal de cierre que cuenta como legalización.
 *
 * Estar en tbl_cerradas no basta para dar una orden por legalizada: el archivo
 * de GDO trae también cierres que no legalizan nada. Esta lista es la que
 * decide, y la mantiene coordinación desde la pantalla de configuración.
 */
class CausalLegalizacion extends Model
{
    protected $table = 'tbl_causales_legalizacion';

    protected $fillable = ['causal', 'clave', 'activa', 'creado_por'];

    protected $casts = ['activa' => 'boolean'];

    /**
     * Claves de las causales activas, listas para comparar.
     *
     * Estática a propósito: se arma una vez por petición y la comparten los dos
     * sitios que la consultan —el cruce de pendientes y el conteo del corte—,
     * que en una misma carga del inicio recorren miles de filas.
     *
     * @var array<string, true>|null
     */
    private static ?array $activas = null;

    /** @return array<string, true> */
    public static function claves(): array
    {
        if (self::$activas !== null) {
            return self::$activas;
        }

        return self::$activas = static::query()
            ->where('activa', true)
            ->pluck('clave')
            ->flip()
            ->map(fn () => true)
            ->all();
    }

    /** ¿Este cierre legaliza? */
    public static function legaliza($causal): bool
    {
        return isset(self::claves()[self::normalizar($causal)]);
    }

    /**
     * Deja la causal comparable: sin tildes, en mayúsculas y con un solo
     * espacio entre palabras. La misma causal llega escrita de formas
     * distintas según el export, y "INSTALACIÓN" e "INSTALACION" son la misma.
     */
    public static function normalizar($texto): string
    {
        $texto = mb_strtoupper(trim((string) $texto), 'UTF-8');
        $texto = strtr($texto, [
            'Á' => 'A', 'É' => 'E', 'Í' => 'I', 'Ó' => 'O', 'Ú' => 'U', 'Ü' => 'U', 'Ñ' => 'N',
        ]);

        return preg_replace('/\s+/u', ' ', $texto);
    }

    /** Olvida la lista memorizada. La necesitan las pruebas y el guardado. */
    public static function olvidar(): void
    {
        self::$activas = null;
    }

    protected static function booted(): void
    {
        // Cualquier cambio invalida lo memorizado en la misma petición.
        static::saved(fn () => self::olvidar());
        static::deleted(fn () => self::olvidar());
    }
}
