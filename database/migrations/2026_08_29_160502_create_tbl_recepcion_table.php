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
        Schema::create('tbl_recepcion', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->integer('ordenTrabajo')->nullable();
            $table->integer('ordenExterna')->nullable();
            $table->string('ccOperario', 20);
            $table->integer('numeroSolicitud')->nullable();
            $table->string('contrato', 100)->nullable();
            $table->string('tipo', 20)->nullable();
            $table->integer('idVne')->nullable();
            $table->string('direccion')->nullable();
            $table->integer('numActa')->nullable();
            $table->integer('estadoRecepcion')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_recepcion');
    }
};
