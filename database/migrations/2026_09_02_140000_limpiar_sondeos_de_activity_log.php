<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Saca de activity_log el ruido de los sondeos automáticos.
 *
 * El middleware ya no los registra, pero quedaban los de antes: la consulta de
 * la campana de notificaciones y el refresco de la tabla de quejas, que corren
 * cada minuto por pestaña abierta y no dicen nada de lo que hizo la persona.
 *
 * No se tocan las acciones de administración sobre notificaciones
 * (admin/notifications/*): esas sí son cosas que alguien hizo a propósito.
 */
return new class extends Migration
{
    private const SONDEOS = [
        '%notifications/json%',
        '%pqrs/coordinacion/datos-actualizados%',
    ];

    public function up(): void
    {
        foreach (self::SONDEOS as $patron) {
            DB::table('activity_log')
                ->where('log_name', 'http_request')
                ->where('description', 'like', $patron)
                ->delete();
        }
    }

    public function down(): void
    {
        /* No se puede deshacer, y tampoco interesa: son registros de sondeos
           automáticos, sin valor de auditoría. */
    }
};
