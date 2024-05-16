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
        Schema::create('tbl_localidades_municipios', function (Blueprint $table) {
            $table->id()->autoIncrement();
            $table->string('nombre', 100);
            $table->unsignedBigInteger('id_sede');
            $table->unsignedBigInteger('id_zona');
            $table->foreign('id_zona')->references('id')->on('tbl_produccion_zonas');
            $table->foreign('id_sede')->references('id')->on('tbl_localidades_sedes');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
