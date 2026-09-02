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
        Schema::create('tbl_parametro_precios', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('fecha_inicio', 30);
            $table->string('fecha_fin', 30);
            $table->integer('res_metro');
            $table->integer('res_norte');
            $table->integer('res_cauca');
            $table->integer('com_metro');
            $table->integer('com_norte');
            $table->integer('com_cauca');
            $table->integer('inspeccion_industrial');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_parametro_precios');
    }
};
