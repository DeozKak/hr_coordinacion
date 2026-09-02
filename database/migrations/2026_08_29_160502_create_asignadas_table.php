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
        Schema::create('asignadas', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('nombre_lugar', 100);
            $table->string('direccion', 100);
            $table->string('departamento', 25);
            $table->string('localidad', 50);
            $table->string('contrato', 30);
            $table->string('telefono', 20)->nullable();
            $table->string('tipo_solicitud', 50);
            $table->string('consecutivo_ruta', 50);
            $table->string('email', 60)->nullable();
            $table->string('emailCc', 60)->nullable();
            $table->double('latitud')->nullable();
            $table->double('longitud')->nullable();
            $table->integer('id_cliente');
            $table->date('fecha_ult_cert');
            $table->date('vence');
            $table->string('categoria', 50);
            $table->string('estado_producto');
            $table->string('estado_corte', 80);
            $table->text('ult_comentario')->nullable();
            $table->integer('orden');
            $table->integer('producto');
            $table->integer('numero_solicitud');
            $table->longText('observacion_solicitud')->nullable();
            $table->string('tipo_trabajo', 20);
            $table->string('sector_operativo', 80);
            $table->string('unidad_operativa', 10);
            $table->string('contratista', 40);
            $table->date('fecha_asignacion');
            $table->date('fecha_maximaEntrega');
            $table->string('NIT_CC', 30);
            $table->string('medidor', 60);
            $table->integer('orden_solicitud_externa')->nullable();
            $table->integer('tipo_solicitud_externa')->nullable();
            $table->string('fecha_solicitud_externa')->nullable();
            $table->string('observacion_externa')->nullable();
            $table->string('fecha_reasignacion_externa')->nullable();
            $table->string('estado_programacion', 50)->nullable();
            $table->integer('codigo_tecnico')->nullable();
            $table->string('nom_inspector', 50)->nullable();
            $table->string('fecha_asignacion_inspector', 30)->nullable();
            $table->integer('causa_cierre')->nullable();
            $table->string('fecha_solicitud_cierre')->nullable();
            $table->integer('orden_trabajo_cerrada')->nullable();
            $table->string('contrato_cerrada', 30)->nullable();
            $table->integer('producto_cerrada')->nullable();
            $table->string('tipo_trabajo_cerrada')->nullable();
            $table->string('fecha_legalizacion', 30)->nullable();
            $table->longText('comentario_legalizacion')->nullable();
            $table->integer('cod_causal')->nullable();
            $table->string('des_causal', 200)->nullable();
            $table->string('consecutivo', 30)->nullable();
            $table->string('dias_proceso', 30)->nullable();
            $table->string('dias_ejecutar', 30)->nullable();
            $table->integer('status')->default(1);
            $table->integer('marca')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asignadas');
    }
};
