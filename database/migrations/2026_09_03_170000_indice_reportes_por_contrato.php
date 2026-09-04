<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para buscar ejecutados en reportes_diarios.
 *
 * findExecuted ahora consulta esta tabla por contrato y tipo de trabajo, y lo
 * hace una vez por fila del archivo en las cargas masivas. Sin índice cada
 * consulta era un recorrido completo de la tabla más un filesort por fecha;
 * el compuesto resuelve el filtro y deja la fecha para ordenar dentro del
 * puñado de filas que quedan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reportes_diarios', function (Blueprint $tabla) {
            $tabla->index(['NroSitio', 'TipoTarea', 'FechaRealFin'], 'idx_reportes_contrato_tipo');
        });
    }

    public function down(): void
    {
        Schema::table('reportes_diarios', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_reportes_contrato_tipo');
        });
    }
};
