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
            $table->id();
            $table->primary('id');
            $table->string('nombre_lugar',80);
            $table->string('direccion',100);
            $table->string('departamento',25);
            $table->string('localidad',50);
            $table->string('contrato',30);
            $table->string('telefono',20)->nullable(true);
            $table->string('email',60)->nullable(true);
            $table->string('emailCc',60)->nullable(true);
            $table->double('latitud')->nullable(true);
            $table->double('longitud')->nullable(true);
            $table->integer('id_cliente')->autoIncrement(false);
            $table->date('vence');
            $table->string('categoria',50);
            $table->string('estado_producto');
            $table->string('estado_corte',80);
            $table->integer('orden',11)->autoIncrement(false);
            $table->integer('orden_externa',11)->autoIncrement(false)->nullable(true);
            $table->integer('producto',11)->autoIncrement(false);
            $table->integer('numero_solicitud',11)->autoIncrement(false);
            $table->string('tipo_trabajo',20);
            $table->string('sector_operativo',80);
            $table->string('unidad_operativa',10);
            $table->string('contratista',40);
            $table->date('fecha_asignacion');
            $table->date('fecha_externa')->nullable(true);
            $table->date('fecha_maximaEntrega');
            $table->string('NIT_CC',30);
            $table->string('medidor',60);
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
