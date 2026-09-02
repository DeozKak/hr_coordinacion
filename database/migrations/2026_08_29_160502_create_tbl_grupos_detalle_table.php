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
        Schema::create('tbl_grupos_detalle', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('id_mun')->nullable()->index('detalle_has_municipio');
            $table->unsignedBigInteger('id_grupo')->nullable()->index('detalle_has_grupo');
            $table->unsignedBigInteger('id_subGrupo')->nullable()->index('detalle_has_subgrupo');
            $table->unsignedBigInteger('id_barrio')->nullable()->index('detalle_has_barrio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_grupos_detalle');
    }
};
