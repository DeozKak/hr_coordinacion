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
        Schema::create('asignadas_quejas', function (Blueprint $table) {
            $table->string('NUMERO_ORDEN')->unique('numero_orden');
            $table->string('CONTRATO');
            $table->string('NUMERO_SOLICITUD');
            $table->string('TIPO_SOLICITUD');
            $table->string('DESCRIPCION_SOLICITUD');
            $table->string('CEDULA');
            $table->string('NOMBRE');
            $table->string('DESC_DEPART');
            $table->string('DESC_LOCALIDAD');
            $table->string('BARRIO');
            $table->string('DIRECCION');
            $table->string('GPS');
            $table->string('DESC_CATEGORIA');
            $table->string('COD_UNIDAD_OPER');
            $table->string('DESC_TIPO_TRABAJO');
            $table->string('FECHA_ASIGNACION');
            $table->longText('OBSERVACION_SOLICITUD');
            $table->string('FECHA_CIERRE_ULTIMA');
            $table->longText('OBSERVACIÓN_CIERRE_ULTIMA')->nullable();
            $table->string('TIPO_TRABAJO_CIERRE_ULTIMA');
            $table->string('DESC_CAUSAL_CIERRE_ULTIMA');
            $table->string('FECHA_ASIGNACIÓN_ULTIMA');
            $table->longText('OBSERVACIÓN_ASIGNACIÓN_ULTIMA');
            $table->string('GESTIÓN_ASIGNACIÓN_ULTIMA');
            $table->string('TIPO_TRABAJO_ASIGNACIÓN_ULTIMA');
            $table->bigInteger('id', true);
            $table->string('MOTIVO_DE_PQR')->nullable();
            $table->boolean('estado')->default(true);
            $table->string('FECHA_LEGALIZACION');
            $table->string('DESC_CAUSAL_LEGALIZACION');
            $table->longText('OBSERVACION_LEGALIZACION');
            $table->string('ASIGNADO')->nullable();
            $table->string('RESPONSABLE')->nullable();
            $table->string('FECHA_ASIGNADO')->nullable();
            $table->string('SUPERVISOR')->nullable();
            $table->string('RECEPCION')->nullable();
            $table->longText('OBSERVACION_GESTION')->nullable();
            $table->string('FECHA_RECEPCION')->nullable();
            $table->string('FECHA_SOLICITUD_CIERRE')->nullable();
            $table->string('CODIGO_AUTORIZACION')->nullable();
            $table->string('FECHA_RESPUESTA')->nullable();
            $table->string('FECHA_LIMITE')->nullable();
            $table->string('DIAS_FALTANTES')->nullable();
            $table->longText('INSTRUCCIONES_CAMPO')->nullable();
            $table->longText('OBSERVACION_SUPERVISOR')->nullable();
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignadas_quejas');
    }
};
