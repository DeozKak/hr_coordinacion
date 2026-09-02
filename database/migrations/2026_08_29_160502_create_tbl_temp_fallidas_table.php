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
        Schema::create('tbl_temp_fallidas', function (Blueprint $table) {
            $table->string('NOMBRE');
            $table->bigIncrements('id');
            $table->string('CC_OPERARIO');
            $table->string('MUNICIPIO');
            $table->date('FECHA');
            $table->string('No_ACTA');
            $table->string('TIPO_TRABAJO');
            $table->string('CONTRATO');
            $table->string('ORDEN_TRABAJO')->nullable();
            $table->string('ORDEN_EXT')->nullable();
            $table->string('CATEGORIA')->nullable();
            $table->string('RESULTADO_CIERRE');
            $table->timestamp('created_at')->useCurrentOnUpdate()->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
            $table->unsignedBigInteger('id_bitacora')->index('fr_bitacora_fallidas');
            $table->unsignedBigInteger('id_usuario')->index('fr_usuario_fallidas');
            $table->unsignedBigInteger('id_super');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_temp_fallidas');
    }
};
