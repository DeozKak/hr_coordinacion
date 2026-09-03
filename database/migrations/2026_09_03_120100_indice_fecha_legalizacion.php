<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice para filtrar cerradas por fecha de legalización.
 *
 * La estadística del corte pregunta por un rango de FECHA_LEGALIZACION en cada
 * carga del inicio, y la columna no estaba indexada. Es varchar con formato
 * "Y-m-d H:i:s", así que la comparación de cadenas ordena igual que la fecha y
 * el índice sirve para el rango.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tbl_cerradas', function (Blueprint $tabla) {
            $tabla->index('FECHA_LEGALIZACION', 'idx_fecha_legalizacion');
        });
    }

    public function down(): void
    {
        Schema::table('tbl_cerradas', function (Blueprint $tabla) {
            $tabla->dropIndex('idx_fecha_legalizacion');
        });
    }
};
