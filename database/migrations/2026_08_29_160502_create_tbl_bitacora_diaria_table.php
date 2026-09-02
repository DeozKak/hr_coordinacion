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
        Schema::create('tbl_bitacora_diaria', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('CC_OPERARIO');
            $table->string('MUNICIPIO');
            $table->date('FECHA');
            $table->string('ACTA');
            $table->string('TIPO_TRABAJO');
            $table->string('CONTRATO');
            $table->string('ORDEN_TRABAJO')->nullable();
            $table->string('ORDEN_EXT')->nullable();
            $table->string('CATEGORIA')->nullable();
            $table->string('RESULTADO_CIERRE');
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_bitacora_diaria');
    }
};
