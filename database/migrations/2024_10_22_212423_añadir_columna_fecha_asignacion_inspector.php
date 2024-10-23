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
            $table->String('fecha_asignacion_inspector', 30)->after('codigo_tecnico')->nullable(true);
            $table->renameColumn('obervacion_externa', 'observacion_externa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function (Blueprint $table) {
            // Eliminar las columnas agregadas en esta migración
            $table->dropColumn([
                'fecha_asignacion_inspector',
            ]);
        });
    }
};
