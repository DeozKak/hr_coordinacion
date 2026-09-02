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
        Schema::create('tbl_cerradas', function (Blueprint $table) {
            $table->string('NUMERO_ORDEN')->primary();
            $table->string('CONTRATO')->nullable();
            $table->string('DESC_DEPART')->nullable();
            $table->string('DESC_LOCALIDAD')->nullable();
            $table->string('DIRECCION')->nullable();
            $table->string('CATE')->nullable();
            $table->string('NOM_CATE')->nullable();
            $table->string('NOMBRE_TECNICO')->nullable();
            $table->string('ID_TIPO_TRABAJO')->nullable();
            $table->string('FECHA_ASIGNACION')->nullable();
            $table->string('FECHA_EJECUCION')->nullable();
            $table->string('FECHA_LEGALIZACION')->nullable();
            $table->string('CAUSAL')->nullable();
            $table->string('DESCCAUSAL')->nullable();
            $table->string('ACTARP')->nullable();
            $table->string('PLAZO_MAXIMO')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_cerradas');
    }
};
