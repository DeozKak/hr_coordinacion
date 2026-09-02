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
        Schema::create('tbl_dv_insp', function (Blueprint $table) {
            $table->integer('id', true);
            $table->unsignedBigInteger('SUPERVISOR')->index('fr_super_dv');
            $table->integer('INSPECTOR')->index('fr_insp_dv');
            $table->string('CC_OPERARIO');
            $table->string('MUNICIPIO');
            $table->date('FECHA_INSP');
            $table->string('No_ACTA');
            $table->string('TIPO_TRABAJO');
            $table->string('CONTRATO', 30);
            $table->string('ORDEN_TRABAJO')->nullable();
            $table->string('ORDEN_EXT')->nullable();
            $table->string('CATEGORIA')->nullable();
            $table->string('RESULTADO_CIERRE');
            $table->string('HORA_INICIO')->nullable();
            $table->string('HORA_FINAL')->nullable();
            $table->string('DURACION_INSP')->nullable();
            $table->string('4_RECINTOS');
            $table->unsignedBigInteger('id_bitacora')->index('fr_bitacora_dv');
            $table->string('CAUSAL', 30);
            $table->date('FECHA_DV');
            $table->boolean('GESTIONADO');
            $table->string('OBSERVACION_GESTION')->nullable();
            $table->date('FECHA_GESTION')->nullable();
            $table->integer('DIAS_SIN_GESTION');
            $table->boolean('ACTIVADO');
            $table->integer('diseno_especial')->nullable();
            $table->string('vence')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_dv_insp');
    }
};
