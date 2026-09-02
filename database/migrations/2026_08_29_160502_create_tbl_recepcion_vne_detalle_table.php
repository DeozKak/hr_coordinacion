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
        Schema::create('tbl_recepcion_vne_detalle', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('ordenTrabajo');
            $table->integer('idVne');
            $table->string('ccOperario');
            $table->string('comObservacion', 300);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_recepcion_vne_detalle');
    }
};
