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
        Schema::create('tbl_quejas_contratos', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('NOMBRE');
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
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_quejas_contratos');
    }
};
