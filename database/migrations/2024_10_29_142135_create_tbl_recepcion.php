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
            $table->id();
            $table->integer('ordenTrabajo')->nullable(true);
            $table->integer('ordenExterna')->nullable(true);
            $table->string('ccOperario', 20)->nullable(true);
            $table->integer('numeroSolicitud')->nullable(true);
            $table->string('contrato', 100)->nullable(true);
            $table->string('direccion',255)->nullable(true);
            $table->integer('numActa')->nullable(true);
            $table->integer('estadoRecepcion')->nullable(true);
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
