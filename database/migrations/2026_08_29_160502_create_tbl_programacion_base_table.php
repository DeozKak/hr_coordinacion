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
        Schema::create('tbl_programacion_base', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('NUMERO_ORDEN')->unique('numero_orden');
            $table->string('CONTRATO');
            $table->string('DESC_ESTADO_PROD');
            $table->string('NOMBRE');
            $table->string('DESC_LOCALIDAD');
            $table->string('BARRIO');
            $table->string('DIRECCION');
            $table->string('NOM_CATE');
            $table->string('ID_TIPO_TRABAJO');
            $table->bigInteger('ID_TECNICO')->nullable();
            $table->date('FECHA_ASIGNACION')->nullable();
            $table->string('ESTADO_RECEPCION')->nullable()->default('0');
            $table->date('FECHA_RECEPCION')->nullable();
            $table->string('SEDE')->nullable();
            $table->string('GRUPO')->nullable();
            $table->string('SUB_GRUPO')->nullable();
            $table->integer('MESES')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->timestamp('updated_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_programacion_base');
    }
};
