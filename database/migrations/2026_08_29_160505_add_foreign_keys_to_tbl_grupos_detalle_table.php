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
        Schema::table('tbl_grupos_detalle', function (Blueprint $table) {
            $table->foreign(['id_barrio'], 'detalle_has_barrio')->references(['id'])->on('tbl_barrios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_grupo'], 'detalle_has_grupo')->references(['id'])->on('tbl_grupos')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_mun'], 'detalle_has_municipio')->references(['id'])->on('tbl_localidades_municipios')->onUpdate('no action')->onDelete('no action');
            $table->foreign(['id_subGrupo'], 'detalle_has_subgrupo')->references(['id'])->on('tbl_subgrupos')->onUpdate('no action')->onDelete('no action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tbl_grupos_detalle', function (Blueprint $table) {
            $table->dropForeign('detalle_has_barrio');
            $table->dropForeign('detalle_has_grupo');
            $table->dropForeign('detalle_has_municipio');
            $table->dropForeign('detalle_has_subgrupo');
        });
    }
};
