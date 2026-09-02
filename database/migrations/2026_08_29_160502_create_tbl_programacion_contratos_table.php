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
        Schema::create('tbl_programacion_contratos', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('CONTRATO');
            $table->string('TIPO_TRABAJO')->nullable();
            $table->date('FECHA')->nullable();
            $table->string('CELULAR')->nullable();
            $table->string('NOMBRE_USUARIO');
            $table->string('ORDEN_TRABAJO');
            $table->string('DIRECCION');
            $table->string('BARRIO');
            $table->string('CIUDAD');
            $table->string('ACTIVA');
            $table->string('SUSPENDIDO');
            $table->string('CATEGORIA');
            $table->date('FECHA_AGENDAMIENTO')->nullable();
            $table->longText('OBSERVACIONES')->nullable();
            $table->string('PORQUE_PROGRAMO');
            $table->string('TECNICO')->nullable();
            $table->string('HORA_INICIO')->nullable();
            $table->string('HORA_FINAL')->nullable();
            $table->string('JORNADA')->nullable();
            $table->unsignedBigInteger('id_programacion')->index('fr_programacion_usuario');
            $table->boolean('mensaje')->default(false);
            $table->boolean('plantilla')->default(false);
            $table->boolean('EJECUTADA')->nullable()->default(false);
            $table->timestamp('updated_at')->nullable()->useCurrent();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_programacion_contratos');
    }
};
