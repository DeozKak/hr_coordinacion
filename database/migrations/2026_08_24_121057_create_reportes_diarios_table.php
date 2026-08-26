<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reportes_diarios', function (Blueprint $table) {
            // El ID externo será nuestra llave primaria aquí
            $table->string('id')->primary();
            $table->string('NroSitio')->nullable();
            $table->string('IdTarea')->nullable();
            $table->string('Direccion')->nullable();
            $table->string('Depto')->nullable();
            $table->string('Localidad')->nullable();
            $table->dateTime('FechaRealFin')->nullable();
            $table->string('NroOperario')->nullable();
            $table->string('NombreOperario')->nullable();
            $table->string('NombreSitio')->nullable();
            $table->string('TipoTarea')->nullable();
            $table->string('Prioridad')->nullable();
            $table->string('Cierre1')->nullable();
            $table->string('Cierre2')->nullable();
            $table->string('Cierre3')->nullable();
            $table->string('AttrCategoria')->nullable();
            $table->string('Meses')->nullable();

            // Opcional: timestamps si quieres saber cuándo se insertó el registro en tu BD local
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reportes_diarios');
    }
};
