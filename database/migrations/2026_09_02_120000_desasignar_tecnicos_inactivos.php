<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Deja la fuerza de trabajo sólo con inspectores activos.
 *
 * A partir de ahora, dar de baja a un inspector lo retira de su localidad
 * (lo hace el modelo tbl_insp_cali). Esta migración salda lo de antes: las
 * asignaciones que quedaron colgando de inspectores ya desactivados —o de
 * inspectores que ya ni existen— y que seguían sumando en la tarjeta.
 */
return new class extends Migration
{
    public function up(): void
    {
        $activos = DB::table('tbl_insp_cali')
            ->where('state', 1)
            ->whereNotNull('id')
            ->pluck('id');

        /* Sin activos no hay nada que conservar, pero tampoco nada que borrar:
           `whereNotIn` contra una lista vacía genera `where 1 = 1` y se llevaría
           por delante todas las asignaciones. */
        if ($activos->isEmpty()) {
            return;
        }

        /* Con pluck y no con una subconsulta: en MySQL, NOT IN contra un
           conjunto que contenga NULL no devuelve ninguna fila y la limpieza
           se quedaría en nada sin avisar. */
        DB::table('tbl_asignacion_tecnicos_localidad')
            ->whereNotIn('id_tecnico', $activos)
            ->delete();
    }

    public function down(): void
    {
        /* No se puede deshacer: al retirar la asignación no queda constancia
           de en qué localidad estaba cada uno. Volver a asignarlos es trabajo
           de coordinación, no de una migración. */
    }
};
