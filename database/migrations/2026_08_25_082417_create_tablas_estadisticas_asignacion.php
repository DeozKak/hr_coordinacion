<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabla de Asignaciones (Se borra y se reescribe)
        Schema::create('tbl_asignaciones', function (Blueprint $table) {
            $table->id();
            $table->string('NUMERO_ORDEN')->nullable();
            $table->string('CONTRATO')->nullable();
            $table->string('PRODUCTO')->nullable();
            $table->string('NUMERO_SOLICITUD')->nullable();
            $table->string('TIPO_SOLICITUD')->nullable();
            $table->string('CEDULA')->nullable();
            $table->string('NOMBRE')->nullable();
            $table->string('DESC_DEPART')->nullable();
            $table->string('DESC_LOCALIDAD')->nullable();
            $table->string('BARRIO')->nullable();
            $table->string('DIRECCION')->nullable();
            $table->string('CONSECUTIVO_RUTA')->nullable();
            $table->string('TELEFONO')->nullable();
            $table->string('MEDIDOR')->nullable();
            $table->string('DESC_CATEGORIA')->nullable();
            $table->string('COD_UNIDAD_OPER')->nullable();
            $table->string('ID_TIPO_TRABAJO')->nullable();
            $table->string('FECHA_ASIGNACION')->nullable();
            $table->text('OBSERVACION_SOLICITUD')->nullable();
            $table->string('DESC_ESTADO_PRODUCTO')->nullable();
            $table->string('DESC_ESTADO_CORTE')->nullable();
            $table->text('ULTIMO_COMENTARIO')->nullable();
            $table->string('FECHA_ULTCERTI')->nullable();
            $table->string('PLAZO_MAXIMO')->nullable();
            $table->string('OIA_RECHAZO')->nullable();
            $table->string('FECHA_RECHAZO')->nullable();
            $table->text('COMENTARIO_RECHAZO')->nullable();
            $table->timestamps();
        });

        // 2. Tabla de Cerradas (Acumulativa)
        Schema::create('tbl_cerradas', function (Blueprint $table) {
            // El NUMERO_ORDEN será nuestra llave principal para evitar duplicados en el histórico
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

    public function down(): void
    {
        Schema::dropIfExists('tbl_asignaciones');
        Schema::dropIfExists('tbl_cerradas');
    }
};
