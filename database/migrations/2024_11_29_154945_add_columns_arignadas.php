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
        Schema::table('asignadas', function(Blueprint $table){
            $table->integer('orden_trabajo_cerrada')->after('fecha_solicitud_cierre')->nullable(true);
            $table->string('contrato_cerrada', 30)->after('orden_trabajo_cerrada')->nullable(true);
            $table->integer('producto_cerrada')->after('contrato_cerrada')->nullable(true);
            $table->string('tipo_trabajo_cerrada')->after('producto_cerrada')->nullable(true);
            $table->string('fecha_legalizacion', 30)->after('tipo_trabajo_cerrada')->nullable(true);
            $table->string('comentario_legalizacion', 350)->after('fecha_legalizacion')->nullable(true);
            $table->integer('cod_causal')->after('comentario_legalizacion')->nullable(true);
            $table->string('des_causal', 200)->after('cod_causal')->nullable(true);
            $table->string('consecutivo', 30)->after('des_causal')->nullable(true);
            $table->string('dias_proceso', 30)->after('consecutivo')->nullable(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('asignadas', function(Blueprint $table){
            $table->dropColumn([
                'orden_trabajo_cerrada',
                'contrato_cerrada',
                'producto_cerrada',
                'tipo_trabajo_cerrada',
                'fecha_legalizacion',
                'comentario_legalizacion',
                'cod_causal',
                'des_causal',
                'consecutivo',
                'dias_proceso'
            ]);
        });
    }
};
