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
            $table->integer('orden_solicitud_externa')->after('medidor')->nullable(true);
            $table->integer('tipo_solicitud_externa')->after('orden_solicitud_externa')->nullable(true);
            $table->String('fecha_solicitud_externa')->after('tipo_solicitud_externa')->nullable(true);
            $table->String('obervacion_externa',255)->after('fecha_solicitud_externa')->nullable(true);
            $table->String('fecha_reasignacion_externa')->after('obervacion_externa')->nullable(true);
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
                'orden_solicitud_externa',
                'tipo_solicitud_externa',
                'fecha_solicitud_externa',
                'obervacion_externa',
                'fecha_reasignacion_externa'
            ]);
        });
    }
};
