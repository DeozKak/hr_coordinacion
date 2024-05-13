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
            $table->id()->primary()->autoIncrement(true);
            $table->string("CC_OPERARIO");
            $table->string("MUNICIPIO");
            $table->date("FECHA");
            $table->string("No_ACTA");
            $table->string("TIPO_TRABAJO");
            $table->string("CONTRATO");
            $table->string("ORDEN_TRABAJO");
            $table->string("ORDEN_EXT")->nullable(TRUE);
            $table->string("CATEGORIA");
            $table->string("RESULTADO_CIERRE");
            $table->string("HORA_INICIO");
            $table->string("HORA_FINAL");
            $table->string("DURACION_INSP");
            $table->string("4_RECINTOS");
            $table->unsignedBigInteger("id_bitacora")->autoIncrement(false);
            $table->foreign("id_bitacora" )->references( "id" )->on( "tbl_bitacora_archivos" );
            $table->timestamps();
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
