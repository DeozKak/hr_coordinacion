<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('asignadas', function (Blueprint $table) {
            $table->String('estado_programacion', 50)->after('fecha_reasignacion_externa')->nullable(true);
            $table->integer('codigo_tecnico')->after('estado_programacion')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_inspeccion_industrial', function (Blueprint $table) {
            // Eliminar las columnas agregadas en esta migración
            $table->dropColumn([
                'estado_programacion',
                'codigo_tecnico',
            ]);
        });
    }
};
