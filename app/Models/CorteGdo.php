<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Corte de GDO: el periodo sobre el que se mide la legalización.
 */
class CorteGdo extends Model
{
    protected $table = 'tbl_cortes_gdo';

    protected $fillable = ['fecha_inicio', 'fecha_fin', 'creado_por'];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin'    => 'date',
    ];

    /**
     * El corte que manda hoy.
     *
     * Primero el que cubre el día de hoy; si no hay ninguno abierto, el último
     * que se cerró, para que la vista siga enseñando la referencia más reciente
     * en vez de quedarse en blanco entre un corte y el siguiente.
     */
    public static function vigente(): ?self
    {
        $hoy = date('Y-m-d');

        return static::query()
            ->where('fecha_inicio', '<=', $hoy)
            ->where('fecha_fin', '>=', $hoy)
            ->orderByDesc('fecha_inicio')
            ->first()
            ?? static::query()->orderByDesc('fecha_fin')->first();
    }
}
