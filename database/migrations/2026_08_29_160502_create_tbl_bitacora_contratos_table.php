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
        Schema::create('tbl_bitacora_contratos', function (Blueprint $table) {
            $table->bigIncrements('id');
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
            $table->string('PRIORIDAD')->nullable();
            $table->string('4_RECINTOS')->default('NO');
            $table->unsignedBigInteger('id_bitacora')->index('tbl_bitacora_contratos_id_bitacora_foreign');
            $table->timestamps();
            $table->integer('state')->default(1);
            $table->integer('diseno_especial')->nullable()->default(0);
            $table->string('vence')->nullable();
            $table->boolean('PERIODO_GRACIA')->nullable();
            $table->string('CAUSAL_RECHAZO')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_bitacora_contratos');
    }
};
