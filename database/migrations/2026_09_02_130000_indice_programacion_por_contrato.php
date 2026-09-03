<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para el cruce de quejas con la última programación de cada contrato.
 *
 * La tabla de coordinación de quejas resuelve TECNICO_AGENDADO y
 * FECHA_AGENDAMIENTO con dos subconsultas correlacionadas contra
 * tbl_programacion_contratos, que tiene ~98.000 filas y sólo estaba indexada
 * por su clave primaria. Cada fila de la tabla obligaba a dos recorridos
 * completos, y esa consulta se repite en el sondeo automático cada minuto.
 *
 * Medido sobre los datos reales: 1,99 s sin el índice, 0,005 s con él.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_programacion_contratos', function (Blueprint $tabla) {
            // El orden importa: se filtra por contrato y se ordena por fecha.
            $tabla->index(['contrato', 'fecha_agendamiento'], 'idx_contrato_agendamiento');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_programacion_contratos', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_contrato_agendamiento');
        });
    }
};
