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
        Schema::create('tbl_temp_contratos', function (Blueprint $table) {
            $table->string('NOMBRE');
            $table->bigInteger('id', true);
            $table->string('CC_OPERARIO');
            $table->string('MUNICIPIO')->nullable();
            $table->date('FECHA')->nullable();
            $table->string('No_ACTA');
            $table->string('TIPO_TRABAJO')->nullable();
            $table->string('CONTRATO');
            $table->string('ORDEN_TRABAJO')->nullable();
            $table->string('ORDEN_EXT')->nullable();
            $table->string('CATEGORIA')->nullable();
            $table->string('RESULTADO_CIERRE');
            $table->string('HORA_INICIO')->nullable();
            $table->string('HORA_FINAL')->nullable();
            $table->string('DURACION_INSP')->nullable();
            $table->string('4_RECINTOS')->default('NO');
            $table->unsignedBigInteger('id_bitacora')->index('fr_archivo');
            $table->timestamps();
            $table->string('vence')->nullable();
            $table->boolean('PERIODO_GRACIA')->nullable();
            $table->string('CAUSAL_RECHAZO')->nullable();
            $table->unsignedBigInteger('id_usuario')->index('fr_user');
            $table->bigInteger('id_super');
            $table->string('ESTADO')->default('OK');
            $table->string('CAUSAL')->default('--SELECCIONE CAUSAL--');
            $table->boolean('G_DEVOLUCION')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_temp_contratos');
    }
};
