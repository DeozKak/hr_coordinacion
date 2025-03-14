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
            $table->id();
            $table->integer('id_mun');
            $table->integer('id_grupo');
            $table->integer('id_subGrupo');
            $table->integer('id_barrio');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tbl_grupo_detalle');
    }
};
